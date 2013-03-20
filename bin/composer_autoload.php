<?php

ini_set('display_errors','On');
ini_set('html_errors','Off');

set_error_handler(function($n,$s,$f,$l){
	throw new \ErrorException($s,0,$n,$f,$l);
});

if(ini_get('date.timezone') == ''){
	date_default_timezone_set('Asia/Tokyo');
}
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
if(is_file($f=(getcwd().'/__settings__.php'))){
	require_once($f);

	if(!defined('APPMODE')) define('APPMODE','local');
	if(!defined('COMMONDIR')) define('_COMMONDIR',getcwd().'/commons');
	if(is_file($f=(constant('COMMONDIR').'/'.constant('APPMODE').'.php'))){
		require_once($f);
	}
}
return;

