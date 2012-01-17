<?php
/**
 * ファイルが監視し更新時に通知する
 * @param string $value 
 * @param null $error
 */
list($target,$params) = array(isset($_ENV['value'])?$_ENV['value']:null,isset($_ENV['params'])?$_ENV['params']:array());
if(empty($target)){
	if(isset($params['error'])){
		$error_log = ini_get('error_log');
		if(empty($error_log)) throw new \RuntimeException('undefined error_log');
		$target = $error_log;
	}else{
		$target = getcwd();
	}
}
$self = new \org\rhaco\io\FileChangedLog($target);
