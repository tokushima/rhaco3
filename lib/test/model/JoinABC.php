<?php
namespace test\model;
/**
 * @class @['table'=>'join_a']
 * @var serial $id
 * @var string $name @['column'=>'name','cond'=>'id(join_c.a_id.b_id,join_b.id)']
 */
class JoinABC extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $name;
}

