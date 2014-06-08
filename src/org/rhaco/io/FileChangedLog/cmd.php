<?php
/**
 * ファイルを監視し更新時に通知する
 * @param boolean $error
 */
if(empty($arg)){
	if(isset($params['error'])){
		$error_log = ini_get('error_log');
		if(empty($error_log)) throw new \RuntimeException('undefined error_log');
		$arg = $error_log;
	}else{
		$arg = getcwd();
	}
}
$self = new \org\rhaco\io\FileChangedLog($arg);
