<?php
use \org\rhaco\store\db\Q;

$ref = function($obj){
	return $obj;
};


$a1 = $ref(new \test\model\JoinA())->save();
$a2 = $ref(new \test\model\JoinA())->save();
$a3 = $ref(new \test\model\JoinA())->save();
$a4 = $ref(new \test\model\JoinA())->save();
$a5 = $ref(new \test\model\JoinA())->save();
$a6 = $ref(new \test\model\JoinA())->save();

$b1 = $ref(new \test\model\JoinB())->name("aaa")->save();
$b2 = $ref(new \test\model\JoinB())->name("bbb")->save();

$c1 = $ref(new \test\model\JoinC())->a_id($a1->id())->b_id($b1->id())->save();
$c2 = $ref(new \test\model\JoinC())->a_id($a2->id())->b_id($b1->id())->save();
$c3 = $ref(new \test\model\JoinC())->a_id($a3->id())->b_id($b1->id())->save();
$c4 = $ref(new \test\model\JoinC())->a_id($a4->id())->b_id($b2->id())->save();
$c5 = $ref(new \test\model\JoinC())->a_id($a4->id())->b_id($b1->id())->save();
$c6 = $ref(new \test\model\JoinC())->a_id($a5->id())->b_id($b2->id())->save();
$c7 = $ref(new \test\model\JoinC())->a_id($a5->id())->b_id($b1->id())->save();

$re = \test\model\JoinABC::find_all();
eq(7,sizeof($re));

$re = \test\model\JoinABC::find_all(Q::eq("name","aaa"));
eq(5,sizeof($re));

$re = \test\model\JoinABC::find_all(Q::eq("name","bbb"));
eq(2,sizeof($re));


$re = \test\model\JoinABBCC::find_all(Q::eq("name","bbb"));
eq(2,sizeof($re));


