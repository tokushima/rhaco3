<?php
namespace test\model;
/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value @['cond'=>'parent_id(find.id)','column'=>'value1']
 * @var string $value2 @['cond'=>'@value']
 */
class RefFind extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
	protected $value2;

	private $private_value;
}
