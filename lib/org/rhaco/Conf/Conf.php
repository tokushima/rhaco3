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
	 * @param string $class
	 * @param string $key
	 * @param mixed $value
	 */
	static public function set($class,$key,$value){
		$class = str_replace("\\",'.',$class);
		if($class[0] === '.') $class = substr($class,1);
		if(func_num_args() > 3){
			$value = func_get_args();
			array_shift($value);
			array_shift($value);
		}
		if(!isset(self::$value[$class]) || !array_key_exists($key,self::$value[$class])) self::$value[$class][$key] = $value;
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
	 * Configの一覧を取得する
	 * @return array
	 */
	static public function all(){
		$conf_get = function($filename){
			$src = file_get_contents($filename);
			$gets = array();
			if(preg_match_all('/[^\w]Conf::'.'(get)\(([\"\'])(.+?)\\2/',$src,$m)){
				foreach($m[3] as $k => $n){
					if(!isset($gets[$n])) $gets[$n] = array('string','');
				}
			}			
			if(preg_match_all("/@conf\s+([^\s]+)\s+\\$(\w+)(.*)/",$src,$m)){
				foreach($m[0] as $k => $v) $docs[trim($m[2][$k])] = array($m[1][$k],trim($m[3][$k]));
			}
			if(preg_match_all("/@conf\s+\\$(\w+)(.*)/",$src,$m)){
				foreach($m[0] as $k => $v) $docs[trim($m[1][$k])] = array('string',trim($m[2][$k]));
			}
			foreach($gets as $n => $v){
				if(isset($docs[$n])) $gets[$n] = $docs[$n];
			}
			return $gets;
		};
		$gets = array();
		foreach(\org\rhaco\Man::classes() as $p => $lib){
			if($lib['dir']){
				$ret = array();
				foreach(new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator(
						dirname($lib['filename'])
						,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS)
						,\RecursiveIteratorIterator::SELF_FIRST
				) as $e){
					if(substr($e->getPathname(),-4) == '.php'){
						$ret = array_merge($ret,$conf_get($e->getPathname()));
					}
				}
				if(!empty($ret)) $gets[$p] = $ret;
			}else{
				$ret = $conf_get($lib['filename']);
				if(!empty($ret)) $gets[$p] = $ret;
			}
		}
		return $gets;
	}
	/**
	 * アプリケーションの動作環境
	 * @return string
	 */
	static public function appenv(){
		return defined('APPENV') ? constant('APPENV') : null;
	}
	/**
	 * ライブラリの配置してあるパス
	 * @return string
	 */
	static public function libdir(){
		return defined('LIBDIR') ? constant('LIBDIR') : null;
	}
}