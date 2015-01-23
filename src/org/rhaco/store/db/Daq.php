<?php
namespace org\rhaco\store\db;
/**
 * DBへのクエリモデル
 * @author tokushima
 * @var mixed[] $vars
 */
class Daq{
	static private $count = 0;
	private $sql;
	private $vars = array();
	private $id;

	public function __construct($sql=null,array $vars=array(),$id_name=null){
		$this->sql = $sql;
		$this->id = $id_name;
		
		foreach($vars as $k => $v){
			$this->vars[$k] = is_bool($v) ? (($v === true) ? 1 : 0) : $v;
		}
	}
	public function id(){
		return $this->id;
	}
	public function sql(){
		return $this->sql;
	}
	public function ar_vars(){
		return (empty($this->vars) ? array() : $this->vars);
	}
	public function is_id(){
		return !empty($this->id);
	}
	public function is_vars(){
		return !empty($this->vars);
	}
	public function unique_sql(){
		$rep = array();
		$sql = $this->sql();

		if(preg_match_all("/[ct][\d]+/",$this->sql,$match)){
			foreach($match[0] as $m){
				if(!isset($rep[$m])) $rep[$m] = 'q'.self::$count++;
			}
			foreach($rep as $key => $value){
				$sql = str_replace($key,$value,$sql);
			}
		}
		return $sql;
	}
}
