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

eq(array("B","A"),Calc::find_distinct("type"));
$result = Calc::find_distinct("name",Q::eq("type","A"));
eq(array("AAA","BBB"),$result);


eq(array("A"=>2,"B"=>2),Calc::find_count_by("id","type"));
eq(array("AAA"=>2,"BBB"=>1,"ccc"=>1),Calc::find_count_by("type","name"));


/**
 * @var serial $id
 * @var number $order
 * @var timestamp $updated
 * @var string $value
 * @var string $value2
 */
class Find extends Dao{
	protected $id;
	protected $order;
	protected $value1;
	protected $value2;
	protected $updated;	
}
class AbcFind extends Find{
	protected function __find_conds__(){
		return Q::b(Q::eq("value1","abc"));
	}
}
/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value @['cond'=>'parent_id(find.id)','column'=>'value1']
 */
class RefFind extends Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}
/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value @['cond'=>'parent_id(ref_find.id.parent_id,find.id)','column'=>'value1']
 */
class RefRefFind extends Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}
/**
 * @class @['table'=>'ref_find']
 * @var serial $id
 * @var integer $parent_id
 * @var Find $parent @['cond'=>'parent_id()id']
 */
class HasFind extends Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}
/**
 * @var serial $id
 * @var string $value
 * @var integer $order;
 */
class SubFind extends Dao{
	protected $id;
	protected $value;
	protected $order;
}

RefRefFind::find_delete();
RefFind::find_delete();
Find::find_delete();
SubFind::find_delete();

$abc = r(new Find())->order(4)->value1("abc")->value2("ABC")->save();
$def = r(new Find())->order(3)->value1("def")->value2("DEF")->save();
$ghi = r(new Find())->order(1)->value1("ghi")->value2("GHI")->updated("2008/12/24 10:00:00")->save();
$jkl = r(new Find())->order(2)->value1("jkl")->value2("EDC")->save();
$aaa = r(new Find())->order(2)->value1("aaa")->value2("AAA")->updated("2008/12/24 10:00:00")->save();
$bbb = r(new Find())->order(2)->value1("bbb")->value2("Aaa")->save();
$ccc = r(new Find())->order(2)->value1("ccc")->value2("aaa")->save();
$mno = r(new Find())->order(2)->value1("mno")->value2(null)->save();


$ref1 = r(new RefFind())->parent_id($jkl->id())->save();
$ref2 = r(new RefFind())->parent_id($ccc->id())->save();

$refref1 = r(new RefRefFind())->parent_id($ref1->id())->save();

$sub1 = r(new SubFind())->value("abc")->order(4)->save();
$sub2 = r(new SubFind())->value("def")->order(3)->save();
$sub3 = r(new SubFind())->value("ghi")->order(1)->save();
$sub4 = r(new SubFind())->value("jkl")->order(2)->save();

eq(8,sizeof(Find::find_all()));

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
/**
 * @var serial $id
 * @var string $name
 */
class JoinB extends Dao{
	protected $id;
	protected $name;
}
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


foreach(Find::find(Q::eq("value1","abc")) as $obj){
	eq("abc",$obj->value1());
}
foreach(AbcFind::find() as $obj){
	eq("abc",$obj->value1());
}

eq(8,Find::find_count());
eq(8,Find::find_count("value1"));
eq(7,Find::find_count("value2"));
eq(5,Find::find_count(Q::eq("order",2)));
eq(4,Find::find_count(
	Q::neq("value1","abc"),
	Q::ob(
		Q::b(Q::eq("order",2)),
		Q::b(Q::eq("order",4))
	),
	Q::neq("value1","aaa")
));
$q = new Q();
$q->add(Q::neq("value1","abc"));
$q->add(Q::ob(
		Q::b(Q::eq("order",2)),
		Q::b(Q::eq("order",4))
	));
$q->add(Q::neq("value1","aaa"));
eq(4,Find::find_count($q));

$q = new Q();
$q->add(Q::ob(
		Q::b(Q::eq("order",2),Q::ob(Q::b(Q::eq("value1",'ccc')),Q::b(Q::eq("value2",'AAA')))),
		Q::b(Q::eq("order",4))
	));
eq(4,Find::find_count($q));


$paginator = new \org\rhaco\Paginator(1,2);
eq(1,sizeof($result = Find::find_all(Q::neq("value1","abc"),$paginator)));
eq("ghi",$result[0]->value1());
eq(7,$paginator->total());

$i = 0;
foreach(Find::find(
	Q::neq("value1","abc"),
	Q::ob(
		Q::b(Q::eq("order",2)),
		Q::b(Q::eq("order",4))
	),
	Q::neq("value1","aaa")
) as $obj){
	$i++;
}
eq(4,$i);

$list = array("abc","def","ghi","jkl","aaa","bbb","ccc","mno");
$i = 0;
foreach(Find::find() as $obj){
	eq($list[$i],$obj->value1());
	$i++;
}
foreach(Find::find(Q::eq("value1","AbC",Q::IGNORE)) as $obj){
	eq("abc",$obj->value1());
}
foreach(Find::find(Q::neq("value1","abc")) as $obj){
	neq("abc",$obj->value1());
}
try{
	Find::find(Q::eq("value_error","abc"));
	fail();
}catch(\Exception $e){
	success();
}

$i = 0;
$r = array("aaa","bbb","ccc");
foreach(Find::find(Q::startswith("value1,value2",array("aa"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array("abc","jkl","ccc");
foreach(Find::find(Q::endswith("value1,value2",array("c"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array("abc","bbb");
foreach(Find::find(Q::contains("value1,value2",array("b"))) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(2,$i);

$i = 0;
$r = array("abc","jkl","ccc");
foreach(Find::find(Q::endswith("value1,value2",array("C"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
	$t[] = $obj->value1();
}
eq(3,$i);

$i = 0;
foreach(Find::find(Q::in("value1",array("abc"))) as $obj){
	eq("abc",$obj->value1());
	$i++;
}
eq(1,$i);

foreach(Find::find(Q::match("value1=abc")) as $obj){
	eq("abc",$obj->value1());
}
foreach(Find::find(Q::match("value1=!abc")) as $obj){
	neq("abc",$obj->value1());
}
foreach(Find::find(Q::match("abc")) as $obj){
	eq("abc",$obj->value1());
}
$i = 0;
$r = array("aaa","bbb","mno");
foreach(Find::find(Q::neq("value1","ccc"),new \org\rhaco\Paginator(1,3),Q::order("-id")) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
foreach(Find::find(Q::neq("value1","abc"),new \org\rhaco\Paginator(1,3),Q::order("id")) as $obj){
	eq("jkl",$obj->value1());
}
$i = 0;
$r = array("mno","aaa");
foreach(Find::find(Q::neq("value1","ccc"),new \org\rhaco\Paginator(1,2),Q::order("order,-id")) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
$result = Find::find_all(Q::match("AAA",Q::IGNORE));
eq(3,sizeof($result));

$result = Find::find_all(Q::match("AA",Q::IGNORE));
eq(3,sizeof($result));

$result = Find::find_all(Q::eq("value2",null));
eq(1,sizeof($result));
$result = Find::find_all(Q::neq("value2",null));
eq(7,sizeof($result));

$result = Find::find_all(Q::eq("updated",null));
eq(6,sizeof($result));
$result = Find::find_all(Q::neq("updated",null));
eq(2,sizeof($result));
eq("2008/12/24 10:00:00",$result[0]->fm_updated());

$c = 0;
for($i=0;$i<10;$i++){
	$a = $b = array();
	foreach(Find::find_all(Q::random_order()) as $o) $a[] = $o->id();
	foreach(Find::find_all(Q::random_order()) as $o) $b[] = $o->id();
	if($a === $b) $c++;
}
neq(10,$c);


$result = Find::find_all(Q::ob(
						Q::b(Q::eq("value1","abc"))
						,Q::b(Q::eq("value2","EDC"))
					));
eq(2,sizeof($result));

eq("EDC",Find::find_get(Q::eq("value1","jkl"))->value2());

$i = 0;
$r = array("jkl","ccc");
foreach(RefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(2,$i);

$i = 0;
$r = array("jkl");
foreach(RefRefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(1,$i);


$i = 0;
$r = array("jkl","ccc");
foreach(HasFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->parent()->value1());
	$i++;
}
eq(2,$i);


$result = Find::find_all(Q::in("value1",SubFind::find_sub("value")));
eq(4,sizeof($result));
$result = Find::find_all(Q::in("value1",SubFind::find_sub("value",Q::lt("order",3))));
eq(2,sizeof($result));


/**
 * @var serial $id
 * @var string $value
 */
class UpdateModel extends Dao{
	protected $id;
	protected $value;
}
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


