<?php
namespace test\model;
/**
 * @class @['table'=>'ref_find']
 * @var serial $id
 * @var integer $parent_id
 * @var Find $parent @['cond'=>'parent_id()id']
 */
class HasFind extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}
