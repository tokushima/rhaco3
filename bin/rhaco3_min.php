<?php
if(!class_exists('Rhaco3')){
	/**
	 * rhaco3の環境定義クラス
	 * @author tokushima
	 */
	class Rhaco3{
		static private $env;
		static private $common_dir;
		static private $lib_dir;
		static private $rep = array('http://rhaco.org/repository/3/lib/');
		/**
		 * ライブラリのパスを設定する
		 * @param string $env 実行モード
		 * @param string $lib_dir ライブラリのディレクトリパス
		 * @param string $common_dir 設定ファイルのディレクトリ 
		 */
		static public function config_path($env=null,$lib_dir=null,$common_dir=null){
			if(self::$env === null) self::$env = (empty($env) ? 'local' : $env);
			if(self::$lib_dir === null){
				if(empty($lib_dir)) $lib_dir = getcwd().'/lib/';				
				self::$lib_dir = str_replace('\\','/',$lib_dir);
				if(substr(self::$lib_dir,-1) != '/') self::$lib_dir = self::$lib_dir.'/';
			}
			if(self::$common_dir === null){
				if(empty($common_dir)) $common_dir = getcwd().'/commons/';
				self::$common_dir = str_replace('\\','/',$common_dir);
				if(substr(self::$common_dir,-1) != '/') self::$common_dir = self::$common_dir.'/';
			}			
			define('APPENV',self::$env);
			define('LIBDIR',self::$lib_dir);
			define('EXTLIBDIR',self::$lib_dir.'_extlib/');
			define('__PEAR_DATA_DIR__',self::$lib_dir.'_extlib/data');
			set_include_path(self::$lib_dir.PATH_SEPARATOR
								.self::$lib_dir.'_vendor'.PATH_SEPARATOR
								.self::$lib_dir.'_extlib'.PATH_SEPARATOR
								.get_include_path()
			);
		}
		/**
		 * リポジトリの場所を指定する
		 * @param string $rep リポジトリのパス
		 */
		static public function repository($rep){
			array_unshift(self::$rep,$rep);
		}
		/**
		 * リポジトリパスの一覧を返す
		 * @return string[]
		 */
		static public function repositorys(){
			return self::$rep;
		}
		/**
		 * ライブラリのディレクトリ
		 * @return string
		 */
		static public function lib_dir(){
			if(self::$lib_dir === null) self::config_path();
			return self::$lib_dir;
		}
		/**
		 * 設定ファイルのディレクトリ
		 * @return string
		 */
		static public function common_dir(){
			if(self::$common_dir === null) self::config_path();
			return self::$common_dir;
		}
		/**
		 * 実行環境を設定/取得
		 * @return string モード
		 */
		static public function env(){
			if(self::$env === null) self::config_path();
			return self::$env;
		}
	}
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
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(getcwd().'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::env() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::env().'.php'))) require_once($f);
	}
	return;
}
