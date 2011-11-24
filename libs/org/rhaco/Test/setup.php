<?php
/**
 * Test a running
 * @param string $value package path
 * @param string $m method name
 * @param string $b block name
 */
$error_print = function ($msg,$color='1;31'){
	print(((php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg).PHP_EOL);
};
$verify_format = function($class_name,$m=null,$b=null){
	if(php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN'){
		$f = ' .. '.$class_name.' ';
		$l = strlen($f)*2;
		$throw = null;
		print("\033[".$l.'D'.str_repeat(' ',$l)."\033[".$l.'D');
		print($f);
		try{
			\org\rhaco\Test::run($class_name,$m,$b);
		}catch(\Exception $e){
			$throw = $e;
		}
		print("\033[".$l.'D'.str_repeat(' ',$l)."\033[".$l.'D');
		if(isset($throw)) throw $throw;
	}else{
		\org\rhaco\Test::run($class_name,$m,$b);
	}
};
\org\rhaco\Test::start_time();
if(isset($_ENV['params']['mem']) && $_ENV['params']['mem'] != '') ini_set('memory_limit',$_ENV['params']['mem']);
if(isset($_ENV['params']['value'])){
	try{
		$verify_format($_ENV['params']['value']
			,(isset($_ENV['params']['m']) ? $_ENV['params']['m'] : null)
			,(isset($_ENV['params']['b']) ? $_ENV['params']['b'] : null)
		);
	}catch(\Exception $e){
		$error_print($_ENV['params']['value'].': '.$e->getMessage());
	}
	print(new \org\rhaco\Test());
}else{
	$dup = array();	
	$exceptions = array();
	list($entry_path,$tests_path,$libs_path) = \org\rhaco\Test::search_path();
	
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($libs_path,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS),RecursiveIteratorIterator::SELF_FIRST) as $p){
		if(substr($p->getFilename(),-4) == '.php' && strpos($p->getPathname(),'/.') === false && strpos($p->getPathname(),'/_') === false){
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
	if(empty($exceptions)){
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
		foreach($exceptions as $k => $e) $error_print($k.': '.$e);
	}
	print(new \org\rhaco\Test());
}