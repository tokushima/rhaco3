<?php
spl_autoload_register(function($c){
	$cp = str_replace('\\','//',(($c[0] == '\\') ? substr($c,1) : $c));
	foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
		if(!empty($p) && ($r = realpath($p)) !== false){
			if(is_file($f=($r.'/'.$cp.'.php')) 
				|| is_file($f=($r.'/'.$cp.'/'.basename($cp).'.php'))
				|| is_file($f=($r.'/'.str_replace('_','/',$cp).'.php'))
				|| is_file($f=($r.'/'.implode('/',array_slice(explode('_',$cp),0,-1)).'.php'))
			){
				require_once($f);
				
				if(class_exists($c,false) || interface_exists($c,false)){
					if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
					if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
					return true;
				}
			}
		}
	}
	return false;
},true,false);
ini_set('display_errors','On');
ini_set('html_errors','Off');
set_error_handler(function($n,$s,$f,$l){
	throw new \ErrorException($s,0,$n,$f,$l);
});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}