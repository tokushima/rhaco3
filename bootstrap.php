<?php
/**
 * rhaco3の環境定義クラス
 * @author tokushima
 */
class Rhaco3{
	/**
	 * ライブラリのパスを設定する
	 * @param string $mode 実行モード
	 * @param string $lib_dir ライブラリのディレクトリパス
	 * @param string $common_dir 設定ファイルのディレクトリ
	 */
	static public function config_path($mode=null,$lib_dir=null,$common_dir=null){
		if(!defined('APPMODE')){
			$mode = (empty($mode) ? 'local' : $mode);
				
			if(empty($lib_dir)) $lib_dir = getcwd().'/lib/';
			$lib_dir = str_replace('\\','/',$lib_dir);
			if(substr($lib_dir,-1) != '/') $lib_dir = $lib_dir.'/';

			if(empty($common_dir)) $common_dir = getcwd().'/commons/';
			$common_dir = str_replace('\\','/',$common_dir);
			if(substr($common_dir,-1) != '/') $common_dir = $common_dir.'/';
				
			define('APPMODE',$mode);
			define('COMMONDIR',$common_dir);
			define('LIBDIR',$lib_dir);
			define('EXTLIBDIR',$lib_dir.'_extlib/');
			define('__PEAR_DATA_DIR__',$lib_dir.'_extlib/data');
			if(strpos(get_include_path(),$lib_dir) === false){
				set_include_path($lib_dir.PATH_SEPARATOR
						.$lib_dir.'_vendor'.PATH_SEPARATOR
						.$lib_dir.'_extlib'.PATH_SEPARATOR
						.get_include_path()
				);
			}
		}
	}
}
if(($p=realpath('./lib')) !== false && strpos(get_include_path(),$p) === false){
	set_include_path($p
			.PATH_SEPARATOR.$p.'/_vendor'
			.PATH_SEPARATOR.get_include_path()
	);
}
spl_autoload_register(function($c){
	$cp = str_replace('\\','/',(($c[0] == '\\') ? substr($c,1) : $c));
	foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
		if(!empty($p) && ($r = realpath($p)) !== false){
			if(is_file($f=($r.'/'.$cp.'.php')) 
				|| is_file($f=($r.'/'.$cp.'/'.basename($cp).'.php'))
				|| is_file($f=($r.'/'.str_replace('_','/',$cp).'.php'))
				|| is_file($f=($r.'/'.implode('/',array_slice(explode('_',$cp),0,-1)).'.php'))
			){
				require_once($f);
				
				if(class_exists($c,false) || interface_exists($c,false)){
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
if(ini_get('date.timezone') == ''){
	date_default_timezone_set('Asia/Tokyo');
}
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(getcwd().'/__settings__.php'))){
		require_once($f);
		
		if(!defined('APPMODE')) define('APPMODE','local'); 
		if(!defined('COMMONDIR')) define('_COMMONDIR',getcwd().'/commons');
		if(is_file($f=(constant('COMMONDIR').'/'.constant('APPMODE').'.php'))){
			require_once($f);
		}
	}
	return;
}

