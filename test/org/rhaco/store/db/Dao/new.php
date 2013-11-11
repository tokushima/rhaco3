<?php
use \org\rhaco\store\db\Q;

\test\model\NewDao::create_table();


$obj = new \test\model\NewDao();
neq(null,$obj);


\test\model\NewDao::find_delete();
eq(0,\test\model\NewDao::find_count());

$obj = new \test\model\NewDao();
$obj->save();

$obj = new \test\model\NewDao();
$obj->value(null);
$obj->save();

$obj = new \test\model\NewDao();
$obj->value('');
$obj->save();

eq(1,\test\model\NewDao::find_count(Q::eq('value','')));
eq(2,\test\model\NewDao::find_count(Q::eq('value',null)));
eq(3,\test\model\NewDao::find_count());

$r = array(null,null,'');
$i = 0;
foreach(\test\model\NewDao::find(Q::order('id')) as $o){
	eq($r[$i],$o->value());
	$i++;
}
