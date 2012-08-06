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
		 * @param string $lib_dir ライブラリのディレクトリパス
		 * @param string $common_dir 設定ファイルのディレクトリ 
		 */
		static public function config_path($mode=null,$lib_dir=null,$common_dir=null){
			if(self::$mode === null) self::$mode = (empty($mode) ? 'local' : $mode);
			if(self::$lib_dir === null){
				if(empty($lib_dir)) $lib_dir = getcwd().'/lib/';				
				self::$lib_dir = str_replace('\\','/',$lib_dir);
				if(substr(self::$lib_dir,-1) != '/') self::$lib_dir = self::$lib_dir.'/';
				set_include_path(self::$lib_dir.'_extlib'.PATH_SEPARATOR.get_include_path());
				define('PEAR_DATA_DIR',self::$lib_dir.'_extlib/data');
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