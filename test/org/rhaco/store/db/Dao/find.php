<?php
namespace org\rhaco\store\db\Dao;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Dao;
use \org\rhaco\store\db\Q;
use \org\rhaco\store\db\Column;
use \org\rhaco\Log;

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
Find::create_table();

class AbcFind extends Find{
	protected function __find_conds__(){
		return Q::b(Q::eq("value1","abc"));
	}
}
AbcFind::create_table();
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
RefFind::create_table();
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
RefRefFind::create_table();
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
HasFind::create_table();
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
SubFind::create_table();

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
		Q::b(Q::eq("order",2),Q::ob(Q::b(Q::eq("value1",'ccc',Q::IGNORE)),Q::b(Q::eq("value2",'AAA',Q::IGNORE)))),
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
