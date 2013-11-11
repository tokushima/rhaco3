<?php
namespace test\model;
/**
 * @var serial $id
 * @var integer $u1 @['unique_together'=>['u2','u3']]
 * @var integer $u2
 * @var integer $u3
 */
class UniqueTripleVerify extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $u1;
	protected $u2;
	protected $u3;
}

