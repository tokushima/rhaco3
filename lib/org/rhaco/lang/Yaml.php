<?php
namespace org\rhaco\lang;
/**
 * Yamlを操作する
 * @author tokushima
 */
class Yaml{
	/**
	 * シンプルなyamlからphpに変換
	 * @param string $src YAML文字列
	 * @return mixed[]
	 */
	static public function decode($src){
		$src = preg_replace("/([\"\'])(.+)\\1/me",'str_replace(array("#",":"),array("__SHAPE__","__COLON__"),"\\0")',$src);
		$src = preg_replace("/^([\t]+)/me",'str_replace("\t"," ","\\1")',str_replace(array("\r\n","\r","\n"),"\n",$src));
		$src = preg_replace("/#.+$/m","",$src);
		$stream = array();

		if(!preg_match("/^[\040]*---(.*)$/m",$src)) $src = "---\n".$src;
		if(preg_match_all("/^[\040]*---(.*)$/m",$src,$match,PREG_OFFSET_CAPTURE | PREG_SET_ORDER)){
			$blocks = array();
			$size = sizeof($match) - 1;

			foreach($match as $c => $m){
				$obj = new \stdClass();
				$obj->header = ltrim($m[1][0]);
				$obj->nodes = array();
				$node = array();
				$offset = $m[0][1] + mb_strlen($m[0][0]);
				$block = ($size == $c) ? mb_substr($src,$offset) :
											mb_substr($src,$offset,$match[$c+1][0][1] - $offset);
				foreach(explode("\n",$block) as $key => $line){
					if(!empty($line)){
						if($line[0] == " "){
							$node[] = $line;
						}else{
							self::nodes($obj,$node);
							$result = self::node($node);
							$node = array($line);
						}
					}
				}
				self::nodes($obj,$node);
				array_shift($obj->nodes);
				$stream[] = $obj;
			}
		}
		return $stream;
		/***
			$yml = pre('
						--- hoge
						a: mapping
						foo: bar
						---
						- a
						- sequence
					');
			$obj1 = (object)array("header"=>"hoge","nodes"=>array("a"=>"mapping","foo"=>"bar"));
			$obj2 = (object)array("header"=>"","nodes"=>array("a","sequence"));
			$result = array($obj1,$obj2);
			eq($result,self::decode($yml));

			$yml = pre('
						---
						This: top level mapping
						is:
							- a
							- YAML
							- document
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("This"=>"top level mapping","is"=>array("a","YAML","document")));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						--- !recursive-sequence &001
						- * 001
						- * 001
					');
			$obj1 = (object)array("header"=>"!recursive-sequence &001","nodes"=>array("* 001","* 001"));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						a sequence:
							- one bourbon
							- one scotch
							- one beer
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a sequence"=>array("one bourbon","one scotch","one beer")));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						a scalar key: a scalar value
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a scalar key"=>"a scalar value"));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						- a plain string
						- -42
						- 3.1415
						- 12:34
						- 123 this is an error
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a plain string",-42,3.1415,"12:34","123 this is an error"));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						- >
						 This is a multiline scalar which begins on
						 the next line. It is indicated by a single
						 carat.
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("This is a multiline scalar which begins on the next line. It is indicated by a single carat."));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						- |
						 QTY  DESC		 PRICE TOTAL
						 ===  ====		 ===== =====
						 1  Foo Fighters  $19.95 $19.95
						 2  Bar Belles	$29.95 $59.90
					');
			$rtext = pre('
						QTY  DESC		 PRICE TOTAL
						===  ====		 ===== =====
						1  Foo Fighters  $19.95 $19.95
						2  Bar Belles	$29.95 $59.90
						');
			$obj1 = (object)array("header"=>"","nodes"=>array($rtext));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						-
						  name: Mark McGwire
						  hr:   65
						  avg:  0.278
						-
						  name: Sammy Sosa
						  hr:   63
						  avg:  0.288
					');
			$obj1 = (object)array("header"=>"","nodes"=>array(
													array("name"=>"Mark McGwire","hr"=>65,"avg"=>0.278),
													array("name"=>"Sammy Sosa","hr"=>63,"avg"=>0.288)));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						hr:  65	# Home runs
						avg: 0.278 # Batting average
						rbi: 147   # Runs Batted In
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("hr"=>65,"avg"=>0.278,"rbi"=>147));
			$result = array($obj1);
			eq($result,self::decode($yml));

			$yml = pre('
						name: Mark McGwire
						accomplishment: >
						  Mark set a major league
						  home run record in 1998.
						stats: |
						  65 Home Runs
						  0.278 Batting Average
					');
			$obj1 = (object)array("header"=>"","nodes"=>array(
												"name"=>"Mark McGwire",
												"accomplishment"=>"Mark set a major league home run record in 1998.",
												"stats"=>"65 Home Runs\n0.278 Batting Average"));
			$result = array($obj1);
			eq($result,self::decode($yml));
		*/
	}
	static private function nodes(&$obj,$node){
		$result = self::node($node);
		if(is_array($result) && sizeof($result) == 1){
			if(isset($result[1])){
				$obj->nodes[] = array_shift($result);
			}else{
				$obj->nodes[key($result)] = current($result);
			}
		}else{
			$obj->nodes[] = $result;
		}
	}
	static private function node($node){
		$result = $child = $sequence = array();
		$line = $indent = 0;
		$isseq = $isblock = $onblock = $ischild = $onlabel = false;
		$name = "";
		$node[] = null;

		foreach($node as $value){
			if(!empty($value) && $value[0] == " ") $value = substr($value,$indent);
			switch($value[0]){
				case "[":
				case "{":
					return $value;
					break;
				case " ":
					if($indent == 0 && preg_match("/^[\040]+/",$value,$match)){
						$indent = strlen($match[0]) - 1;
						$value = substr($value,$indent);
					}
					if($isseq){
						if($onlabel){
							$result[$name] .= (($onblock) ? (($isblock) ? "\n" : " ") : "").ltrim(substr($value,1));
						}else{
							$sequence[$line] .= (($onblock) ? (($isblock) ? "\n" : " ") : "").ltrim(substr($value,1));
						}
						$onblock = true;
					}else{
						$child[] = substr($value,1);
					}
					break;
				case "-":
					$line++;
					$value = ltrim(substr($value,1));
					$isseq = $isblock = false;
					switch(trim($value)){
						case "": $ischild = true;
						case "|": $isblock = true; $onblock = false;
						case ">": $value = ""; $isseq = true;
					}
					$sequence[$line] = self::unescape($value);
					break;
				default:
					if(empty($value) && !empty($sequence)){
						if($ischild){
							foreach($sequence as $key => $seq) $sequence[$key] = self::node(explode("\n",$seq));
							return $sequence;
						}
						return (sizeof($sequence) == 1) ? $sequence[1] : array_merge($sequence);
					}else if($name != "" && !empty($child)){
						$result[$name] = self::node($child);
					}
					$onlabel = false;
					if(substr(rtrim($value),-1) == ":"){
						$name = ltrim(self::unescape(substr(trim($value),0,-1)));
						$result[$name] = null;
					}else if(strpos($value,":") !== false){
						list($tmp,$value) = explode(":",$value);
						$tmp = self::unescape(trim($tmp));
						switch(trim($value)){
							case "|": $isblock = true; $onblock = false;
							case ">": $isseq = $onlabel = true; $result[$name = $tmp] = ""; break;
							default: $result[$tmp] = self::unescape(ltrim($value));
						}
					}
					$child = array();
					$indent = 0;
			}
		}
		return $result;
	}
	static private function unescape($value){
		return self::seem(preg_replace("/^(['\"])(.+)\\1.*$/","\\2",str_replace(array("__SHAPE__","__COLON__"),array("#",":"),$value)));
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