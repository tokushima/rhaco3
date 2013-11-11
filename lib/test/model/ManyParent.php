<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $value
 * @var ManyChild[] $children @['cond'=>'id()parent_id']
 */
class ManyParent extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
	protected $children;
}
