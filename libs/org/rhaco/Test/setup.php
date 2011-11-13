<?php
/**
 * Test a running
 * @param string $value package path
 * @param string $m method name
 * @param string $b block name
 */
$fcolor = function ($msg,$color="30"){
	return (php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg;
};
$verify_format = function($class_name,$m=null,$b=null){
	if(php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN'){
		$f = ' .. '.$class_name;
		$l = strlen($f)*2;
		print("\033[".$l.'D'.str_repeat(' ',$l)."\033[".$l.'D');
		print($f);
		\org\rhaco\Test::run($class_name,$m,$b);
		print("\033[".$l.'D'.str_repeat(' ',$l)."\033[".$l.'D');
	}else{
		\org\rhaco\Test::run($class_name,$m,$b);
	}
};
\org\rhaco\Test::start_time();
if(isset($_ENV['params']['mem']) && $_ENV['params']['mem'] != '') ini_set('memory_limit',$_ENV['params']['mem']);
if(isset($_ENV['params']['value'])){
	$verify_format($_ENV['params']['value']
		,(isset($_ENV['params']['m']) ? $_ENV['params']['m'] : null)
		,(isset($_ENV['params']['b']) ? $_ENV['params']['b'] : null)
	);
	print(new \org\rhaco\Test());
}else{
	$dup = array();
	$dirs = function($dir) use(&$dirs){
		$base = $dir;
		if(substr($dir,-1) == '/') $dir = substr($dir,0,-1);
		if(substr($base,-1) != '/') $base = $base.'/';
		$list = array();
		if(is_file($dir)){
			$list[str_replace($base,'',$dir)] = $dir;
		}else{
			if($h = opendir($dir)){
				while($p = readdir($h)){
					if($p != '.' && $p != '..'){
						$s = sprintf('%s/%s',$dir,$p);
						if(is_dir($s)){
							$r = $dirs($s,$base);
							$list = array_merge($list,$r);
						}else{
							$list[str_replace($base,'',$s)] = $s;
						}
					}
				}
				closedir($h);
			}
		}
		return $list;
	};
	
	$exceptions = array();
	list($entry_path,$tests_path,$libs_path) = \org\rhaco\Test::search_path();
	
	foreach($dirs($libs_path) as $p){
		if(substr($p,-4) == '.php'){
			$r = str_replace("\\",'/',$p);
			$n = substr(basename($r),0,-4);
			$b = true;
	
			if(ctype_upper($n[0])){
				foreach($dup as $d){
					if(strpos($r,$d) === 0){
						$b = false;
						break;
					}
				}
				if($b){
					if(preg_match("/^(.*)\/(\w+)\/(\w+)\.php$/",$r,$m) && $m[2] == $m[3] && !preg_match('/[A-Z]/',str_replace($libs_path,'',$m[1]))){
						$dir = dirname($r);
						$dup[] = $dir.'/';
						$class_name = "\\".str_replace(array($libs_path,'/'),array('',"\\"),$dir);
						try{
							$verify_format($class_name);
						}catch(\Exception $e){
							$exceptions[$class_name] = $e->getMessage();
						}
					}else if(!preg_match('/[A-Z]/',str_replace($libs_path,'',dirname($r)))){
						$class_name = "\\".str_replace(array($libs_path,'/'),array('',"\\"),substr($r,0,-4));
						try{
							$verify_format($class_name);
						}catch(\Exception $e){
							$exceptions[$class_name] = $e->getMessage();
						}
					}
				}
			}
		}
	}
	if(!empty($exceptions)){
		foreach(new \RecursiveDirectoryIterator($entry_path,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS) as $e){
			if(substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false){
				$src = file_get_contents($e->getFilename());
				if((strpos($src,"\\org\\rhaco\\Flow") !== false && strpos($src,'->output(') !== false)){
					try{
						$verify_format($e->getPathname());
					}catch(\Exception $exception){
						$exceptions[$e->getFilename()] = $exception->getMessage();
					}
				}
			}
		}
	}
	if(!empty($exceptions)){
		foreach(new \RecursiveDirectoryIterator($tests_path,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS) as $e){
			if(substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false){
				try{
					$verify_format($e->getPathname());
				}catch(\Exception $exception){
					$exceptions[$e->getFilename()] = $exception->getMessage();
				}
			}
		}
	}
	if(!empty($exceptions)){
		foreach($exceptions as $k => $e) $fcolor($k.': '.$e.PHP_EOL);
	}
	print(new \org\rhaco\Test());
}