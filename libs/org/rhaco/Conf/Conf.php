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
	 * @param string $class
	 * @param string $key
	 * @param mixed $default
	 */
	static public function get($key,$default=null){
		list(,$d) = debug_backtrace(false);
		$class = str_replace("\\",'.',$d['class']);
		if($class[0] === '.') $class = substr($class,1);
		if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)) $class = $m[1];
		return self::exists($class,$key) ? self::$value[$class][$key] : $default;
	}
	/**
	 * 定義情報を配列で取得する
	 * @param string $class
	 * @param string $key
	 * @param mixed $option 
	 */
	static public function get_array($key,$option=null){
		list(,$d) = debug_backtrace(false);
		$class = str_replace("\\",'.',$d['class']);
		if($class[0] === '.') $class = substr($class,1);
		if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)) $class = $m[1];
		$r = self::exists($class,$key) ? self::$value[$class][$key] : null;
		if(!isset($r)) $r = array();
		if(!is_array($r)) $r = array($r);
		if(isset($option)){
			if(is_array($option)){
				$names_cnt = sizeof($option);
				$result_cnt = sizeof($r);
				$chunk = array();
				for($i=0;$i<$result_cnt;$i+=$names_cnt){
					$c = array();
					foreach($option as $k => $name) $c[$name] = isset($r[$i+$k]) ? $r[$i+$k] : null;
					$chunk[] = $c;
				}
				$r = $chunk;
			}else if(is_int($option)){
				$num = $option-sizeof($r);
				if($num > 0) $r = array_merge($r,array_fill(0,$num,null));
			}
		}
		return $r;
	}
	/**
	 * Configの一覧を取得する
	 * @return array
	 */
	static public function all(){
		$conf_get = function($filename){
			$src = file_get_contents($filename);
			$gets = array();
			if(preg_match_all('/[^\w]Conf::'.'(get|get_array)\(([\"\'])(.+?)\\2/',$src,$m)){
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
		foreach(\org\rhaco\Man::libs() as $p => $lib){
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
}