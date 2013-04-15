<?php
namespace org\rhaco;
/**
 * 定義情報を格納するクラス
 * @author tokushima
 */
class Conf{
	static private $value = array();
	/**
	 * 定義情報をセットする
	 * @param string|array $class
	 * @param string $key
	 * @param mixed $value
	 */
	static public function set($class,$key=null,$value=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				foreach($v as $k => $value){
					if(!isset(self::$value[$c]) || !array_key_exists($k,self::$value[$c])){
						self::$value[$c][$k] = $value;
					}
				}
			}
		}else if(!empty($key)){
			$class = str_replace("\\",'.',$class);
			if($class[0] === '.') $class = substr($class,1);
			if(func_num_args() > 3){
				$value = func_get_args();
				array_shift($value);
				array_shift($value);
			}
			if(!isset(self::$value[$class]) || !array_key_exists($key,self::$value[$class])) self::$value[$class][$key] = $value;
		}
	}
	/**
	 * 定義されているか
	 * @param string $class
	 * @param string $key
	 * @return boolean
	 */
	static public function exists($class,$key){
		return (isset(self::$value[$class]) && array_key_exists($key,self::$value[$class]));
	}
	/**
	 * 定義情報を取得する
	 * @param string $key
	 * @param mixed $default
	 */
	static public function get($key,$default=null,$return_vars=null){
		if(strpos($key,'@') === false){
			list(,$d) = debug_backtrace(false);
			$class = str_replace('\\','.',$d['class']);
			if($class[0] === '.') $class = substr($class,1);
			if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)) $class = $m[1];
		}else{
			list($class,$key) = explode('@',$key,2);
		}
		$result = self::exists($class,$key) ? self::$value[$class][$key] : $default;
		if(is_array($return_vars)){
			if(empty($return_vars) && !is_array($result)) return array($result);
			$result_vars = array();
			foreach($return_vars as $var_name) $result_vars[] = isset($result[$var_name]) ? $result[$var_name] : null;
			return $result_vars;
		}
		return $result;
	}
	/**
	 * アプリケーションの動作環境
	 * @return string
	 */
	static public function appmode(){
		return defined('APPMODE') ? constant('APPMODE') : null;
	}
}