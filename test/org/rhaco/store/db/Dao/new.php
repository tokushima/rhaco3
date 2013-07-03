<?php
namespace org\rhaco\store\db\Dao\test;
use \org\rhaco\store\db\Q;

/**
 * @var serial $id
 * @var string $value;
 */
class NewDao extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
}


NewDao::find_delete();
eq(0,NewDao::find_count());


$obj = new NewDao();
neq(null,$obj);


$obj = new NewDao();
$obj->save();

$obj = new NewDao();
$obj->value('');
$obj->save();


eq(1,NewDao::find_count(Q::eq('value','')));
eq(1,NewDao::find_count(Q::eq('value',null)));
eq(2,NewDao::find_count());

