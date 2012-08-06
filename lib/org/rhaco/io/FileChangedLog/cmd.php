<?php
/**
 * ファイルを監視し更新時に通知する
 * @param string $value 
 * @param null $error
 */
if(empty($value)){
	if(isset($params['error'])){
		$error_log = ini_get('error_log');
		if(empty($error_log)) throw new \RuntimeException('undefined error_log');
		$value = $error_log;
	}else{
		$value = getcwd();
	}
}
$self = new \org\rhaco\io\FileChangedLog($value);
