<?php
namespace org\rhaco\lang;
/**
 * エラーを抑制する
 * @author tokushima
 */
class LooseSyntax{
	static private $set_error_handler = false;
	static public function error_handler($errno,$errstr,$errfile,$errline){
		switch($errno){
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				throw new \ErrorException($errstr,0,$errno,$errfile,$errline);
			default:
				return true;
		}
	}
	/**
	 * E_*_ERROR以外はエラーとせずに実行する
	 * @param callback $func
	 */
	static public function call($func){
		$args = func_get_args();
		$func = array_shift($args);
		self::begin();
		$result = call_user_func_array($func,$args);
		self::end();
		return $result;
	}
	/**
	 * E_*_ERROR 以外はエラーとしない
	 */
	static public function begin(){
		if(self::$set_error_handler === false){
			set_error_handler(array(__CLASS__,'error_handler'),E_ALL);
		}
		self::$set_error_handler = true;
	}
	/**
	 * エラー制御を元に戻す
	 */
	static public function end(){
		if(self::$set_error_handler){
			restore_error_handler();
		}
		self::$set_error_handler = false;
	}
}