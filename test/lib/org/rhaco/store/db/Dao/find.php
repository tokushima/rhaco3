<?php
use \org\rhaco\store\db\Q;

$ref = function($obj){
	return $obj;
};


\test\model\Find::create_table();
\test\model\AbcFind::create_table();
\test\model\RefFind::create_table();
\test\model\RefRefFind::create_table();
\test\model\HasFind::create_table();
\test\model\SubFind::create_table();
\test\model\RefRefFind::find_delete();
\test\model\RefFind::find_delete();
\test\model\Find::find_delete();
\test\model\SubFind::find_delete();

$abc = $ref(new \test\model\Find())->order(4)->value1("abc")->value2("ABC")->save();
$def = $ref(new \test\model\Find())->order(3)->value1("def")->value2("DEF")->save();
$ghi = $ref(new \test\model\Find())->order(1)->value1("ghi")->value2("GHI")->updated("2008/12/24 10:00:00")->save();
$jkl = $ref(new \test\model\Find())->order(2)->value1("jkl")->value2("EDC")->save();
$aaa = $ref(new \test\model\Find())->order(2)->value1("aaa")->value2("AAA")->updated("2008/12/24 10:00:00")->save();
$bbb = $ref(new \test\model\Find())->order(2)->value1("bbb")->value2("Aaa")->save();
$ccc = $ref(new \test\model\Find())->order(2)->value1("ccc")->value2("aaa")->save();
$mno = $ref(new \test\model\Find())->order(2)->value1("mno")->value2(null)->save();


$ref1 = $ref(new \test\model\RefFind())->parent_id($abc->id())->save();
$ref2 = $ref(new \test\model\RefFind())->parent_id($def->id())->save();
$ref3 = $ref(new \test\model\RefFind())->parent_id($ghi->id())->save();
$ref4 = $ref(new \test\model\RefFind())->parent_id($jkl->id())->save();


eq(4,sizeof(\test\model\RefFind::find_all()));
eq(1,sizeof(\test\model\RefFind::find_all(Q::eq('value','def'))));
eq(1,sizeof(\test\model\RefFind::find_all(Q::eq('value2','EDC'))));

eq(1,sizeof(\test\model\RefFindExt::find_all(Q::eq('value','def'))));
eq(1,sizeof(\test\model\RefFindExt::find_all(Q::eq('value2','EDC'))));
eq(1,sizeof(\test\model\RefFindExt::find_all(Q::eq('order',3))));


$refref1 = $ref(new \test\model\RefRefFind())->parent_id($ref1->id())->save();

$sub1 = $ref(new \test\model\SubFind())->value("abc")->order(4)->save();
$sub2 = $ref(new \test\model\SubFind())->value("def")->order(3)->save();
$sub3 = $ref(new \test\model\SubFind())->value("ghi")->order(1)->save();
$sub4 = $ref(new \test\model\SubFind())->value("jkl")->order(2)->save();

eq(8,sizeof(\test\model\Find::find_all()));

foreach(\test\model\Find::find(Q::eq("value1","abc")) as $obj){
	eq("abc",$obj->value1());
}
foreach(\test\model\AbcFind::find() as $obj){
	eq("abc",$obj->value1());
}

eq(8,\test\model\Find::find_count());
eq(8,\test\model\Find::find_count("value1"));
eq(7,\test\model\Find::find_count("value2"));
eq(5,\test\model\Find::find_count(Q::eq("order",2)));
eq(4,\test\model\Find::find_count(
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
eq(4,\test\model\Find::find_count($q));

$q = new Q();
$q->add(Q::ob(
		Q::b(Q::eq("order",2),Q::ob(Q::b(Q::eq("value1",'ccc',Q::IGNORE)),Q::b(Q::eq("value2",'AAA',Q::IGNORE)))),
		Q::b(Q::eq("order",4))
));
eq(4,\test\model\Find::find_count($q));


$paginator = new \org\rhaco\Paginator(1,2);
eq(1,sizeof($result = \test\model\Find::find_all(Q::neq("value1","abc"),$paginator)));
eq("ghi",$result[0]->value1());
eq(7,$paginator->total());

$i = 0;
foreach(\test\model\Find::find(
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
foreach(\test\model\Find::find() as $obj){
	eq($list[$i],$obj->value1());
	$i++;
}
foreach(\test\model\Find::find(Q::eq("value1","AbC",Q::IGNORE)) as $obj){
	eq("abc",$obj->value1());
}
foreach(\test\model\Find::find(Q::neq("value1","abc")) as $obj){
	neq("abc",$obj->value1());
}
try{
	\test\model\Find::find(Q::eq("value_error","abc"));
	fail();
}catch(\org\rhaco\store\db\exception\QueryException $e){
}


$i = 0;
$r = array("aaa","bbb","ccc");
foreach(\test\model\Find::find(Q::startswith("value1,value2",array("aa"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array("abc","jkl","ccc");
foreach(\test\model\Find::find(Q::endswith("value1,value2",array("c"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array("abc","bbb");
foreach(\test\model\Find::find(Q::contains("value1,value2",array("b"))) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(2,$i);

$i = 0;
$r = array("abc","jkl","ccc");
foreach(\test\model\Find::find(Q::endswith("value1,value2",array("C"),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
	$t[] = $obj->value1();
}
eq(3,$i);

$i = 0;
foreach(\test\model\Find::find(Q::in("value1",array("abc"))) as $obj){
	eq("abc",$obj->value1());
	$i++;
}
eq(1,$i);

foreach(\test\model\Find::find(Q::match("value1=abc")) as $obj){
	eq("abc",$obj->value1());
}
foreach(\test\model\Find::find(Q::match("value1=!abc")) as $obj){
	neq("abc",$obj->value1());
}
foreach(\test\model\Find::find(Q::match("abc")) as $obj){
	eq("abc",$obj->value1());
}
$i = 0;
$r = array("aaa","bbb","mno");
foreach(\test\model\Find::find(Q::neq("value1","ccc"),new \org\rhaco\Paginator(1,3),Q::order("-id")) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
foreach(\test\model\Find::find(Q::neq("value1","abc"),new \org\rhaco\Paginator(1,3),Q::order("id")) as $obj){
	eq("jkl",$obj->value1());
}
$i = 0;
$r = array("mno","aaa");
foreach(\test\model\Find::find(Q::neq("value1","ccc"),new \org\rhaco\Paginator(1,2),Q::order("order,-id")) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
$result = \test\model\Find::find_all(Q::match("AAA",Q::IGNORE));
eq(3,sizeof($result));

$result = \test\model\Find::find_all(Q::match("AA",Q::IGNORE));
eq(3,sizeof($result));

$result = \test\model\Find::find_all(Q::eq("value2",null));
eq(1,sizeof($result));
$result = \test\model\Find::find_all(Q::neq("value2",null));
eq(7,sizeof($result));

$result = \test\model\Find::find_all(Q::eq("updated",null));
eq(6,sizeof($result));
$result = \test\model\Find::find_all(Q::neq("updated",null));
eq(2,sizeof($result));
eq("2008/12/24 10:00:00",$result[0]->fm_updated());

$c = 0;
for($i=0;$i<10;$i++){
	$a = $b = array();
	foreach(\test\model\Find::find_all(Q::random_order()) as $o) $a[] = $o->id();
	foreach(\test\model\Find::find_all(Q::random_order()) as $o) $b[] = $o->id();
	if($a === $b) $c++;
}
neq(10,$c);


$result = \test\model\Find::find_all(Q::ob(
		Q::b(Q::eq("value1","abc"))
		,Q::b(Q::eq("value2","EDC"))
));
eq(2,sizeof($result));

eq("EDC",\test\model\Find::find_get(Q::eq("value1","jkl"))->value2());

$i = 0;
$r = array("abc","def","ghi","jkl");
foreach(\test\model\RefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(4,$i);

$i = 0;
$r = array("abc");
foreach(\test\model\RefRefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(1,$i);


$i = 0;
$r = array("abc","def","ghi","jkl");
foreach(\test\model\HasFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->parent()->value1());
	$i++;
}
eq(4,$i);


$result = \test\model\Find::find_all(Q::in("value1",\test\model\SubFind::find_sub("value")));
eq(4,sizeof($result));
$result = \test\model\Find::find_all(Q::in("value1",\test\model\SubFind::find_sub("value",Q::lt("order",3))));
eq(2,sizeof($result));
