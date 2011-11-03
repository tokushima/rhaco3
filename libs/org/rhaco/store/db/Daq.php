<?php
namespace org\rhaco\store\db;
/**
 * DBへのクエリモデル
 * @author tokushima
 * @var mixed[] $vars
 */
class Daq extends \org\rhaco\Object{
	static private $count = 0;
	protected $sql;
	protected $vars = array();
	protected $id;

	protected function __is_vars__(){
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
	/**
	 * Daqを返す
	 * @param string $sql
	 * @param array $vars
	 * @param string $id_name
	 * @return Daq
	 */
	final static public function get($sql,array $vars=array(),$id_name=null){
		$self = new self();
		$self->sql = $sql;
		$self->vars = $vars;	
		$self->id = $id_name;
		return $self;
	}
}