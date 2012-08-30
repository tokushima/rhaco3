<?php
##RHACO3_PHP##
##AUTOLOAD_PHP##
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(getcwd().'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::env() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::env().'.php'))) require_once($f);
	}
	return;
}
