<?php
namespace test\model;
/**
 * @var serial $id
 * @var string $value1 @['max'=>3,'min'=>2]
 * @var number $value2 @['max'=>3,'min'=>2]
 */
class LimitVerify extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value1;
	protected $value2;
}

