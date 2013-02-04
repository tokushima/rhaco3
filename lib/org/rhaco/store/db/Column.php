<?php
namespace org\rhaco\store\db;
/**
 * column定義モデル
 * @author tokushima
 */
class Column{
	private $name;
	private $column;
	private $column_alias;
	private $table;
	private $table_alias;
	private $primary = false;
	private $auto = false;
	private $base = true;

	private function accessor($name,$v=null){
		if($v !== null) $this->{$name} = $v;
		return $this->{$name};
	}
	public function name($v=null){
		return $this->accessor('name',$v);
	}
	public function column($v=null){
		return $this->accessor('column',$v);
	}
	public function column_alias($v=null){
		return $this->accessor('column_alias',$v);
	}
	public function table($v=null){
		return $this->accessor('table',$v);
	}
	public function table_alias($v=null){
		return $this->accessor('table_alias',$v);
	}
	public function primary($v=null){
		return $this->accessor('primary',$v);
	}
	public function auto($v=null){
		return $this->accessor('auto',$v);
	}
	public function base($v=null){
		return $this->accessor('base',$v);
	}
	public function is_base(){
		return ($this->base === true);
	}
	static public function cond_instance($column,$column_alias,$table,$table_alias){
		$self = new self();
		$self->column($column);
		$self->column_alias($column_alias);
		$self->table($table);
		$self->table_alias($table_alias);
		$self->base(false);
		return $self;
	}
}
