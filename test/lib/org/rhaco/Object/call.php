<?php
$hoge = new \test\model\ObjectCall();

eq(null,$hoge->aaa());
eq(false,$hoge->is_aaa());
$hoge->aaa("123");
eq(123,$hoge->aaa());
eq(true,$hoge->is_aaa());
eq(array(123),$hoge->ar_aaa());
$hoge->rm_aaa();
eq(false,$hoge->is_aaa());
eq(null,$hoge->aaa());

eq(array(),$hoge->bbb());
$hoge->bbb("123");
eq(array(123),$hoge->bbb());
$hoge->bbb(456);
eq(array(123,456),$hoge->bbb());
eq(456,$hoge->in_bbb(1));
eq("hoge",$hoge->in_bbb(5,"hoge"));
$hoge->bbb(789);
$hoge->bbb(10);
eq(array(123,456,789,10),$hoge->bbb());
eq(array(1=>456,2=>789),$hoge->ar_bbb(1,2));
eq(array(1=>456,2=>789,3=>10),$hoge->ar_bbb(1));
$hoge->rm_bbb();
eq(array(),$hoge->bbb());

eq(array(),$hoge->ccc());
eq(false,$hoge->is_ccc());
$hoge->ccc("AaA");
eq(array("AaA"=>"AaA"),$hoge->ccc());
eq(true,$hoge->is_ccc());
eq(true,$hoge->is_ccc("AaA"));
eq(false,$hoge->is_ccc("bbb"));
$hoge->ccc("bbb");
eq(array("AaA"=>"AaA","bbb"=>"bbb"),$hoge->ccc());
$hoge->ccc(123);
eq(array("AaA"=>"AaA","bbb"=>"bbb","123"=>"123"),$hoge->ccc());
$hoge->rm_ccc("bbb");
eq(array("AaA"=>"AaA","123"=>"123"),$hoge->ccc());
$hoge->ccc("ddd");
eq(array("AaA"=>"AaA","123"=>"123","ddd"=>"ddd"),$hoge->ccc());
eq(array("123"=>"123"),$hoge->ar_ccc(1,1));
$hoge->rm_ccc("AaA","ddd");
eq(array("123"=>"123"),$hoge->ccc());
$hoge->rm_ccc();
eq(array(),$hoge->ccc());
$hoge->ccc("abc","def");
eq(array("abc"=>"def"),$hoge->ccc());

eq(null,$hoge->ddd());
$hoge->ddd("hoge","fuga");
eq("hogefuga",$hoge->ddd());

$hoge->eee("1976/10/04");
eq("1976/10/04",date("Y/m/d",$hoge->eee()));
eq("1976/10/05 00:00:00",$hoge->nextDay());

try{
	$hoge->eee("ABC");
	fail();
}catch(\InvalidArgumentException $e){
}

$hoge->eee("000/00/00 00:00:00");
eq(null,$hoge->eee());

$hoge->eee("1969:12:31 17:59:59");
eq('-54001',$hoge->eee());

$hoge->eee("-54001");
eq('-54001',$hoge->eee());

$hoge->eee(null);
eq(null,$hoge->eee());

eq(array("BTbl","Acol"),$hoge->cn_fff());

eq("hoge",$hoge->ggg());
try{
	$hoge->ggg("fuga");
	fail();
}catch(\InvalidArgumentException $e){
}
$hoge->hhh(true);
eq(true,$hoge->hhh());
$hoge->hhh(false);
eq(false,$hoge->hhh());
try{
	$hoge->hhh("hoge");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$hoge->iii();
	fail();
}catch(\ErrorException $e){
}
