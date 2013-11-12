<?php
namespace org\rhaco\net;
/**
 * query文字列を作成する
 * @author tokushima
 */
class Query{
	/**
	 * query文字列に変換する
	 * @param mixed $var query文字列化する変数
	 * @param string $name ベースとなる名前
	 * @param boolean $null nullの値を表現するか
	 * @param boolean $array 配列を表現するか
	 * @return string
	 */
	static public function get($var,$name=null,$null=true,$array=true){
		$result = "";
		foreach(self::expand_vars($vars,$var,$name,$array) as $v){
			if(($null || ($v[1] !== null && $v[1] !== '')) && is_string($v[1])) $result .= $v[0].'='.urlencode($v[1]).'&';
		}
		return (empty($result)) ? $result : substr($result,0,-1);
	}
	/**
	 * 
	 * @param mixed{} $vars マージ元の値
	 * @param mixed $value 展開する値
	 * @param string $name ベースとなる名前
	 * @param boolean $array 配列を表現するか
	 */
	static public function expand_vars(&$vars,$value,$name=null,$array=true){
		if(!is_array($vars)) $vars = array();
		if($value instanceof \org\rhaco\io\File){
			$vars[] = array($name,$value);
		}else{
			$ar = array();
			if(is_object($value)){
				if($value instanceof \Traversable){
					foreach($value as $k => $v) $ar[$k] = $v;
				}else{
					foreach(get_object_vars($value) as $k => $v) $ar[$k] = $v;
				}
				$value = $ar;
			}
			if(is_array($value)){
				foreach($value as $k => $v){
					self::expand_vars($vars,$v,(isset($name) ? $name.(($array) ? '['.$k.']' : '') : $k),$array);
				}
			}else if(!is_numeric($name)){
				if(is_bool($value)) $value = ($value) ? 'true' : 'false';
				$vars[] = array($name,(string)$value);
			}
		}
		return $vars;
	}
}