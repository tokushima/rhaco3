<?php
if(!class_exists('Rhaco3')){
	/**
	 * rhaco3の環境定義クラス
	 * @author tokushima
	 */
	class Rhaco3{
		static private $env;
		static private $common_dir;
		static private $lib_dir;
		static private $rep = array('http://rhaco.org/repository/3/lib/');
		/**
		 * ライブラリのパスを設定する
		 * @param string $env 実行モード
		 * @param string $lib_dir ライブラリのディレクトリパス
		 * @param string $common_dir 設定ファイルのディレクトリ 
		 */
		static public function config_path($env=null,$lib_dir=null,$common_dir=null){
			if(self::$env === null) self::$env = (empty($env) ? 'local' : $env);
			if(self::$lib_dir === null){
				if(empty($lib_dir)) $lib_dir = getcwd().'/lib/';				
				self::$lib_dir = str_replace('\\','/',$lib_dir);
				if(substr(self::$lib_dir,-1) != '/') self::$lib_dir = self::$lib_dir.'/';
			}
			if(self::$common_dir === null){
				if(empty($common_dir)) $common_dir = getcwd().'/commons/';
				self::$common_dir = str_replace('\\','/',$common_dir);
				if(substr(self::$common_dir,-1) != '/') self::$common_dir = self::$common_dir.'/';
			}			
			define('APPENV',self::$env);
			define('LIBDIR',self::$lib_dir);
			define('__PEAR_DATA_DIR__',self::$lib_dir.'_extlib/data');
			set_include_path(self::$lib_dir.'_extlib'.PATH_SEPARATOR.get_include_path());			
		}
		/**
		 * リポジトリの場所を指定する
		 * @param string $rep リポジトリのパス
		 */
		static public function repository($rep){
			array_unshift(self::$rep,$rep);
		}
		/**
		 * リポジトリパスの一覧を返す
		 * @return string[]
		 */
		static public function repositorys(){
			return self::$rep;
		}
		/**
		 * ライブラリのディレクトリ
		 * @return string
		 */
		static public function lib_dir(){
			if(self::$lib_dir === null) self::config_path();
			return self::$lib_dir;
		}
		/**
		 * 設定ファイルのディレクトリ
		 * @return string
		 */
		static public function common_dir(){
			if(self::$common_dir === null) self::config_path();
			return self::$common_dir;
		}
		/**
		 * 実行環境を設定/取得
		 * @return string モード
		 */
		static public function env(){
			if(self::$env === null) self::config_path();
			return self::$env;
		}
	}
}
spl_autoload_register(function($c){
	$libdir = Rhaco3::lib_dir();
	if(substr($libdir,-1) != '/') $libdir = $libdir.'/';
	if($c[0] == '\\') $c = substr($c,1);
	$p = str_replace('\\','/',$c);
	if(ctype_upper($p[0]) || preg_match('/^(.+)\/([A-Z][\w_]*)$/',$p,$m)){
		foreach(array('','_vendor/') as $q){
			if(is_file($f=($libdir.$q.$p.'.php'))){require_once($f);break;
			}else if(isset($m[2]) && is_file($f=($libdir.$q.$p.'/'.$m[2].'.php'))){require_once($f);break;}
		}
	}
	if(!class_exists($c,false) && !interface_exists($c,false)){
		$e = $libdir.'_extlib/';
		if(is_file($f=$e.$p.'.php')){require_once($f);
		}else if(is_file($f=$e.str_replace('_','/',$c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.class.php')){require_once($f);
		}else if((strpos($c,'_')!==false)&&(is_file($f=$e.implode('/',array_slice(explode('_',$c),0,-1)).'.php'))){require_once($f);
		}else{$f=$c;}
	}
	if(class_exists($c,false) || interface_exists($c,false)){
		if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
		if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
		return true;
	}
	return false;
},true,false);
ini_set('display_errors','On');
ini_set('html_errors','Off');
set_error_handler(function($n,$s,$f,$l){throw new ErrorException($s,0,$n,$f,$l);});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(getcwd().'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::env() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::env().'.php'))) require_once($f);
	}
	return;
}
if(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])){header('HTTP/1.1 404 Not Found');exit;}
if(isset($_SERVER['argv'][1])){
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
	$get_args = function(){
		$argv = array_slice($_SERVER['argv'],2);
		$value = (empty($argv)) ? null : array_shift($argv);
		if(substr($value,0,1) == '-'){
			array_unshift($argv,$value);
			$value = null;
		}
		$params = array();
		for($i=0;$i<sizeof($argv);$i++){
			if($argv[$i][0] == '-') $params[substr($argv[$i],1)] = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
		}
		return array($value,$params);	
	};
	$htaccess = function($base){
		$base = empty($base) ? '/'.basename(getcwd()) : $base;
		if(substr($base,0,1) !== '/') $base = '/'.$base;
		$rules = "RewriteEngine On\nRewriteBase ".$base."\n\n";
		foreach(new DirectoryIterator(getcwd()) as $f){
			if($f->isFile() && substr($f->getPathname(),-4) == '.php' && substr($f->getFilename(),0,1) != '_' && $f->getPathname() != __FILE__ && $f->getFilename() != 'index.php'){
				$src = file_get_contents($f->getPathname());
				if(strpos($src,'Flo'.'w') !== false && (strpos($src,'->outpu'.'t(') !== false || strpos($src,'Flo'.'w::out(') !== false)){
					$app = substr($f->getFilename(),0,-4);
					$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^".$app."[/]{0,1}(.*)\$ ".$app.".php/\$1?%{QUERY_STRING} [L]\n\n";
				}
			}
		}
		if(is_file(getcwd().'/index.php')) $rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^(.*)\$ index.php/\$1?%{QUERY_STRING} [L]\n\n";
		file_put_contents('.htaccess',$rules);
		print('Written: '.realpath('.htaccess').PHP_EOL.str_repeat('-',60).PHP_EOL.trim($rules).PHP_EOL.str_repeat('-',60).PHP_EOL);		
	};
	$download = function($argv,$renew) use($println){		
		$search = function($s){
			$z = $u = array();
			$s = preg_replace("/\/\*.+?\*\//s",'',$s);
			if(preg_match_all('/([\"\'])(\w+\.[\.\w:]+?)\\1/',$s,$m)){
				foreach($m[2] as $c){
					if(strpos($c,':') !== false) list($c) = explode(':',$c); 
					if(preg_match('/\.[A-Z]\w*$/',$c)) $z['\\'.str_replace('.','\\',$c)] = '\\'.str_replace('.','\\',$c);
				}
			}
			$s = preg_replace("/([\"\']).*?\\1/",'',$s);
			if(preg_match_all('/use\s+([\w\\\\]+)\s*;/',$s,$m)){foreach($m[1] as $c) $z[$c] = $u[preg_replace('/^.+\\\\([^\\\\]+)$/','\\1',$c)] = (($c[0] != "\\") ? "\\" : '').$c;}
			if(preg_match_all('/use\s+([\w\\\\]+)\s+as\s+(\w+)\s*;/',$s,$m)){foreach($m[1] as $k => $c) $z[$c] = $u[$m[2][$k]] = (($c[0] != "\\") ? "\\" : '').$c;}
			if(preg_match_all('/\s+instanceof\s+([\\\\\w]+)/',$s,$m)){foreach($m[1] as $k => $c) $z[$c] = $c;}
			if(preg_match_all('/\s+class_exists\(([\"\'])([\\\\\w]+)\\1/',$s,$m)){foreach($m[2] as $k => $c) $z[$c] = $c;}
			if(preg_match('/\s+extends\s+([\\\\\w]+)/',$s,$m)) $z[$m[1]] = $m[1];
			if(preg_match_all('/new ([\w\\\\]+)|([\w\\\\]+)::/',$s,$m)){for($i=1;$i<=2;$i++){foreach($m[$i] as $c){$z[$c] = $c;}}}
			if(preg_match('/implements\s+([\\\\\w,\s]+)/ms',$s,$m)){foreach(explode(',',$m[1]) as $r) $z[trim($r)] = trim($r);}
			if(!empty($z)){
				$n = ((preg_match("/namespace ([\w\\\\]+)/",$s,$m)) ? $m[1] : null);
				if(!empty($n)) $n = '\\'.$n;
				foreach($z as $k => $v){
					$r = $k;
					if(!empty($k)){
						if(isset($u[$k])){$r = $u[$k];}else if(!empty($n) && strpos($v,'\\') !== 0){$r = $n.'\\'.$k;}
						if($r != $k) unset($z[$k]);
						$z[$r] = str_replace('\\','.',preg_replace('/^\\\\(.+)$/','\\1',$r));
					}
					if(empty($k) || !preg_match('/[A-Z]/',$r) || preg_match('/^(.*[A-Z].*)\\\\\w+$/',$r) || strpos(substr($r,1),'\\') === false) unset($z[$r]);
				}
			}
			return $z;
		};
		$rm = function($d,$t){if(is_dir($d)){
			$f = array();
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS),RecursiveIteratorIterator::SELF_FIRST) as $e) $f[] = $e->getPathname();
			rsort($f);
			foreach($f as $p) ((is_file($p)) ? unlink($p) : rmdir($p));
			if($t && is_dir($d)) rmdir($d);
		}};
		$pkg = function(&$imported,$package) use(&$pkg,$rm,$search,$println){
			if(isset($imported[$package])) return true;
			$imported[$package] = true;
			$dl = str_replace(array('/',':','-'),array('.','_','_'),$package);
			$dp = Rhaco3::lib_dir().'_download/';
			$ep = Rhaco3::lib_dir().'_download/extract/'.$package.'/';
			$vp = Rhaco3::lib_dir().'_vendor/';

			foreach(Rhaco3::repositorys() as $rp){
				try{
					$rp = str_replace('\\','/',$rp);					
					if(substr($rp,-1) != '/') $rp = $rp.'/';
					if(strpos($dl,'_') !== false) $rp = $rp.'previous/';
					if(strpos($rp,'://') !== false){
						if(!is_dir($dp)) mkdir($dp,0777,true);
						if(is_file($dp.$dl.'.tgz')) unlink($dp.$dl.'.tgz');
						$fr = fopen($rp.$dl.'.tgz','rb');
						$fw = fopen($dp.$dl.'.tgz','wb');
						while(!feof($fr)) fwrite($fw,fread($fr,4096));
						fclose($fr);
						fclose($fw);
					}else{
						$dp = $rp;
					}
					if(is_file($dp.$dl.'.tgz')){
						$p = str_replace('.','/',$package);
						if(is_file($vp.$p.'.php')) unlink($vp.$p.'.php');
						if(is_file($vp.$p.'/'.basename($p).'.php')) $rm($vp.$p,true);
						$fp = gzopen($dp.$dl.'.tgz','rb');
						while(!gzeof($fp)){
							$b = gzread($fp,512);
							if(strlen($b) < 512) break;
							$d = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/'
											.'a8chksum/'
											.'a1typeflg/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix',
											 $b);
							if(!empty($d['name'])){
								if($d['name'][0] == '/') $d['name'] = substr($d['name'],1);
								if(ctype_alnum($d['name'][0])){
									$path = $ep.$d['name'];
									if(ctype_digit($d['typeflg'])){
										switch((int)$d['typeflg']){
											case 0:
												$size = base_convert($d['size'],8,10);
												if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
												for($i=0;$i<=$size;$i+=512){
													$s = ($i+512>$size) ? $size - $i : 512;
													if($s > 0){
														file_put_contents($path,gzread($fp,$s),FILE_APPEND);
														if($s < 512) gzread($fp,512-$s);
													}
												}
												touch($path,base_convert($d['mtime'],8,10));
												break;
											case 5:
												if(!is_dir($path)) mkdir($path,0777,true);
												break;
										}
									}
								}
							}
						}
						gzclose($fp);
						$required = array();
						if(!is_dir($ep)) return false;
						foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ep,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
							$so = str_replace($ep,$vp,$f->getPathname());
							if(!is_dir(dirname($f->getPathname()))) mkdir(dirname($f->getPathname()),0777,true);
							if(!is_dir(dirname($so))) mkdir(dirname($so),0777,true);
							copy($f->getPathname(),$so);
							if(substr($f->getPathname(),-4) == '.php'){$required = array_merge($required,$search(file_get_contents($f->getPathname())));}
						}
						$rm($ep,true);
						foreach($required as $r){if(!$pkg($imported,$r)){$println('not found: '.$package.' > '.$package.' in '.$r,false);}}
						$println('installed: '.$package.' ('.$rp.')');
						return true;
					}
				}catch(Exception $e){
					$println($rp.$dl.'.tgz'.' => '.$e->getMessage(),false);
				}
			}
			return false;
		};
		$error = $invalid = $imported = array();
		$argv = array_flip($argv);
		if(empty($argv)){
			foreach(new DirectoryIterator(getcwd()) as $f){
				if($f->isFile() && strpos($f->getPathname(),'/_') === false && substr($f->getFilename(),-4) == '.php' && strpos($f->getPathname(),Rhaco3::lib_dir()) === false){
					foreach($search(file_get_contents($f->getPathname())) as $k => $v) $argv[$v] = $f->getPathname();
				}
			}
			if(is_dir(Rhaco3::lib_dir())){
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::lib_dir(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					if($f->isFile() && strpos($f->getPathname(),'/_') === false && substr($f->getPathname(),-4) == '.php'){ foreach($search(file_get_contents($f->getPathname())) as $k => $v){ $argv[$v] = $f->getPathname(); } }
				}
			}
			if(is_dir(Rhaco3::common_dir())){
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::common_dir(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					if($f->isFile() && strpos($f->getPathname(),'/_') === false && substr($f->getPathname(),-4) == '.php'){ foreach($search(file_get_contents($f->getPathname())) as $k => $v){ $argv[$v] = $f->getPathname(); } }
				}
			}
			if(is_file(getcwd().'/__settings__.php')){
				foreach($search(file_get_contents(getcwd().'/__settings__.php')) as $k => $v){ $argv[$v] = $f->getPathname(); }
			}
		}
		foreach($argv as $arg => $f){
			ob_start();
			if(($b = class_exists($p = '\\'.str_replace('.','\\',$arg))) || ($b = interface_exists($p = '\\'.str_replace('.','\\',$arg)))){
				if($renew){
					$r = new ReflectionClass($p);
					$b = (strpos($r->getFilename(),(Rhaco3::lib_dir().'_vendor')) === false);
				}
			}
			if(ob_get_clean() != ''){
				$invalid[$arg] = $f;
			}else if(!$b && !$pkg($imported,$arg)){
				$error[$arg] = $f;
			}
		}
		$rm((Rhaco3::lib_dir().'_download/'),true);
		if(!empty($error)){
			foreach($error as $p => $f) $println('not found: '.(is_int($f) ? '' : $f.(' in ')).$p,false);
		}
		if(!empty($invalid)){
			foreach($invalid as $p => $f) $println('invalid source: '.(is_int($f) ? '' : $f.(' in ')).$p,false);
		}
	};
	try{
		set_time_limit(0);
		$help = (substr($_SERVER['argv'][1],-1) == '?');
		$cmd = ($help) ? substr($_SERVER['argv'][1],0,-1) : $_SERVER['argv'][1];
		list($value,$params) = $get_args();
		
		switch($cmd){
			case '-import':
				if(is_file($f=getcwd().'/__settings__.php') && preg_match_all('/\n\s*[\\\\]{0,1}Rhaco3::.+?\);/ms',file_get_contents($f),$m)){foreach($m[0] as $e){eval($e);}}
				if(isset($params['repository'])) Rhaco3::repository($params['repository']);
				if(is_file(Rhaco3::lib_dir()) || strpos(Rhaco3::lib_dir(),'://') !== false) throw new RuntimeException(Rhaco3::lib_dir().' is not a directory');
				$download(empty($value) ? array() : array($value.(isset($params['v']) ? ':'.$params['v'] : '')),true);
				exit;
			case '-phar':
				if(!Phar::canWrite()) die('write operations disabled by the php.ini setting phar.readonly'.PHP_EOL.' > php -d phar.readonly=0 '.basename(__FILE__).' '.$_SERVER['argv'][1].PHP_EOL);
				$path = getcwd().'/lib_'.date('Ymd_Hi').'.phar';
				$phar = new Phar($path,0,'lib.phar');
				print('Written: '.$path.PHP_EOL);
				$phar->setDefaultStub((isset($phar['cli.php']) ? 'cli.php' : '<?php return; __HALT_COMPILER();'),(isset($phar['web.php']) ? 'web.php' : '<?php return; __HALT_COMPILER();'));
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::lib_dir(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					$phar->addFile($f->getPathname(),str_replace(Rhaco3::lib_dir(),'',$f->getPathname()));
					print(' Add: '.$f->getPathname().PHP_EOL);
				}
				$phar->compress(Phar::GZ,'phar.gz');
				print(PHP_EOL.'Created: '.$path.'.gz'.PHP_EOL);
				if(is_file($path)) unlink($path);
				exit;
			case '-search':
				if(is_file($f=getcwd().'/__settings__.php') && preg_match_all('/\n\s*[\\\\]{0,1}Rhaco3::.+?\);/ms',file_get_contents($f),$m)){foreach($m[0] as $e){eval($e);}}
				if(isset($params['repository'])) Rhaco3::repository($params['repository']);
				$q = strtolower($value);
				$list = array();
				$len = 0;
				foreach(Rhaco3::repositorys() as $rp){
					$rp = str_replace('\\','/',$rp);
					if(substr($rp,-1) != '/') $rp = $rp.'/';
					print('Read repository: '.$rp.PHP_EOL);
					try{
						$csv = file_get_contents($rp.'packages.csv');
						foreach(explode("\n",$csv) as $line){
							if(empty($q) || strpos(strtolower($line),$q) !== false){
								list($pkg,$dec) = explode(',',$line);
								$list[trim($pkg)] = trim($dec);
								if(strlen(trim($pkg)) > $len) $len = strlen(trim($pkg));
							}
						}
					}catch(\Exception $e){}
				}
				if(empty($list)){
					print(PHP_EOL.'No result'.PHP_EOL);
				}else{
					ksort($list);
					print(PHP_EOL.'Search result: '.((empty($q) ? '' : '['.$q.']')).PHP_EOL);
					foreach($list as $pkg => $dec){
						print(' '.str_pad($pkg,$len).' '.$dec.PHP_EOL);
					}
				}
				exit;
			case '-htaccess':
				$htaccess($value);
				exit;
			case '-settings':
				if(isset($params['repository'])) Rhaco3::repository($params['repository']);
				if(isset($params['import']) || isset($params['repository'])){
					$download(array(),true);
					print(PHP_EOL);
				}
				$mode = empty($value) ? 'local' : $value;
				$path = getcwd().'/__settings__.php';
				$header = 'HTTP/1.1 404 Not Found';
				if(isset($params['l'])) $header = (strtolower($params['l']) == 'fastcgi') ? 'Status: 404 Not Found' : 'Location: '.$params['l'];
				$rep = sprintf("if(\$_SERVER['SCRIPT_FILENAME']==__FILE__){header('%s');exit;}",$header);
				if(is_file($path)){
					$str = file_get_contents($path);
					if(preg_match("/if\(\\\$_SERVER\['SCRIPT_FILENAME'\]==__FILE__.+?;exit;\}/",$str,$m)) $str = str_replace($m[0].PHP_EOL,'',$str);
					$str = str_replace('<?php','<?php'.PHP_EOL.$rep,$str);
					if(!empty($mode)) $str = preg_replace("/Rhaco3::config_path\(([\"\']).+\\1\)/",'Rhaco3::config_path(\''.$mode.'\')',$str);
				}else{
					$str = '<?php'.PHP_EOL.$rep.PHP_EOL.'Rhaco3::config_path(\''.$mode.'\');'.PHP_EOL;
				}
				if(isset($params['repository']) && !preg_match("/Rhaco3::repository\(([\"\'])".preg_quote($params['repository'],'/')."\\1\)/",$str)){
					$str = $str.'Rhaco3::repository(\''.$params['repository'].'\');'.PHP_EOL;
				}
				file_put_contents($path,$str);
				print('Written: __settings__.php'.PHP_EOL);
				if(isset($params['htaccess'])) $htaccess($params['htaccess']);
				exit;
			default:
				if($cmd[0] == '-'){
					if(is_file($f=(getcwd().'/__settings__.php'))){
						require_once($f);
						if(Rhaco3::env() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::env().'.php'))) require_once($f);
					}
					$package = substr($cmd,1);
					$download(array($package),false);
					if(is_file($f=(Rhaco3::lib_dir().str_replace('.','/',$package).'/cmd.php')) || is_dir($f=(Rhaco3::lib_dir().str_replace('.','/',$package).'/cmd'))
						|| is_file($f=(Rhaco3::lib_dir().'_vendor/'.str_replace('.','/',$package).'/cmd.php')) || is_dir($f=(Rhaco3::lib_dir().'_vendor/'.str_replace('.','/',$package).'/cmd'))
					){
						$output_help_func = function($package,$f,$value){
							$help_params = array();
							$pad = 4;
							$pvalue = '';
							$src = is_file($f) ? file_get_contents($f) : (is_file($f.'/__setup__.php') ? file_get_contents($f.'/__setup__.php') : null);
							$doc = (preg_match('/\/\*\*.+?\*\//s',$src,$m)) ? trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[0]))) : '';
							if(preg_match_all('/@.+/',$doc,$as)){
								foreach($as[0] as $m){
									if(preg_match("/@(\w+)\s+([^\s]+)\s+\\$(\w+)(.*)/",$m,$p)){
										if($p[2] == '$this' || $p[2] == 'self') $p[2] = $package;
										if($p[1] == 'param' && $p[3] == 'value'){ $pvalue = sprintf('[(%s) %s]',$p[2],trim($p[4]));
										}else if($p[1] == 'param'){ $help_params[$p[3]] = array($p[2],trim($p[4])); }
									}else if(preg_match("/@(\w+)\s+\\$(\w+)(.*)/",$m,$p)){
										$help_params[$p[2]] = array(null,trim($p[3]));
									}
								}
								foreach(array_keys($help_params) as $k){if($pad < strlen($k)){ $pad = strlen($k); }}
							}
							print("\nUsage:\n");
							print("  -".$package." ".(empty($value) ? ((is_file($f.'/__setup__.php') ? '[(function name)]' : $pvalue)) : $value)."\n");
							if(!empty($help_params)){
								print("\n  Options:\n");
								foreach($help_params as $k => $v){
									print('    '.sprintf('-%s%s %s',str_pad($k,$pad),(empty($v[0]) ? '' : ' ('.$v[0].')'),trim($v[1]))."\n");
								}
							}
							$doc = trim(preg_replace('/@.+/','',$doc));
							print("\n\n  description:\n");
							print('    '.str_replace("\n","\n    ",$doc)."\n\n");
						};
						if($help){
							$output_help_func($package,$f,null);
						}else{
							$_SERVER['argv'] = array_slice($_SERVER['argv'],2);
							$_ENV['PATH_LIB_DIR'] = Rhaco3::lib_dir();
							$_ENV['PATH_EXTLIB_DIR'] = Rhaco3::lib_dir().'_extlib';
							$_ENV['PATH_VENDOR_DIR'] = Rhaco3::lib_dir().'_vendor';
							$_ENV['return'] = 0;
							list($_ENV['value'],$_ENV['params']) = array($value,$params);
							require_once(dirname($f).'/'.basename(dirname($f)).'.php');

							if(is_file($f)){
								$_ENV['return'] = require($f);
							}else if(is_dir($f)){
								try{
									if(substr($value,-1) == '?' && is_file($cmdf=$f.'/'.substr($value,0,-1).'.php')){
										$output_help_func($package,$cmdf,substr($value,0,-1));
									}else if(is_file($cmdf=$f.'/'.$value.'.php')){
										if(is_file($f.'/__setup__.php')) require($f.'/__setup__.php');
										$_ENV['return'] = require($cmdf);
										if(is_file($f.'/__teardown__.php')) require($f.'/__teardown__.php');
									}else{
										if(is_file($sf=$f.'/__setup__.php')){
											if(preg_match('/\/\*\*(.+?)\*\//ms',file_get_contents($sf),$m)){
												$println(trim(preg_replace('/@.+/','',preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[1])))),null,0);
												$println(str_repeat('-',50));
											}
											require($sf);
										}
										if(is_file($f.'/__teardown__.php')) require($f.'/__teardown__.php');
										$list = array();
										$len = 8;
										foreach(new \DirectoryIterator($f) as $f){
											if($f->isFile() && substr($f->getPathname(),-4) == '.php' && substr($f->getFilename(),0,1) != '_'){
												$package = substr($f->getFileName(),0,-4);
												$list[$package] = null;
												if($len < strlen($package)) $len = strlen($package);
												if(preg_match('/\/\*\*(.+?)\*\//ms',file_get_contents($f->getPathname()),$m)){
													list($summary) = explode("\n",trim(preg_replace('/@.+/','',preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[1])))));
													$list[$package] = $summary;
												}
											}
										}
										print(PHP_EOL.'Functions ('.substr($cmd,1).'): '.PHP_EOL);
										foreach($list as $p => $s) print('  '.str_pad($p,$len).' : '.$s.PHP_EOL);
									}
								}catch(\Exception $exception){
									$_ENV['exception'] = $exception;
									if(is_file($f.'/__exception__.php')) require($f.'/__exception__.php');
								}
								if(isset($_ENV['exception'])) throw $_ENV['exception'];
							}
							exit(($_ENV['return'] === 1) ? 0 : (int)$_ENV['return']);
						}
					}else{
						throw new \RuntimeException('Command not found `'.$package.'`');
					}
				}
		}
	}catch(\Exception $e){
		$println($e->getMessage(),false);
	}
	exit;
}
if(is_file($f=getcwd().'/__settings__.php') && preg_match_all('/\n\s*[\\\\]{0,1}Rhaco3::.+?\);/ms',file_get_contents($f),$m)){foreach($m[0] as $e){eval($e);}}
$list = array('import'=>'Download package','phar'=>'Create a phar','search'=>'Search package','htaccess'=>'Create .htaccess','settings'=>'Create __settings__.php');
$len = 8;
if(is_dir(Rhaco3::lib_dir())){
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::lib_dir(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
		$dir = dirname($f->getPathname());
		if($f->isFile() && substr($f->getPathname(),-4) == '.php' 
			&& basename($dir) === $f->getBasename('.php') && preg_match('/^[A-Z].*/',$f->getBasename('.php'))
			&& (strpos($f->getPathname(),'/_') === false || strpos($f->getPathname(),'/_vendor/') !== false)
			&& (is_file($dir.'/cmd.php') || is_dir($dir.'/cmd'))
		){
			$package = str_replace(array(Rhaco3::lib_dir(),'/'),array('','.'),$dir);
			if(strpos($package,'_vendor.') === 0) $package = substr($package,9);
			if($len < strlen($package)) $len = strlen($package);
			$doc = is_file($dir.'/cmd.php') ? file_get_contents($dir.'/cmd.php') : (is_file($dir.'/cmd/__setup__.php') ? file_get_contents($dir.'/cmd/__setup__.php') : null);
			$summary = null;
			if(preg_match('/\/\*\*.+?\*\//s',$doc,$m)) list($summary) = explode("\n",trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[0]))));				
			$list[$package] = $summary;
		}
	}
}
print(PHP_EOL.'Commands: '.PHP_EOL);
foreach($list as $p => $s) print('  '.str_pad($p,$len).' : '.$s.PHP_EOL);
exit;
