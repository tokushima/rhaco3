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
		$f = ' .. '.$class_name.(isset($m) ? '::'.$m : '').(isset($b) ? '#'.$b : '').' ';
		$l = strlen($f)*2;
		$throw = null;
		print("\033[".$l.'D'."\033[0K");
		print($f);
		try{
			\org\rhaco\Test::run($class_name,$m,$b,true);
		}catch(\Exception $e){
			$throw = $e;
		}
		print("\033[".$l.'D'."\033[0K");
		if(isset($throw)) throw $throw;
	}else{
		\org\rhaco\Test::run($class_name,$m,$b,true);
	}
};

$ini_error_log = ini_get('error_log');
$ini_error_log_start_size = (empty($ini_error_log) || !is_file($ini_error_log)) ? 0 : filesize($ini_error_log);
\org\rhaco\Test::start_time();
if(isset($params['mem']) && $params['mem'] != '') ini_set('memory_limit',$params['mem']);
if(isset($value)){
	try{
		$verify_format($value
			,(isset($params['m']) ? $params['m'] : null)
			,(isset($params['b']) ? $params['b'] : null)
		);
	}catch(\Exception $e){
		$error_print($value.': '.$e->getMessage());
	}
}else{
	$dup = array();	
	$exceptions = array();
	list($entry_path,$tests_path,$lib_path) = \org\rhaco\Test::search_path();
	
	if(is_dir($lib_path)){
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lib_path,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS),RecursiveIteratorIterator::SELF_FIRST) as $p){
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
						if(preg_match("/^(.*)\/(\w+)\/(\w+)\.php$/",$r,$m) && $m[2] == $m[3] && !preg_match('/[A-Z]/',str_replace($lib_path,'',$m[1]))){
							$dir = dirname($r);
							$dup[] = $dir.'/';
							$class_name = "\\".str_replace(array($lib_path,'/'),array('',"\\"),$dir);
							try{
								$verify_format($class_name);
							}catch(\Exception $e){
								$exceptions[$class_name] = $e->getMessage();
							}
						}else if(!preg_match('/[A-Z]/',str_replace($lib_path,'',dirname($r)))){
							$class_name = "\\".str_replace(array($lib_path,'/'),array('',"\\"),substr($r,0,-4));
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
	}
	if(empty($exceptions)){
		if(is_dir($entry_path)){
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
		if(is_dir($tests_path)){
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
	}
	if(!empty($exceptions)){
		foreach($exceptions as $k => $e) $error_print($k.': '.$e);
	}
}
$ini_error_log_end_size = (empty($ini_error_log) || !is_file($ini_error_log)) ? 0 : filesize($ini_error_log);
$error_msg = ($ini_error_log_end_size != $ini_error_log_start_size) ? file_get_contents($ini_error_log,false,null,$ini_error_log_start_size) : null;
if(isset($params['xml'])){
	if(!empty($params['xml'])){
		if(!is_dir(dirname($params['xml']))) \org\rhaco\io\File::mkdir(dirname($params['xml']),0777);
		file_put_contents($params['xml'],\org\rhaco\Test::xml($value,$error_msg)->get('UTF-8'));
	}else{
		print(\org\rhaco\Test::xml($value,$error_msg)->get('UTF-8'));
	}
}else{
	print(new \org\rhaco\Test());
	if(!empty($error_msg)){
		$error_print(PHP_EOL.'PHP Error ('.$ini_error_log.'):');
		$error_print($error_msg);
	}
}
