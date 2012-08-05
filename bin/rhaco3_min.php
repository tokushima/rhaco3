<?php
if(!class_exists('Rhaco3')){
	/**
	 * rhaco3の環境定義クラス
	 * @author tokushima
	 */
	class Rhaco3{
		static private $mode;
		static private $common_dir;
		static private $lib_dir;
		static private $rep = array('http://rhaco.org/repository/3/lib/');
		/**
		 * ライブラリのパスを設定する
		 * @param string $mode 実行モード
		 * @param string $libs_dir ライブラリのディレクトリパス
		 * @param string $common_dir 設定ファイルのディレクトリ 
		 */
		static public function config_path($mode=null,$libs_dir=null,$common_dir=null){
			if(self::$mode === null) self::$mode = (empty($mode) ? 'local' : $mode);
			if(self::$lib_dir === null){
				if(empty($libs_dir)) $libs_dir = getcwd().'/libs/';				
				self::$lib_dir = str_replace('\\','/',$libs_dir);
				if(substr(self::$lib_dir,-1) != '/') self::$lib_dir = self::$lib_dir.'/';
				set_include_path(self::$lib_dir.'_extlibs'.PATH_SEPARATOR.get_include_path());
				define('PEAR_DATA_DIR',self::$lib_dir.'_extlibs/data');
			}
			if(self::$common_dir === null){
				if(empty($common_dir)) $common_dir = getcwd().'/commons/';
				self::$common_dir = str_replace('\\','/',$common_dir);
				if(substr(self::$common_dir,-1) != '/') self::$common_dir = self::$common_dir.'/';
			}			
			define('APP_MODE',self::$mode);
			define('LIB_DIR',self::$lib_dir);
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
		 * 実行モードを設定/取得
		 * @return string モード
		 */
		static public function mode(){
			if(self::$mode === null) self::config_path();
			return self::$mode;
		}
	}
}
spl_autoload_register(function($c){
	$libdir = constant('LIB_DIR');
	if(substr($libdir,-1) != '/') $libdir = $libdir.'/';
	if($c[0] == '\\') $c = substr($c,1);
	$p = str_replace('\\','/',$c);
	if(ctype_upper($p[0]) || preg_match('/^(.+)\/([A-Z][\w_]*)$/',$p,$m)){
		foreach(array('','_vendors/') as $q){
			if(is_file($f=($libdir.$q.$p.'.php'))){require_once($f);break;
			}else if(isset($m[2]) && is_file($f=($libdir.$q.$p.'/'.$m[2].'.php'))){require_once($f);break;}
		}
	}
	if(!class_exists($c,false) && !interface_exists($c,false)){
		$e = $libdir.'_extlibs/';
		if(is_file($f=$e.$p.'.php')){require_once($f);
		}else if(is_file($f=$e.str_replace('_','/',$c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.class.php')){require_once($f);
		}else if((strpos($c,'_')!==false)&&(is_file($f=$e.implode('/',array_slice(explode('_',$c),0,-1)).'.php'))){require_once($f);
		}else{$f=$c;}
	}
	if(class_exists($c,false) || interface_exists($c,false)){
		if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
		if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
		return true;
	}
	return false;
},true,false);
ini_set('display_errors','On');
ini_set('html_errors','Off');
set_error_handler(function($n,$s,$f,$l){throw new ErrorException($s,0,$n,$f,$l);});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(__DIR__.'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::mode() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::mode().'.php'))) require_once($f);
	}
	return;
}
