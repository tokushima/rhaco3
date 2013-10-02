<?php
namespace org\rhaco\lang;
/**
 * JSONを操作する
 * @author tokushima
 */
class Json{
	/**
	 * Jsonに変換して取得
	 * @param mixed $v  対象の値
	 * @return string
	 */
	static public function encode($v){
		/***
		 * $variable = array(1,2,3);
		 * eq("[1,2,3]",self::encode($variable));
		 * $variable = "ABC";
		 * eq("\"ABC\"",self::encode($variable));
		 * $variable = 10;
		 * eq(10,self::encode($variable));
		 * $variable = 10.123;
		 * eq(10.123,self::encode($variable));
		 * $variable = true;
		 * eq("true",self::encode($variable));
		 *
		 * $variable = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
		 * eq('["foo","bar",[1,2,"baz"],[3,[4]]]',self::encode($variable));
		 *
		 * $variable = array("foo"=>"bar",'baz'=>1,3=>4);
		 * eq('{"foo":"bar","baz":1,"3":4}',self::encode($variable));
		 *
		 * $variable = array("type"=>"hoge","name"=>"fuga");
		 * eq('{"type":"hoge","name":"fuga"}',self::encode($variable));
		 */
		/***
		 * # array
		 * $variable = array("name"=>"hoge","type"=>"fuga");
		 * eq('{"name":"hoge","type":"fuga"}',self::encode($variable));
		 *
		 * $variable = array("aa","name"=>"hoge","type"=>"fuga");
		 * eq('{"0":"aa","name":"hoge","type":"fuga"}',self::encode($variable));
		 *
		 * $variable = array("aa","hoge","fuga");
		 * eq('["aa","hoge","fuga"]',self::encode($variable));
		 *
		 * $variable = array("aa","hoge","fuga");
		 * eq('["aa","hoge","fuga"]',self::encode($variable));
		 * 
		 * $variable = array(array("aa"=>1),array("aa"=>2),array("aa"=>3));
		 * eq('[{"aa":1},{"aa":2},{"aa":3}]',self::encode($variable));
		 */
		switch(gettype($v)){
			case 'boolean': return ($v) ? 'true' : 'false';
			case 'integer': return intval(sprintf('%d',$v));
			case 'double': return floatval(sprintf('%f',$v));
			case 'array':
				$list = array();
				$i = 0;
				foreach(array_keys($v) as $key){
					if(!ctype_digit((string)$key) || $i !== (int)$key){
						foreach($v as $key => $value) $list[] = sprintf("\"%s\":%s",$key,self::encode($value));
						return sprintf('{%s}',implode(',',$list));
					}
					$i++;
				}
				foreach($v as $key => $value) $list[] = self::encode($value);
				return sprintf('[%s]',implode(',',$list));
			case 'object':
				$list = array();
				foreach((($v instanceof \Traversable) ? $v : get_object_vars($v)) as $key => $value){
					$list[] = sprintf("\"%s\":%s",$key,self::encode($value));
				}
				return sprintf('{%s}',implode(',',$list));
			case 'string':
				return sprintf("\"%s\"",addslashes($v));
			default:
		}
		return 'null';
	}
	/**
	 * JSONPとして出力
	 * @param mixied $var 対象の値
	 * @param string $callback コールバック名
	 * @param string $encode 文字エンコード
	 */
	static public function output($var,$callback=null,$encode='UTF-8'){
		header('Content-Type: application/json; charset='.$encode);
		print(str_replace(array("\r\n","\r","\n"),array("\\n"),(empty($callback) ? self::encode($var) : ($callback.'('.self::encode($var).');'))));
		exit;
	}
	/**
	 * JsonからPHPの変数に変換
	 * @param string $json JSON文字列
	 * @return mixed
	 */
	static public function decode($json){
		if(!is_string($json)) return $json;
		$json = self::seem($json);
		if(!is_string($json)) return $json;
		$json = preg_replace("/[\s]*([,\:\{\}\[\]])[\s]*/","\\1",
						preg_replace_callback("/[\"].*?[\"]/sm",function($m){return str_replace(array(",",":","{","}","[","]"),array("#B#","#C#","#D#","#E#","#F#","#G#"),$m[0]);},
							str_replace(array('\\\\','\\"','$',"\\'"),array('#J#','#A#','#H#','#I#'),trim($json))));
		if(preg_match("/^\"([^\"]*?)\"$/",$json)){
			return str_replace('#J#','\\',stripcslashes(str_replace(array('#A#','#B#','#C#','#D#','#E#','#F#','#G#','#H#','#I#'),array('\\"',',',':','{','}','[',']','$',"\\'"),substr($json,1,-1))));
		}
		$start = substr($json,0,1);
		$end = substr($json,-1);
		if(($start == '[' && $end == ']') || ($start == '{' && $end == '}')){
			$hash = ($start == '{');
			$src = substr($json,1,-1);
			$list = array();
			while(strpos($src,'[') !== false){
				list($value,$start,$end) = self::block($src,'[',']');
				if($value === null) return null;
				$src = str_replace("[".$value."]",str_replace(array('[',']',','),array('#AA#','#AB','#AC'),'['.$value.']'),$src);
			}
			while(strpos($src,'{') !== false){
				list($value,$start,$end) = self::block($src,'{','}');
				if($value === null) return null;
				$src = str_replace('{'.$value.'}',str_replace(array('{','}',','),array('#BA#','#BB','#AC'),'{'.$value.'}'),$src);
			}
			foreach(explode(',',$src) as $value){
				if($value === '') return null;
				$value = str_replace(array('#AA#','#AB','#BA#','#BB','#AC'),array('[',']','{','}',','),$value);

				if($hash){
					$exp = explode(':',$value,2);
					if(sizeof($exp) != 2) throw new \InvalidArgumentException('value error'); 
					list($key,$var) = $exp;
					$index = self::decode($key);
					if($index === null) $index = $key;
					$list[$index] = self::decode($var);
				}else{
					$list[] = self::decode($value);
				}
			}
			return $list;
		}
		return null;
		/***
			$variable = "ABC";
			eq($variable,self::decode('"ABC"'));
			$variable = 10;
			eq($variable,self::decode(10));
			$variable = 10.123;
			eq($variable,self::decode(10.123));
			$variable = true;
			eq($variable,self::decode("true"));
			$variable = false;
			eq($variable,self::decode("false"));
			$variable = null;
			eq($variable,self::decode("null"));
			$variable = array(1,2,3);
			eq($variable,self::decode("[1,2,3]"));
			$variable = array(1,2,array(9,8,7));
			eq($variable,self::decode("[1,2,[9,8,7]]"));
			$variable = array(1,2,array(9,array(10,11),7));
			eq($variable,self::decode("[1,2,[9,[10,11],7]]"));
			
			$variable = array("A"=>"a","B"=>"b","C"=>"c");
			eq($variable,self::decode('{"A":"a","B":"b","C":"c"}'));
			$variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>"f","G"=>"g"));
			eq($variable,self::decode('{"A":"a","B":"b","C":{"E":"e","F":"f","G":"g"}}'));
			$variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>array("H"=>"h","I"=>"i"),"G"=>"g"));
			eq($variable,self::decode('{"A":"a","B":"b","C":{"E":"e","F":{"H":"h","I":"i"},"G":"g"}}'));
			
			$variable = array("A"=>"a","B"=>array(1,2,3),"C"=>"c");
			eq($variable,self::decode('{"A":"a","B":[1,2,3],"C":"c"}'));
			$variable = array("A"=>"a","B"=>array(1,array("C"=>"c","D"=>"d"),3),"C"=>"c");
			eq($variable,self::decode('{"A":"a","B":[1,{"C":"c","D":"d"},3],"C":"c"}'));
			
			$variable = array(array("a"=>1,"b"=>array("a","b",1)),array(null,false,true));
			eq($variable,self::decode('[ {"a" : 1, "b" : ["a", "b", 1] }, [ null, false, true ] ]'));
			
			eq(null,self::decode("[1,2,3,]"));
			eq(null,self::decode("[1,2,3,,,]"));
			
			if(extension_loaded("json")) eq(null,json_decode("[1,[1,2,],3]"));
			eq(array(1,null,3),self::decode("[1,[1,2,],3]"));
			eq(null,self::decode('{"A":"a","B":"b","C":"c",}'));
			
			eq(array("hoge"=>"123,456"),self::decode('{"hoge":"123,456"}'));
		*/
		/***
			# quote
			eq(array("hoge"=>'123,"456'),self::decode('{"hoge":"123,\\"456"}'));
			eq(array("hoge"=>"123,'456"),self::decode('{"hoge":"123,\'456"}'));
			eq(array("hoge"=>'123,\\"456'),self::decode('{"hoge":"123,\\\\\\"456"}'));
			eq(array("hoge"=>"123,\\'456"),self::decode('{"hoge":"123,\\\\\'456"}'));
		 */
		/***
			# escape
			eq(array("hoge"=>"\\"),self::decode('{"hoge":"\\\\"}'));
			eq(array("hoge"=>"a\\"),self::decode('{"hoge":"a\\\\"}'));
			eq(array("hoge"=>"t\\t"),self::decode('{"hoge":"t\\\\t"}'));
			eq(array("hoge"=>"\tA"),self::decode('{"hoge":"\\tA"}'));
		 */
		/***
		 	# value_error
		 	try{
			 	self::decode("{'hoge':'123,456'}");
			 	fail();
			 }catch(\InvalidArgumentException $e){
			 	success();
			 }
		 */
	}
	static private function block($src,$start,$end){
		$eq = ($start == $end);
		if(preg_match_all("/".(($end == null || $eq) ? preg_quote($start,"/") : "(".preg_quote($start,"/").")|(".preg_quote($end,"/").")")."/sm",$src,$match,PREG_OFFSET_CAPTURE)){
			$count = 0;
			$pos = null;

			foreach($match[0] as $key => $value){
				if($value[0] == $start){
					$count++;
					if($pos === null) $pos = $value[1];
				}else if($pos !== null){
					$count--;
				}
				if($count == 0 || ($eq && ($count % 2 == 0))) return array(substr($src,$pos + strlen($start),($value[1] - $pos - strlen($start))),$pos,$value[1] + strlen($end));
			}
		}
		return array(null,0,strlen($src));
	}
	static private function seem($value){
		if(!is_string($value)) throw new \InvalidArgumentException("not string");
		if(is_numeric(trim($value))) return (strpos($value,".") !== false) ? floatval($value) : intval($value);
		switch(strtolower($value)){
			case "null": return null;
			case "true": return true;
			case "false": return false;
			default: return $value;
		}
	}	
}