<?php
/**
 * rhaco3の環境定義クラス
 * @author tokushima
 */
class Rhaco3{
	static private $libs;
	static private $rep = array('http://rhaco.org/repository/3/lib/');
	/**
	 * ライブラリのパスを設定する
	 * @param string $libs_dir ライブラリのディレクトリパス
	 */
	static public function config_path($libs_dir=null){
		if(self::$libs === null) self::$libs = $libs_dir.((substr($libs_dir,-1)=='/')?'':'/');
	}
	/**
	 * リポジトリの場所を指定する
	 * @param string $rep
	 */
	static public function repository($rep){
		array_unshift(self::$rep,$rep);
	}
	static public function repositorys(){
		return self::$rep;
	}
	static public function libs($p=null){
		if(self::$libs == null){
			self::$libs = __DIR__.'/libs/';
			set_include_path(self::$libs.'_extlibs'.PATH_SEPARATOR.get_include_path());
			define('__PEAR_DATA_DIR__',self::$libs.'_extlibs/data');
		}
		return self::$libs.$p;
	}
}
ini_set('display_errors','On');
ini_set('html_errors','Off');
set_error_handler(function($n,$s,$f,$l){throw new ErrorException($s,0,$n,$f,$l);});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
spl_autoload_register(function($c){
	if($c[0] == '\\') $c = substr($c,1);
	$p = str_replace('\\','/',$c);
	if(ctype_upper($p[0]) || preg_match('/^(.+)\/([A-Z][\w_]*)$/',$p,$m)){
		foreach(array('','_vendors/') as $q){
			if(is_file($f=Rhaco3::libs($q.$p.'.php'))){require_once($f);
			}else if(isset($m[2]) && is_file($f=Rhaco3::libs($q.$p.'/'.$m[2].'.php'))){require_once($f);}
		}
	}
	if(!class_exists($c,false) && !interface_exists($c,false)){
		$e = Rhaco3::libs('_extlibs/');
		if(is_file($f=$e.$p.'.php')){require_once($f);
		}else if(is_file($f=$e.str_replace('_','/',$c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.class.php')){require_once($f);
		}else{$f=$c;}
	}
	if(class_exists($c,false) || interface_exists($c,false)){
		if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
		if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
		return true;
	}
	return false;
},true,false);
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=__DIR__.'/__settings__.php')) require_once($f);
	return;
}