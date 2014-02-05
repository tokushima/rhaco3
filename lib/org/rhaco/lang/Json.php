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