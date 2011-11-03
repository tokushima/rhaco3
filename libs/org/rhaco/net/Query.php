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
		/***
			eq("req=123",self::get("123","req"));
			eq("req[0]=123",self::get(array(123),"req"));
			eq("req[0]=123&req[1]=456&req[2]=789",self::get(array(123,456,789),"req"));
			eq("",self::get(array(123,456,789)));
			eq("abc=123&def=456&ghi=789",self::get(array("abc"=>123,"def"=>456,"ghi"=>789)));
			eq("req[0]=123&req[1]=&req[2]=789",self::get(array(123,null,789),"req"));
			eq("req[0]=123&req[2]=789",self::get(array(123,null,789),"req",false));
			
			eq("req=123&req=789",self::get(array(123,null,789),"req",false,false));
			eq("label=123&label=&label=789",self::get(array("label"=>array(123,null,789)),null,true,false));

			$name = newclass('
				class *{
					public $id = 0;
					public $value = "";
					public $test = "TEST";
				}
			');
			$obj = new $name();
			$obj->id = 100;
			$obj->value = "hogehoge";
			eq("req[id]=100&req[value]=hogehoge&req[test]=TEST",self::get($obj,"req"));
			eq("id=100&value=hogehoge&test=TEST",self::get($obj));
		 */
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
		/***
			$array = array();
			eq(array(array("abc",123),array("def",456)),self::expand_vars($array,array("abc"=>"123","def"=>456)));
			eq(array(array("abc",123),array("def",456)),$array);
			
			$array = array();			
			eq(array(array("hoge[abc]",123),array("hoge[def]",456)),self::expand_vars($array,array("abc"=>"123","def"=>456),'hoge'));
			eq(array(array("hoge[abc]",123),array("hoge[def]",456)),$array);
			
			$array = array();			
			eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),self::expand_vars($array,array("abc"=>"123","def"=>array("ABC"=>123,"DEF"=>456)),'hoge'));
			eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),$array);
			
			$name = newclass('
				class *{
					public $id = 0;
					public $value = "";
					public $test = "TEST";
				}
			');
			$obj = new $name();
			$obj->id = 100;
			$obj->value = "hogehoge";
			
			$array = array();
			eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),self::expand_vars($array,$obj,"req"));
			eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),$array);
		 */
	}
}