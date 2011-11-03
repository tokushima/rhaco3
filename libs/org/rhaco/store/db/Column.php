<?php
namespace org\rhaco\store\db;
/**
 * column定義モデル
 * @author tokushima
 * @var boolean $primary
 * @var boolean $self
 * @var boolean $auto
 * @var boolean $base
 */
class Column extends \org\rhaco\Object{
	protected $name;
	protected $column;
	protected $column_alias;
	protected $table;
	protected $table_alias;
	protected $primary = false;
	protected $auto = false;
	protected $base = true;

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
