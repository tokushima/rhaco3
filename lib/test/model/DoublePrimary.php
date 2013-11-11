<?php
namespace test\model;
/**
 * @var integer $id1 @['primary'=>true]
 * @var integer $id2 @['primary'=>true]
 * @var string $value
 */
class DoublePrimary extends \org\rhaco\store\db\Dao{
	protected $id1;
	protected $id2;
	protected $value;
}
