<?php
namespace test\model;
/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value @['cond'=>'parent_id(ref_find.id.parent_id,find.id)','column'=>'value1']
 */
class RefRefFind extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}
