<?php
spl_autoload_register(function($c){
	$cp = str_replace('\\','//',(($c[0] == '\\') ? substr($c,1) : $c));
	foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
		if(($r = realpath($p)) !== false){
			if(is_file($f=($r.'/'.$cp.'.php')) || is_file($f=($r.'/'.$cp.'/'.basename($cp).'.php'))){
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