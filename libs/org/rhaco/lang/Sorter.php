<?php
namespace org\rhaco\lang;
/**
 * ソート
 * @author tokushima
 */
class Sorter{
	/**
	 * ハッシュのキーを指定してソート
	 *
	 * @param array $list
	 * @param string $key
	 * @return array
	 */
	static public function hash(array $list,$key){
		if(!empty($key) && is_string($key)){
			$revers = false;

			if($key[0] == "-"){
				$key = substr($key,1);
				$revers = true;
			}
			try{
				foreach($list as $o) $o[$key] = isset($o[$key]) ? $o[$key] : null;
				usort($list,create_function('$a,$b',sprintf('return ($a["%s"] %s $b["%s"]) ? -1 : 1;',$key,(($revers) ? ">" : "<"),$key)));
			}catch(ErrorException $e){}
		}
		return $list;
		/***
			$o = array();
			$o[] = array("id"=>1,"abc"=>1);
			$o[] = array("id"=>2,"abc"=>3);
			$o[] = array("id"=>3,"abc"=>2);
			
			usort($o,create_function('$a,$b',sprintf('return ($a["abc"] > $b["abc"]) ? -1 : 1;')));
			eq(2,$o[0]["id"]);
			eq(3,$o[1]["id"]);
			eq(1,$o[2]["id"]);
		 */
		/***
			$objects = array();
			$obj["id"] = 1;
			$obj["abc"] = 1;
			$objects[] = $obj;
			
			$obj["id"]  = 2;
			$obj["abc"] = 3;
			$objects[] = $obj;

			$obj["id"]  = 3;
			$obj["abc"] = 2;
			$objects[] = $obj;
			
			eq(3,sizeof($objects));
			$sort = self::hash($objects,"abc");
			eq(3,sizeof($sort));
			eq(1,$sort[0]["id"]);
			eq(3,$sort[1]["id"]);
			eq(2,$sort[2]["id"]);
			
			eq(1,$objects[0]["id"]);
			eq(2,$objects[1]["id"]);
			eq(3,$objects[2]["id"]);

			$sort = self::hash($objects,"-abc");
			eq(3,sizeof($sort));
			eq(2,$sort[0]["id"]);
			eq(3,$sort[1]["id"]);
			eq(1,$sort[2]["id"]);
		 */
	}	
	/**
	 * Objectのgetterを指定してソート
	 *
	 * @param array $list
	 * @param string $getter_name
	 * @return array
	 */
	static public function object(array $list,$getter_name){
		if(!empty($getter_name) && is_string($getter_name)){
			$revers = false;

			if($getter_name[0] == "-"){
				$getter_name = substr($getter_name,1);
				$revers = true;
			}
			try{
				foreach($list as $o) $o->$getter_name();
				usort($list,create_function('$a,$b',sprintf('return ($a->%s() %s $b->%s()) ? -1 : 1;',$getter_name,(($revers) ? ">" : "<"),$getter_name)));
			}catch(ErrorException $e){}
		}
		return $list;
		/***
			$o = array();
			$o[] = (object)array("id"=>1,"abc"=>1);
			$o[] = (object)array("id"=>2,"abc"=>3);
			$o[] = (object)array("id"=>3,"abc"=>2);
			
			usort($o,create_function('$a,$b',sprintf('return ($a->abc > $b->abc) ? -1 : 1;')));
			eq(2,$o[0]->id);
			eq(3,$o[1]->id);
			eq(1,$o[2]->id);
		 */
		/***
			$objects = array();
			$obj = new \org\rhaco\Object();
			$obj->id = 1;
			$obj->abc = 1;
			$objects[] = $obj;
			
			$obj = new \org\rhaco\Object();
			$obj->id = 2;
			$obj->abc = 3;
			$objects[] = $obj;

			$obj = new \org\rhaco\Object();
			$obj->id = 3;
			$obj->abc = 2;
			$objects[] = $obj;
			
			eq(3,sizeof($objects));
			$sort = self::object($objects,"abc");
			eq(3,sizeof($sort));
			eq(1,$sort[0]->id());
			eq(3,$sort[1]->id());
			eq(2,$sort[2]->id());
			
			eq(1,$objects[0]->id());
			eq(2,$objects[1]->id());
			eq(3,$objects[2]->id());

			$sort = self::object($objects,"-abc");
			eq(3,sizeof($sort));
			eq(2,$sort[0]->id());
			eq(3,$sort[1]->id());
			eq(1,$sort[2]->id());
		 */
	}
	/**
	 * 文字列として比較してソート
	 *
	 * @param array $list
	 * @param boolean $revers
	 * @return array
	 */
	static public function string(array $list,$revers=false){
		uasort($list,create_function('$a,$b',sprintf('return (strcmp((string)($a),(string)($b)) > 0) ? %s1 : %s1;',(($revers) ? "-" : ""),(($revers) ? "" : "-"))));
		return $list;
	}
	/**
	 * オーダー文字列を取得
	 * @param string $o 現在のオーダー 
	 * @param string $p 前回のオーダー
	 * @return string
	 */
	static public function order($o,$p){
		$or = $pr = false;
		
		if(empty($o)) return null;
		if($o[0] == "-"){
			$or = true;
			$o = substr($o,1);
		}
		if(!empty($p) && $p[0] == "-"){
			$pr = true;
			$p = substr($p,1);
		}
		if($p !== $o) return (($or) ? "-" : "").$o;
		if($or === $pr) return (($or) ? "" : "-").$o;
		return (($or) ? "-" : "").$o;
		/***
			eq("name",self::order("name",null));
			eq("-name",self::order("-name",null));
			eq("name",self::order("name","id"));
			eq("name",self::order("name","-id"));
			eq("-name",self::order("-name","id"));
			eq("-name",self::order("-name","-id"));

			eq("-name",self::order("name","name"));
			eq("name",self::order("name","-name"));
			eq("-name",self::order("-name","name"));
			eq("name",self::order("-name","-name"));
		 */
	}
}
