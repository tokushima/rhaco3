<?php
namespace org\rhaco\store\db\Dao;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Dao;
use \org\rhaco\store\db\Q;
use \org\rhaco\store\db\Column;
use \org\rhaco\Log;
use \org\rhaco\store\db\exception\DaoExceptions;


/**
 * @var serial $id
 * @var string $value;
 */
class InitHasParent extends Dao{
	protected $id;
	protected $value;
}
InitHasParent::create_table();

$obj = new InitHasParent();
$columns = $obj->columns();
eq(2,sizeof($columns));
foreach($columns as $column){
	eq(true,($column instanceof Column));
}

/**
 * @var mixed $extra_value @['extra'=>true]
 */
class ExtraInitHasParent extends InitHasParent{
	protected $extra_value;
}
ExtraInitHasParent::create_table();

try{
	$result = ExtraInitHasParent::find_all();
	success();
}catch(Excepton $e){
	fail();
}

/**
 * @var serial $id
 * @var timestamp $ts
 * @var date $date
 * @var intdate $idate
 */
class DateTime extends Dao{
	protected $id;
	protected $ts;
	protected $date;
	protected $idate;
}
DateTime::create_table();

DateTime::find_delete();
$obj = new DateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(DateTime::find() as $o){
	eq(null,$o->ts());
	eq(null,$o->date());
	eq(null,$o->idate());
}

/**
 * @var timestamp $ts @['auto_now_add'=>true]
 * @var date $date @['auto_now_add'=>true]
 * @var intdate $idate @['auto_now_add'=>true]
 */
class AddNowDateTime extends DateTime{
}
AddNowDateTime::create_table();
AddNowDateTime::find_delete();

$obj = new AddNowDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}

/**
 * @var timestamp $ts @['auto_now'=>true]
 * @var date $date @['auto_now'=>true]
 * @var intdate $idate @['auto_now'=>true]
 */
class AddDateTime extends DateTime{
}
AddDateTime::create_table();
AddDateTime::find_delete();

$obj = new AddDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}

/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true]
 * @var string $code2 @['auto_code_add'=>true,'max'=>10]
 * @var string $code3 @['auto_code_add'=>true,'max'=>40]
 */
class UniqueCode extends Dao{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}
UniqueCode::create_table();
UniqueCode::find_delete();
$obj = new UniqueCode();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(UniqueCode::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
}


/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit']
 * @var string $code2 @['auto_code_add'=>true,'max'=>10,'ctype'=>'digit','ignore_auto_code'=>'000.+000']
 * @var string $code3 @['auto_code_add'=>true,'max'=>40,'ctype'=>'digit']
 */
class UniqueCodeDigit extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}
UniqueCodeDigit::create_table();
UniqueCodeDigit::find_delete();
$obj = new UniqueCodeDigit();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(UniqueCodeDigit::find() as $o){
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


/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'alpha']
 * @var string $code2 @['auto_code_add'=>true,'max'=>10,'ctype'=>'alpha']
 * @var string $code3 @['auto_code_add'=>true,'max'=>40,'ctype'=>'alpha']
 */
class UniqueCodeAlpha extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}
UniqueCodeAlpha::create_table();
UniqueCodeAlpha::find_delete();
$obj = new UniqueCodeAlpha();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(UniqueCodeAlpha::find() as $o){
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

/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit','max'=>1,'ignore_auto_code'=>'[0-8]']
 */
class UniqueCodeIgnore extends UniqueCode{
	protected $id;
	protected $code1;
}
UniqueCodeIgnore::create_table();
UniqueCodeIgnore::find_delete();
$obj = new UniqueCodeIgnore();
eq(null,$obj->code1());
$obj->save();

foreach(UniqueCodeIgnore::find() as $o){
	neq(null,$o->code1());
	eq(1,strlen($o->code1()));
	eq(true,ctype_digit($o->code1()));
	eq('9',$o->code1());
}


/**
 * @var integer $id1 @['primary'=>true]
 * @var integer $id2 @['primary'=>true]
 * @var string $value
 */
class DoublePrimary extends Dao{
	protected $id1;
	protected $id2;
	protected $value;
}
DoublePrimary::create_table();
DoublePrimary::find_delete();
try{
	$obj = new DoublePrimary();
	$obj->id1(1)->id2(1)->value("hoge")->save();
}catch(DaoExceptions $e){
	fail();
}
$p = new DoublePrimary();
eq("hoge",$p->id1(1)->id2(1)->sync()->value());

/**
 * @var serial $id
 * @var string $value1 @['max'=>3,'min'=>2]
 * @var number $value2 @['max'=>3,'min'=>2]
 */
class LimitVerify extends Dao{
	protected $id;
	protected $value1;
	protected $value2;
}
LimitVerify::create_table();
LimitVerify::find_delete();

$obj = new LimitVerify();
$obj->value1("123");
$obj->value2(3);
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	Exceptions::clear();
	fail();
}
$obj = new LimitVerify();
$obj->value1("1234");
$obj->value2(4);
try{
	$obj->save();
	fail();
}catch(DaoExceptions $e){
	Exceptions::clear();
	success();
}
$obj = new LimitVerify();
$obj->value1("1");
$obj->value2(1);
try{
	$obj->save();
	fail();
}catch(DaoExceptions $e){
	Exceptions::clear();
	success();
}

$obj = new LimitVerify();
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	Exceptions::clear();
	fail();
}


/**
 * @var serial $id
 * @var integer $u1 @['unique_together'=>'u2']
 * @var integer $u2
 */
class UniqueVerify extends Dao{
	protected $id;
	protected $u1;
	protected $u2;
}
UniqueVerify::create_table();
UniqueVerify::find_delete();

$obj = new UniqueVerify();
$obj->u1(2);
$obj->u2(3);
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	fail();
	Exceptions::clear();
}

$obj = new UniqueVerify();
$obj->u1(2);
$obj->u2(3);
try{
	$obj->save();
	fail();
}catch(DaoExceptions $e){
	success();
	Exceptions::clear();
}
$obj = new UniqueVerify();
$obj->u1(2);
$obj->u2(4);
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	fail();
	Exceptions::clear();
}


/**
 * @var serial $id
 * @var integer $u1 @['unique_together'=>['u2','u3']]
 * @var integer $u2
 * @var integer $u3
 */
class UniqueTripleVerify extends Dao{
	protected $id;
	protected $u1;
	protected $u2;
	protected $u3;
}
UniqueTripleVerify::create_table();
UniqueTripleVerify::find_delete();


$obj = new UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	fail();
	Exceptions::clear();
}

$obj = new UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
try{
	$obj->save();
	fail();
}catch(DaoExceptions $e){
	success();
	Exceptions::clear();
}
$obj = new UniqueTripleVerify();
$obj->u1(2);
$obj->u2(4);
$obj->u3(4);
try{
	$obj->save();
	success();
}catch(DaoExceptions $e){
	fail();
	Exceptions::clear();
}

/**
 * @var serial $id
 * @var integer $price
 * @var string $type
 * @var string $name
 */
class Calc extends Dao{
	protected $id;
	protected $price;
	protected $type;
	protected $name;
}
Calc:: create_table();
Calc::find_delete();

r(new Calc())->price(30)->type("B")->name("AAA")->save();
r(new Calc())->price(20)->type("B")->name("ccc")->save();
r(new Calc())->price(20)->type("A")->name("AAA")->save();
r(new Calc())->price(10)->type("A")->name("BBB")->save();

eq(80,Calc::find_sum("price"));
eq(30,Calc::find_sum("price",Q::eq("type","A")));

eq(array("A"=>30,"B"=>50),Calc::find_sum_by("price","type"));
eq(array("A"=>30),Calc::find_sum_by("price","type",Q::eq("type","A")));

eq(30,Calc::find_max("price"));
eq(20,Calc::find_max("price",Q::eq("type","A")));
eq("ccc",Calc::find_max("name"));
eq("BBB",Calc::find_max("name",Q::eq("type","A")));


eq(10,Calc::find_min("price"));
eq(20,Calc::find_min("price",Q::eq("type","B")));


$result = Calc::find_min_by("price","type");
eq(array("A"=>10,"B"=>20),$result);
eq(array("A"=>10),Calc::find_min_by("price","type",Q::eq("type","A")));

eq(20,Calc::find_avg("price"));
eq(15,Calc::find_avg("price",Q::eq("type","A")));

eq(array("A"=>15,"B"=>25),Calc::find_avg_by("price","type"));
eq(array("A"=>15),Calc::find_avg_by("price","type",Q::eq("type","A")));

eq(array("A","B"),Calc::find_distinct("type",Q::order('type')));
$result = Calc::find_distinct("name",Q::eq("type","A"));
eq(array("AAA","BBB"),$result);


eq(array("A"=>2,"B"=>2),Calc::find_count_by("id","type"));
eq(array("AAA"=>2,"BBB"=>1,"ccc"=>1),Calc::find_count_by("type","name"));



/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value
 */
class ManyChild extends Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}
ManyChild::create_table();
/**
 * @var serial $id
 * @var string $value
 * @var ManyChild[] $children @['cond'=>'id()parent_id']
 */
class ManyParent extends Dao{
	protected $id;
	protected $value;
	protected $children;
}
ManyParent::create_table();

ManyChild::find_delete();
ManyParent::find_delete();

$p1 = r(new ManyParent())->value("parent1")->save();
$p2 = r(new ManyParent())->value("parent2")->save();

$c1 = r(new ManyChild())->parent_id($p1->id())->value("child1-1")->save();
$c2 = r(new ManyChild())->parent_id($p1->id())->value("child1-2")->save();
$c3 = r(new ManyChild())->parent_id($p1->id())->value("child1-3")->save();
$c4 = r(new ManyChild())->parent_id($p2->id())->value("child2-1")->save();
$c5 = r(new ManyChild())->parent_id($p2->id())->value("child2-2")->save();

$size = array(3,2);
$i = 0;
foreach(ManyParent::find() as $r){
	eq($size[$i],sizeof($r->children()));
	$i++;
}
$i = 0;
foreach(ManyParent::find_all() as $r){
	eq($size[$i],sizeof($r->children()));
	foreach($r->children() as $child){
		eq(true,($child instanceof ManyChild));
		eq($r->id(),$child->parent_id());
	}
	$i++;
}

/**
 * @var serial $id
 */
class JoinA extends Dao{
	protected $id;
}
JoinA::create_table();

/**
 * @var serial $id
 * @var string $name
 */
class JoinB extends Dao{
	protected $id;
	protected $name;
}
JoinB::create_table();

/**
 * @var serial $id
 * @var integer $a_id
 * @var integer $b_id
 */
class JoinC extends Dao{
	protected $id;
	protected $a_id;
	protected $b_id;
}
JoinC::create_table();

JoinA::find_delete();
JoinB::find_delete();
JoinC::find_delete();


/**
 * @class @['table'=>'join_a']
 * @var serial $id
 * @var string $name @['column'=>'name','cond'=>'id(join_c.a_id.b_id,join_b.id)']
 */
class JoinABC extends Dao{
	protected $id;
	protected $name;
}
JoinABC::create_table();

$a1 = r(new JoinA())->save();
$a2 = r(new JoinA())->save();
$a3 = r(new JoinA())->save();
$a4 = r(new JoinA())->save();
$a5 = r(new JoinA())->save();
$a6 = r(new JoinA())->save();

$b1 = r(new JoinB())->name("aaa")->save();
$b2 = r(new JoinB())->name("bbb")->save();

$c1 = r(new JoinC())->a_id($a1->id())->b_id($b1->id())->save();
$c2 = r(new JoinC())->a_id($a2->id())->b_id($b1->id())->save();
$c3 = r(new JoinC())->a_id($a3->id())->b_id($b1->id())->save();
$c4 = r(new JoinC())->a_id($a4->id())->b_id($b2->id())->save();
$c5 = r(new JoinC())->a_id($a4->id())->b_id($b1->id())->save();
$c6 = r(new JoinC())->a_id($a5->id())->b_id($b2->id())->save();
$c7 = r(new JoinC())->a_id($a5->id())->b_id($b1->id())->save();

$re = JoinABC::find_all();
eq(7,sizeof($re));

$re = JoinABC::find_all(Q::eq("name","aaa"));
eq(5,sizeof($re));

$re = JoinABC::find_all(Q::eq("name","bbb"));
eq(2,sizeof($re));




/**
 * @var serial $id
 * @var string $value
 */
class UpdateModel extends Dao{
	protected $id;
	protected $value;
}
UpdateModel::create_table();
UpdateModel::find_delete();

r(new UpdateModel())->value("abc")->save();
r(new UpdateModel())->value("def")->save();
r(new UpdateModel())->value("def")->save();
r(new UpdateModel())->value("def")->save();
r(new UpdateModel())->value("ghi")->save();

eq(5,UpdateModel::find_count());
UpdateModel::find_delete(Q::eq("value","def"));
eq(2,UpdateModel::find_count());


UpdateModel::find_delete();
$d1 = r(new UpdateModel())->value("abc")->save();
$d2 = r(new UpdateModel())->value("def")->save();
$d3 = r(new UpdateModel())->value("ghi")->save();

eq(3,UpdateModel::find_count());
$obj = new UpdateModel();
$obj->id($d1->id())->delete();
eq(2,UpdateModel::find_count());
$obj = new UpdateModel();
$obj->id($d3->id())->delete();
eq(1,UpdateModel::find_count());
eq("def",UpdateModel::find_get()->value());


UpdateModel::find_delete();
$s1 = r(new UpdateModel())->value("abc")->save();
$s2 = r(new UpdateModel())->value("def")->save();
$s3 = r(new UpdateModel())->value("ghi")->save();

eq(3,UpdateModel::find_count());
$obj = new UpdateModel();
$obj->id($s1->id())->sync();
eq("abc",$obj->value());

$obj->value("hoge");
$obj->save();
$obj = new UpdateModel();
$obj->id($s1->id())->sync();
eq("hoge",$obj->value());


UpdateModel::find_delete();
$s1 = r(new UpdateModel())->value("abc")->save();
$s2 = r(new UpdateModel())->value("def")->save();

eq(2,UpdateModel::find_count());
$obj = new UpdateModel();
$obj->id($s1->id())->sync();
eq("abc",$obj->value());
$obj = new UpdateModel();
$obj->id($s2->id())->sync();
eq("def",$obj->value());

$obj = new UpdateModel();
try{
	$obj->id($s2->id()+100)->sync();
	fail();
}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
	success();
}
UpdateModel::find_delete();

/**
 * @var serial $id
 * @var string $value
 */
class CrossParent extends Dao{
	protected $id;
	protected $value;	
}
CrossParent::create_table();

/**
 * @var serial $id
 * @var integer $parent_id
 * @var CrossParent $parent @['cond'=>'parent_id()id']
 */
class CrossChild extends Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}
CrossChild::create_table();

CrossParent::find_delete();
CrossChild::find_delete();

$p1 = r(new CrossParent())->value("A")->save();
$p2 = r(new CrossParent())->value("B")->save();
$c1 = r(new CrossChild())->parent_id($p1->id())->save();
$c2 = r(new CrossChild())->parent_id($p2->id())->save();

$result = array($p1->id()=>"A",$p2->id()=>"B");
foreach(CrossChild::find_all() as $o){
	eq(true,($o->parent() instanceof CrossParent));
	eq($result[$o->parent()->id()],$o->parent()->value());
}


/**
 * @var serial $id
 * @var string $value
 */
class Replication extends Dao{
	protected $id;
	protected $value;
}
Replication::create_table();
Replication::find_delete();
Replication::commit();

/**
 * @class @['table'=>'replication','update'=>false,'create'=>false,'delete'=>false]
 * @var serial $id
 * @var string $value
 */
class ReplicationSlave extends Dao{
	protected $id;
	protected $value;
}
ReplicationSlave::create_table();

$result = ReplicationSlave::find_all();
eq(0,sizeof($result));

try{
	$obj = new ReplicationSlave();
	$obj->value("hoge")->save();
	fail();
}catch(\BadMethodCallException $e){
	success();
}

$result = ReplicationSlave::find_all();
eq(0,sizeof($result));

try{
	$obj = new Replication();
	$obj->value("hoge");
	$obj->save();
	success();
}catch(\BadMethodCallException $e){
	fail();
}

$result = ReplicationSlave::find_all();
eq(1,sizeof($result));

$result = Replication::find_all();
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



/**
 * @var integer $id1 @['primary'=>true]
 * @var integer $id2 @['primary'=>true]
 * @var string $value
 */
class CompositePrimaryKeys extends Dao{
	protected $id1;
	protected $id2;
	protected $value;
}
CompositePrimaryKeys::create_table();
/**
 * @var serial $id
 * @var integer $ref_id
 * @var integer $type_id
 */
class CompositePrimaryKeysRef extends Dao{
	protected $id;
	protected $ref_id;
	protected $type_id;
}
CompositePrimaryKeysRef::create_table();
/**
 * @var string $value @['cond'=>'type_id(composite_primary_keys.id2.id1,ref_id)']
 */
class CompositePrimaryKeysRefValue extends CompositePrimaryKeysRef{
	protected $value;
}


CompositePrimaryKeys::find_delete();
r(new CompositePrimaryKeys())->id1(1)->id2(1)->value('AAA1')->save();
r(new CompositePrimaryKeys())->id1(1)->id2(2)->value('AAA2')->save();
r(new CompositePrimaryKeys())->id1(1)->id2(3)->value('AAA3')->save();

r(new CompositePrimaryKeys())->id1(2)->id2(1)->value('BBB1')->save();
r(new CompositePrimaryKeys())->id1(2)->id2(2)->value('BBB2')->save();
r(new CompositePrimaryKeys())->id1(2)->id2(3)->value('BBB3')->save();

CompositePrimaryKeysRef::find_delete();
r(new CompositePrimaryKeysRef())->ref_id(1)->type_id(1)->save();
r(new CompositePrimaryKeysRef())->ref_id(2)->type_id(1)->save();
r(new CompositePrimaryKeysRef())->ref_id(1)->type_id(2)->save();
r(new CompositePrimaryKeysRef())->ref_id(2)->type_id(2)->save();


$i = 0;
$r = array(
array(1,1,'AAA1'),
array(2,1,'BBB1'),
array(1,2,'AAA2'),
array(2,2,'BBB2'),
);
foreach(CompositePrimaryKeysRefValue::find(Q::order('type_id,id')) as $o){
	eq($r[$i][0],$o->ref_id());
	eq($r[$i][1],$o->type_id());
	eq($r[$i][2],$o->value());
	$i++;
}
eq(4,$i);


