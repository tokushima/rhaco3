<?php
namespace org\rhaco\store\db\Dao\test;
/**
 * @var serial $id
 * @var string $value;
 */
class NewDao extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
}

$obj = new NewDao();
neq(null,$obj);

