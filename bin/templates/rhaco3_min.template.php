<?php
##RHACO3_PHP##
define('LIBDIR',\Rhaco3::lib_dir());
##AUTOLOAD_PHP##
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(__DIR__.'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::mode() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::mode().'.php'))) require_once($f);
	}
	return;
}
