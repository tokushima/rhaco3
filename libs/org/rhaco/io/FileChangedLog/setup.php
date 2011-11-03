<?php
/**
 * ファイルが監視し更新時に通知する
 * @param string $value 
 * @param null $error
 */
if(empty($_ENV['params']['value'])){
	if(isset($_ENV['params']['error'])){
		$error_log = ini_get('error_log');
		if(empty($error_log)) throw new \RuntimeException('undefined error_log');
		$target = $error_log;
	}else{
		$target = getcwd();
	}
}else{
	$target = $_ENV['params']['value'];
}
$self = new \org\rhaco\io\FileChangedLog($target);
