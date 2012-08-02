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
			if(self::$mode === null && $mode !== null) self::$mode = $mode;
			if(self::$common_dir === null && $common_dir !== null){
				self::$common_dir = str_replace('\\','/',$common_dir);
				if(substr(self::$common_dir,-1) != '/') self::$common_dir = self::$common_dir.'/';
			}
			if(self::$lib_dir === null && $libs_dir !== null){
				self::$lib_dir = str_replace('\\','/',$libs_dir);
				if(substr(self::$lib_dir,-1) != '/') self::$lib_dir = self::$lib_dir.'/';
				set_include_path(self::$lib_dir.'_extlibs'.PATH_SEPARATOR.get_include_path());
				define('__PEAR_DATA_DIR__',self::$lib_dir.'_extlibs/data');
			}
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
			if(self::$lib_dir === null){
				self::$lib_dir = getcwd().'/libs/';
				set_include_path(self::$lib_dir.'_extlibs'.PATH_SEPARATOR.get_include_path());
				define('__PEAR_DATA_DIR__',self::$lib_dir.'_extlibs/data');
			}
			return self::$lib_dir;
		}
		/**
		 * 設定ファイルのディレクトリ
		 * @return string
		 */
		static public function common_dir(){
			if(self::$common_dir === null) self::$common_dir = getcwd().'/commons/';
			return self::$common_dir;
		}
		/**
		 * 実行モードを設定/取得
		 * @return string モード
		 */
		static public function mode(){
			if(self::$mode === null) self::$mode = 'local';
			return self::$mode;
		}
	}
}