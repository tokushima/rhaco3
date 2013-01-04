<?php
ini_set('display_errors','On');
ini_set('html_errors','Off');

set_error_handler(function($n,$s,$f,$l){
	throw new ErrorException($s,0,$n,$f,$l);
});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
$parse_args = function($json_file=null,$merge_keys=array()){
	$params = array();
	$value = $cmd = null;
	if(isset($_SERVER['REQUEST_METHOD'])){
		$params = isset($_GET) ? $_GET : array();
	}else{
		$argv = $_SERVER['argv'];
		$argv = array_slice($argv,1);
		$cmd = array_shift($argv);
		$value = (empty($argv)) ? null : array_shift($argv);
		$params = array();
		
		if(substr($value,0,1) == '-'){
			array_unshift($argv,$value);
			$value = null;
		}
		for($i=0;$i<sizeof($argv);$i++){
			if($argv[$i][0] == '-'){
				$k = substr($argv[$i],1);
				$v = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
				if(isset($params[$k]) && !is_array($params[$k])) $params[$k] = array($params[$k]);
				$params[$k] = (isset($params[$k])) ? array_merge($params[$k],array($v)) : $v;
			}
		}
	}
	if(!empty($json_file) && is_file($json_file)){
		$json_ar = json_decode(file_get_contents($json_file),true);
		if($json_ar === null) die('json parse error: '.$json_file);
		foreach($merge_keys as $k){
			if(isset($json_ar[$k])){
				if(isset($params[$k]) && !is_array($params[$k])) $params[$k] = array($params[$k]);
				$params[$k] = isset($params[$k]) ? array_merge($params[$k],((is_array($json_ar[$k]) ? $json_ar[$k] : array($json_ar[$k])))) : $json_ar[$k];
			}
		}
	}
	$_ENV['cmd'] = $cmd;
	$_ENV['value'] = $value;
	$_ENV['params'] = $params;
	return array($cmd,$value,$params);
};
$has = function($key){
	return (isset($_ENV['params']) && array_key_exists($key,$_ENV['params']));
};
$in_value = function($key,$default=null){
	if(!isset($_ENV['params'][$key])) return $default;
	$param = $_ENV['params'][$key];
	return (is_array($param)) ? array_pop($param) : $param;
};
$in_array = function($key,$default=array()){
	if(!isset($_ENV['params'][$key])) return $default;
	return (is_array($_ENV['params'][$key])) ? $_ENV['params'][$key] : array($_ENV['params'][$key]);
};
$autoload_func = function($c){
	$cp = str_replace('\\','//',(($c[0] == '\\') ? substr($c,1) : $c));
	foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
		if(!empty($p) && ($r = realpath($p)) !== false){
			if(is_file($f=($r.'/'.$cp.'.php')) || is_file($f=($r.'/'.$cp.'/'.basename($cp).'.php'))){
				require_once($f);
				if(class_exists($c,false) || interface_exists($c,false)){
					return true;
				}
			}
		}
	}
	if(class_exists($c,false) || interface_exists($c,false)){
		if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
		if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
		return true;
	}
	return false;
};
$json_file = preg_replace('/^.+\:\/\/(.+)$/','\\1',preg_replace('/^(.+)\.[^\/]+/','\\1',__FILE__)).'.json';
$merge_keys = array('bootstrap');
list($cmd,$value,$params) = $parse_args($json_file,$merge_keys);

if($has('bootstrap')){
	foreach($in_array('bootstrap') as $bp){
		if(!is_file(realpath($bp))) die('bootstrap: '.$bp.' No such File'.PHP_EOL);
		ob_start();
			include_once(realpath($bp));
		ob_end_clean();
	}
}else if(is_file($bf=realpath('bootstrap.php'))){
	ob_start();
		include_once($bf);
	ob_end_clean();	
}
spl_autoload_register($autoload_func,true,false);

set_include_path('./lib'.PATH_SEPARATOR.get_include_path());

$println = function($value,$fmt=null,$indent=0){
	if($indent > 0) $value = str_repeat(' ',$indent).implode(PHP_EOL.str_repeat(' ',$indent),explode(PHP_EOL,$value));
	if(substr(PHP_OS,0,3) == 'WIN'){
		$value = mb_convert_encoding($value,'UTF-8','SJIS');
	}else if($fmt !== null){
		$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
		$value = "\033[".$fmt.'m'.$value."\033[0m";
	}
	print($value.PHP_EOL);
};
$command = function($is_summary,$is_help,$cmd,$pathname,$package_base) use($println,$has,$in_value,$in_array){
	$package = str_replace('/','.',$package_base);
	$src = file_get_contents($pathname);
	$doc = (preg_match('/\/\*\*.+?\*\//s',$src,$m)) ? trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[0]))) : '';
	$cmd_array = array();
	
	if(strpos($package,'::') === false && strpos($doc,'@autocmd') !== false){
		if(is_file($f=(dirname($pathname).'.php')) || is_file($f=(dirname($pathname).'/'.basename(dirname($pathname)).'.php'))){
			if(preg_match('/namespace\s([\w\\\\]+)/',file_get_contents($f),$m)){
				$ref = new ReflectionClass('\\'.$m[1].'\\'.substr(basename($f),0,-4));
	
				foreach($ref->getMethods(ReflectionMethod::IS_STATIC|ReflectionMethod::IS_PUBLIC) as $m){
					if(strpos($m->getDocComment(),'@autocmd') !== false){
						$cmd_array[$package.'::'.$m->getName()] = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$m->getDocComment()));
					}
				}
			}
		}
	}else{
		$cmd_array[$package] = $doc;
	}
	if(!$is_summary && !$is_help && isset($cmd_array[$cmd])){
		try{
			$_ENV['return'] = $return = 0;
			$value = $_ENV['value'];

			if(sizeof($cmd_array) == 1){
				try{
					if(is_file(dirname($pathname).'/__setup__.php')) include(dirname($pathname).'/__setup__.php');
					$_ENV['return'] = $return = include($pathname);
					if(is_file(dirname($pathname).'/__teardown__.php')) include(dirname($pathname).'/__teardown__.php');
				}catch(Exception $e){
					$_ENV['exception'] = $e;
					if(is_file(dirname($pathname).'/__exception__.php')) include(dirname($pathname).'/__exception__.php');
					throw $e;
				}
			}else{
				list($class,$method) = explode('::','\\'.str_replace('.','\\',$cms),2);
				$has_method = function($class,$method){
					return (method_exists($class,$method) &&
						($i = new ReflectionMethod($class,$method)) &&
						$i->isStatic() &&
						strpos($i->getDocComment(),'@autocmd') !== false
					);
				};
				try{
					if($has_method($class,'__setup__')) call_user_func(array($class,'__setup__'));
					$_ENV['return'] = $return = call_user_func(array($class,$method));
					if($has_method($class,'__teardown__')) call_user_func(array($class,'__teardown__'));
				}catch(Exception $e){
					$_ENV['exception'] = $e;
					if($has_method($class,'__exception__')) call_user_func(array($class,'__exception__'));
					throw $e;
				}
			}
		}catch(Exception $e){
			$println(PHP_EOL.$e->getMessage(),false);
		}
		exit;
	}else{
		$help_params = array();
		$pad = 4;
		$pvalue = '';
				
		if($is_summary){
			$summary_array = array();
			foreach($cmd_array as $n => $d){
				list($summary) = explode("\n",trim(preg_replace('/@.+/','',$d)));
				$summary_array[$n] = $summary;
			}
			return $summary_array;
		}else if(isset($cmd_array[$cmd])){
			$doc = $cmd_array[$cmd];
			list($pkg) = explode('::',$cmd);
			
			if(preg_match_all('/@.+/',$doc,$as)){
				foreach($as[0] as $m){
					if(preg_match("/@(\w+)\s+([^\s]+)\s+\\$(\w+)(.*)/",$m,$p)){
						if($p[2] == '$this' || $p[2] == 'self') $p[2] = $pkg;
						if($p[1] == 'param' && $p[3] == 'value'){
							$pvalue = sprintf('[(%s) %s]',$p[2],trim($p[4]));
						}else if($p[1] == 'param'){
							$help_params[$p[3]] = array($p[2],trim($p[4]));
						}
					}else if(preg_match("/@(\w+)\s+\\$(\w+)(.*)/",$m,$p)){
						$help_params[$p[2]] = array(null,trim($p[3]));
					}
				}
				foreach(array_keys($help_params) as $k){
					if($pad < strlen($k)){
						$pad = strlen($k);
					}
				}
			}
			print(PHP_EOL.'Usage:'.PHP_EOL);
			print('  '.$cmd.PHP_EOL);
			if(!empty($help_params)){
				print("\n  Options:\n");
				foreach($help_params as $k => $v){
					print('    '.sprintf('-%s%s %s',str_pad($k,$pad),(empty($v[0]) ? '' : ' ('.$v[0].')'),trim($v[1]))."\n");
				}
			}
			$doc = trim(preg_replace('/@.+/','',$doc));
			print("\n\n  description:\n");
			print('    '.str_replace("\n","\n    ",$doc)."\n\n");
			exit;
		}
	}
	return array();
};

$is_summary = empty($cmd);
$is_help = (substr($cmd,-1) == '?');
$cmd = ($is_help) ? substr($cmd,0,-1) : $cmd;
$summary_array = $load_array = array();
$include_path = explode(PATH_SEPARATOR,get_include_path());
rsort($include_path);
foreach($include_path as $p){
	if(($r = realpath($p)) !== false){
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
						$r,
						FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS
				),RecursiveIteratorIterator::SELF_FIRST
		) as $f){
			if($f->isDir() &&
				!isset($load_array[$f->getPathname()]) &&
				ctype_upper(substr($f->getFilename(),0,1)) &&
				strpos($f->getPathname(),'/.') === false &&
				strpos($f->getFilename(),'_') !== 0
			){
				if(is_file($cf=$f->getPathname().'/cmd.php')){
					foreach($command(
								$is_summary,
								$is_help,
								$cmd,
								$cf,
								str_replace($r.'/','',$f->getPathname())
					) as $n => $s){
						$summary_array[$n] = $s;
					}
				}
				if(is_dir($cd=$f->getPathname().'/cmd/')){
					foreach(new DirectoryIterator($cd) as $fi){
						if($fi->isFile() && 
							strpos($fi->getFilename(),'_') !== 0 && 
							$fi->getExtension() == 'php'
						){
							foreach($command(
										$is_summary,
										$is_help,
										$cmd,
										$fi->getPathname(),
										str_replace($r.'/','',dirname($fi->getPath())).'::'.substr($fi->getFilename(),0,-4)
							) as $n => $s){
								$summary_array[$n] = $s;
							}
						}
					}
				}
				$load_array[$f->getPathname()] = true;
			}
		}
	}
}
if($is_summary){
	ksort($summary_array);
	print(PHP_EOL.'Commands: '.PHP_EOL);
	
	$len = 8;
	foreach($summary_array as $n => $s){
		if($len < strlen($n)) $len = strlen($n);
	}
	foreach($summary_array as $n => $s){
		print('  '.str_pad($n,$len).' : '.$s.PHP_EOL);
	}
}else{
	print($cmd.' not found'.PHP_EOL);
}

