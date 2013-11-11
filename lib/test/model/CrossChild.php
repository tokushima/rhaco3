<?php
namespace test\model;
/**
 * @var serial $id
 * @var integer $parent_id
 * @var CrossParent $parent @['cond'=>'parent_id()id']
 */
class CrossChild extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}

