<?php
namespace org\rhaco\store\queue\module\Dao;
/**
 * キュー用のDBモデル
 * @var serial $id
 * @var number $lock
 * @var string $type @['max'=>32]
 * @var string $data @['max'=>255]
 * @var timestamp $fin
 * @var integer $priority
 * @var timestamp $create_date @['auto_now_add'=>true]
 * @author tokushima
 */
class QueueDao extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $type;
	protected $data;
	protected $lock;
	protected $fin;
	protected $priority;
	protected $create_date;
	
	public function set(\org\rhaco\store\queue\Model $obj){
		foreach($obj->props() as $k => $v) $this->{$k} = $v;
		return $this;
	}
	public function get(){
		$obj = new \org\rhaco\store\queue\Model();
		foreach($this->props() as $k => $v) $obj->{$k}($v);
		return $obj;
	}
}