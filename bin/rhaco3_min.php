<?php
if(!class_exists('Rhaco3')){
/**
 * rhaco3の環境定義クラス
 * @author tokushima
 */
class Rhaco3{
	static private $mode;
	static private $common_dir;
	static private $libs;
	static private $rep = array('http://rhaco.org/repository/3/lib/');
	/**
	 * ライブラリのパスを設定する
	 * @param string $libs_dir ライブラリのディレクトリパス
	 * @param string $mode 実行モード
	 * @param string $common_dir 設定ファイルのディレクトリ 
	 */
	static public function config_path($libs_dir=null,$mode=null,$common_dir=null){
		self::libs($libs_dir);
		self::mode($mode);
		self::common_dir($common_dir);
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
	 * ライブラリのパスを設定/取得
	 * @param string $p ライブラリのパス
	 * @return string ライブラリのパス
	 */
	static public function libs($p=null){
		if(self::$libs === null){
			self::$libs = __DIR__.'/libs/';
			set_include_path(self::$libs.'_extlibs'.PATH_SEPARATOR.get_include_path());
			define('__PEAR_DATA_DIR__',self::$libs.'_extlibs/data');
		}
		return self::$libs.$p;
	}
	/**
	 * 実行モードを設定/取得
	 * @param string $mode モード
	 * @return string モード
	 */
	static public function mode($mode='noname'){
		if(self::$mode === null) self::$mode = $mode;
		return self::$mode;
	}
	/**
	 * 設定ファイルのディレクトリを設定/取得
	 * @param string $common_dir 設定ファイルのディレクトリ
	 * @return string モード
	 */
	static public function common_dir($dir=null){
		if(self::$common_dir === null){
			$dir = str_replace("\\",'/',(empty($dir)) ? __DIR__.'/commons/' : $dir);
			if(substr($dir,-1) != '/') $dir = $dir.'/';
			self::$common_dir = $dir;
		}
		return self::$common_dir;
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
			if(is_file($f=Rhaco3::libs($q.$p.'.php'))){require_once($f);break;
			}else if(isset($m[2]) && is_file($f=Rhaco3::libs($q.$p.'/'.$m[2].'.php'))){require_once($f);break;}
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
	if(is_file($f=(__DIR__.'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::mode() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::mode().'.php'))) require_once($f);
	}
	return;
}
}