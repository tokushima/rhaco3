<?php
use \org\rhaco\store\db\Q;

$ref = function($obj){
	return $obj;
};


\test\model\InitHasParent::create_table();

$obj = new \test\model\InitHasParent();
$columns = $obj->columns();
eq(2,sizeof($columns));
foreach($columns as $column){
	eq(true,($column instanceof \org\rhaco\store\db\Column));
}

\test\model\ExtraInitHasParent::create_table();
$result = \test\model\ExtraInitHasParent::find_all();


\test\model\DateTime::create_table();

\test\model\DateTime::find_delete();
$obj = new 	\test\model\DateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\model\DateTime::find() as $o){
	eq(null,$o->ts());
	eq(null,$o->date());
	eq(null,$o->idate());
}

\test\model\AddNowDateTime::create_table();
\test\model\AddNowDateTime::find_delete();

$obj = new \test\model\AddNowDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\model\AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}


\test\model\AddDateTime::create_table();
\test\model\AddDateTime::find_delete();

$obj = new \test\model\AddDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\model\AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}

\test\model\UniqueCode::create_table();
\test\model\UniqueCode::find_delete();
$obj = new \test\model\UniqueCode();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(\test\model\UniqueCode::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
}


\test\model\UniqueCodeDigit::create_table();
\test\model\UniqueCodeDigit::find_delete();
$obj = new \test\model\UniqueCodeDigit();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(\test\model\UniqueCodeDigit::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
	eq(true,ctype_digit($o->code1()));
	eq(true,ctype_digit($o->code2()));
	eq(true,ctype_digit($o->code3()));
	
	neq('000',substr($o->code2(),0,3));
	neq('000',substr($o->code2(),-3));
}

\test\model\UniqueCodeAlpha::create_table();
\test\model\UniqueCodeAlpha::find_delete();
$obj = new \test\model\UniqueCodeAlpha();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(\test\model\UniqueCodeAlpha::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
	eq(true,ctype_alpha($o->code1()));
	eq(true,ctype_alpha($o->code2()));
	eq(true,ctype_alpha($o->code3()));
}

\test\model\UniqueCodeIgnore::create_table();
\test\model\UniqueCodeIgnore::find_delete();
$obj = new \test\model\UniqueCodeIgnore();
eq(null,$obj->code1());
$obj->save();

foreach(\test\model\UniqueCodeIgnore::find() as $o){
	neq(null,$o->code1());
	eq(1,strlen($o->code1()));
	eq(true,ctype_digit($o->code1()));
	eq('9',$o->code1());
}

\test\model\DoublePrimary::create_table();
\test\model\DoublePrimary::find_delete();

$obj = new \test\model\DoublePrimary();
$obj->id1(1)->id2(1)->value("hoge")->save();

$p = new \test\model\DoublePrimary();
eq("hoge",$p->id1(1)->id2(1)->sync()->value());

\test\model\LimitVerify::create_table();
\test\model\LimitVerify::find_delete();

$obj = new \test\model\LimitVerify();
$obj->value1("123");
$obj->value2(3);
$obj->save();

$obj = new \test\model\LimitVerify();
$obj->value1("1234");
$obj->value2(0);

// TODO
try{
	$obj->save();
	fail();
}catch(\org\rhaco\Exceptions $e){
	\org\rhaco\Exceptions::clear();
}

$obj = new \test\model\LimitVerify();
$obj->value1("1");
$obj->value2(1);

// TODO
try{
	$obj->save();
	fail();
}catch(\org\rhaco\Exceptions $e){
	\org\rhaco\Exceptions::clear();
}
$obj = new \test\model\LimitVerify();
$obj->save();


\test\model\UniqueVerify::create_table();
\test\model\UniqueVerify::find_delete();

$obj = new \test\model\UniqueVerify();
$obj->u1(2);
$obj->u2(3);
$obj->save();

// TODO
$obj = new \test\model\UniqueVerify();
$obj->u1(2);
$obj->u2(3);
try{
	$obj->save();
	fail();
}catch(\org\rhaco\Exceptions $e){
	\org\rhaco\Exceptions::clear();
}
$obj = new \test\model\UniqueVerify();
$obj->u1(2);
$obj->u2(4);
$obj->save();


\test\model\UniqueTripleVerify::create_table();
\test\model\UniqueTripleVerify::find_delete();


$obj = new \test\model\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
$obj->save();

// TODO
$obj = new \test\model\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
try{
	$obj->save();
	fail();
}catch(\org\rhaco\Exceptions $e){
	\org\rhaco\Exceptions::clear();
}
$obj = new \test\model\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(4);
$obj->u3(4);
$obj->save();

\test\model\Calc:: create_table();
\test\model\Calc::find_delete();

$ref(new \test\model\Calc())->price(30)->type("B")->name("AAA")->save();
$ref(new \test\model\Calc())->price(20)->type("B")->name("ccc")->save();
$ref(new \test\model\Calc())->price(20)->type("A")->name("AAA")->save();
$ref(new \test\model\Calc())->price(10)->type("A")->name("BBB")->save();

eq(80,\test\model\Calc::find_sum("price"));
eq(30,\test\model\Calc::find_sum("price",Q::eq("type","A")));

eq(array("A"=>30,"B"=>50),\test\model\Calc::find_sum_by("price","type"));
eq(array("A"=>30),\test\model\Calc::find_sum_by("price","type",Q::eq("type","A")));

eq(30,\test\model\Calc::find_max("price"));
eq(20,\test\model\Calc::find_max("price",Q::eq("type","A")));
eq("ccc",\test\model\Calc::find_max("name"));
eq("BBB",\test\model\Calc::find_max("name",Q::eq("type","A")));


eq(10,\test\model\Calc::find_min("price"));
eq(20,\test\model\Calc::find_min("price",Q::eq("type","B")));


$result = \test\model\Calc::find_min_by("price","type");
eq(array("A"=>10,"B"=>20),$result);
eq(array("A"=>10),\test\model\Calc::find_min_by("price","type",Q::eq("type","A")));

eq(20,\test\model\Calc::find_avg("price"));
eq(15,\test\model\Calc::find_avg("price",Q::eq("type","A")));

eq(array("A"=>15,"B"=>25),\test\model\Calc::find_avg_by("price","type"));
eq(array("A"=>15),\test\model\Calc::find_avg_by("price","type",Q::eq("type","A")));
eq(array("AAA","BBB"),\test\model\Calc::find_distinct("name",Q::eq("type","A")));
eq(array("A","B"),\test\model\Calc::find_distinct("type"));


eq(array("A"=>2,"B"=>2),\test\model\Calc::find_count_by("id","type"));
eq(array("AAA"=>2,"BBB"=>1,"ccc"=>1),\test\model\Calc::find_count_by("type","name"));



\test\model\ManyChild::create_table();

\test\model\ManyParent::create_table();

\test\model\ManyChild::find_delete();
\test\model\ManyParent::find_delete();

$p1 = $ref(new \test\model\ManyParent())->value("parent1")->save();
$p2 = $ref(new \test\model\ManyParent())->value("parent2")->save();

$c1 = $ref(new \test\model\ManyChild())->parent_id($p1->id())->value("child1-1")->save();
$c2 = $ref(new \test\model\ManyChild())->parent_id($p1->id())->value("child1-2")->save();
$c3 = $ref(new \test\model\ManyChild())->parent_id($p1->id())->value("child1-3")->save();
$c4 = $ref(new \test\model\ManyChild())->parent_id($p2->id())->value("child2-1")->save();
$c5 = $ref(new \test\model\ManyChild())->parent_id($p2->id())->value("child2-2")->save();

$size = array(3,2);
$i = 0;
foreach(\test\model\ManyParent::find() as $r){
	eq($size[$i],sizeof($r->children()));
	$i++;
}
$i = 0;
foreach(\test\model\ManyParent::find_all() as $r){
	eq($size[$i],sizeof($r->children()));
	foreach($r->children() as $child){
		eq(true,($child instanceof \test\model\ManyChild));
		eq($r->id(),$child->parent_id());
	}
	$i++;
}


\test\model\UpdateModel::create_table();
\test\model\UpdateModel::find_delete();

$ref(new \test\model\UpdateModel())->value("abc")->save();
$ref(new \test\model\UpdateModel())->value("def")->save();
$ref(new \test\model\UpdateModel())->value("def")->save();
$ref(new \test\model\UpdateModel())->value("def")->save();
$ref(new \test\model\UpdateModel())->value("ghi")->save();

eq(5,\test\model\UpdateModel::find_count());
\test\model\UpdateModel::find_delete(Q::eq("value","def"));
eq(2,\test\model\UpdateModel::find_count());


\test\model\UpdateModel::find_delete();
$d1 = $ref(new \test\model\UpdateModel())->value("abc")->save();
$d2 = $ref(new \test\model\UpdateModel())->value("def")->save();
$d3 = $ref(new \test\model\UpdateModel())->value("ghi")->save();

eq(3,\test\model\UpdateModel::find_count());
$obj = new \test\model\UpdateModel();
$obj->id($d1->id())->delete();
eq(2,\test\model\UpdateModel::find_count());
$obj = new \test\model\UpdateModel();
$obj->id($d3->id())->delete();
eq(1,\test\model\UpdateModel::find_count());
eq("def",\test\model\UpdateModel::find_get()->value());


\test\model\UpdateModel::find_delete();
$s1 = $ref(new \test\model\UpdateModel())->value("abc")->save();
$s2 = $ref(new \test\model\UpdateModel())->value("def")->save();
$s3 = $ref(new \test\model\UpdateModel())->value("ghi")->save();

eq(3,\test\model\UpdateModel::find_count());
$obj = new \test\model\UpdateModel();
$obj->id($s1->id())->sync();
eq("abc",$obj->value());

$obj->value("hoge");
$obj->save();
$obj = new \test\model\UpdateModel();
$obj->id($s1->id())->sync();
eq("hoge",$obj->value());


\test\model\UpdateModel::find_delete();
$s1 = $ref(new \test\model\UpdateModel())->value("abc")->save();
$s2 = $ref(new \test\model\UpdateModel())->value("def")->save();

eq(2,\test\model\UpdateModel::find_count());
$obj = new \test\model\UpdateModel();
$obj->id($s1->id())->sync();
eq("abc",$obj->value());
$obj = new \test\model\UpdateModel();
$obj->id($s2->id())->sync();
eq("def",$obj->value());

$obj = new \test\model\UpdateModel();
try{
	$obj->id($s2->id()+100)->sync();
	fail();
}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
}
\test\model\UpdateModel::find_delete();

\test\model\CrossParent::create_table();

\test\model\CrossChild::create_table();

\test\model\CrossParent::find_delete();
\test\model\CrossChild::find_delete();

$p1 = $ref(new \test\model\CrossParent())->value("A")->save();
$p2 = $ref(new \test\model\CrossParent())->value("B")->save();
$c1 = $ref(new \test\model\CrossChild())->parent_id($p1->id())->save();
$c2 = $ref(new \test\model\CrossChild())->parent_id($p2->id())->save();

$result = array($p1->id()=>"A",$p2->id()=>"B");
foreach(\test\model\CrossChild::find_all() as $o){
	eq(true,($o->parent() instanceof \test\model\CrossParent));
	eq($result[$o->parent()->id()],$o->parent()->value());
}


\test\model\Replication::create_table();
\test\model\Replication::find_delete();
\test\model\Replication::commit();

\test\model\ReplicationSlave::create_table();

$result = \test\model\ReplicationSlave::find_all();
eq(0,sizeof($result));

try{
	$obj = new \test\model\ReplicationSlave();
	$obj->value("hoge")->save();
	fail();
}catch(\BadMethodCallException $e){
}

$result = \test\model\ReplicationSlave::find_all();
eq(0,sizeof($result));

$obj = new \test\model\Replication();
$obj->value("hoge");
$obj->save();

$result = \test\model\ReplicationSlave::find_all();
eq(1,sizeof($result));

$result = \test\model\Replication::find_all();
if(eq(1,sizeof($result))){
	eq("hoge",$result[0]->value());

	try{
		$result[0]->value("fuga");
		$result[0]->save();
		eq("fuga",$result[0]->value());
	}catch(\BadMethodCallException $e){
		fail();
	}
}




\test\model\CompositePrimaryKeys::create_table();
\test\model\CompositePrimaryKeysRef::create_table();


\test\model\CompositePrimaryKeys::find_delete();
$ref(new \test\model\CompositePrimaryKeys())->id1(1)->id2(1)->value('AAA1')->save();
$ref(new \test\model\CompositePrimaryKeys())->id1(1)->id2(2)->value('AAA2')->save();
$ref(new \test\model\CompositePrimaryKeys())->id1(1)->id2(3)->value('AAA3')->save();

$ref(new \test\model\CompositePrimaryKeys())->id1(2)->id2(1)->value('BBB1')->save();
$ref(new \test\model\CompositePrimaryKeys())->id1(2)->id2(2)->value('BBB2')->save();
$ref(new \test\model\CompositePrimaryKeys())->id1(2)->id2(3)->value('BBB3')->save();

\test\model\CompositePrimaryKeysRef::find_delete();
$ref(new \test\model\CompositePrimaryKeysRef())->ref_id(1)->type_id(1)->save();
$ref(new \test\model\CompositePrimaryKeysRef())->ref_id(2)->type_id(1)->save();
$ref(new \test\model\CompositePrimaryKeysRef())->ref_id(1)->type_id(2)->save();
$ref(new \test\model\CompositePrimaryKeysRef())->ref_id(2)->type_id(2)->save();


$i = 0;
$r = array(
array(1,1,'AAA1'),
array(2,1,'BBB1'),
array(1,2,'AAA2'),
array(2,2,'BBB2'),
);
foreach(\test\model\CompositePrimaryKeysRefValue::find(Q::order('type_id,id')) as $o){
	eq($r[$i][0],$o->ref_id());
	eq($r[$i][1],$o->type_id());
	eq($r[$i][2],$o->value());
	$i++;
}
eq(4,$i);


