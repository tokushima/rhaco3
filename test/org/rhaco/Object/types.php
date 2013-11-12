<?php
$obj = new \test\model\ObjectTypes();

eq(false,$obj->is_aa());
$obj->aa("hoge");
eq(true,$obj->is_aa());
$obj->aa("");
eq(null,$obj->aa());

eq(false,$obj->is_aaa());
$obj->aaa("hoge");
eq(true,$obj->is_aaa());
eq("[ABChoge]",$obj->aaa());
$obj->rm_aaa(null);
eq(false,$obj->is_aaa());

eq(false,$obj->is_bb());
$obj->bb("hoge");
eq("hoge",$obj->bb());
eq(true,$obj->is_bb());
$obj->bb("");
eq(false,$obj->is_bb());
$obj->bb("");
eq("",$obj->bb());
$obj->bb(null);
eq(null,$obj->bb());
$obj->bb("aaa\nbbb\nccc\n");
eq("aaabbbccc",$obj->bb());

eq(false,$obj->is_pp());
$obj->pp("hoge");
eq("hoge",$obj->pp());
eq(true,$obj->is_pp());
$obj->pp("");
eq(false,$obj->is_pp());
$obj->pp("");
eq("",$obj->pp());
$obj->pp(null);
eq(null,$obj->pp());

eq(false,$obj->is_cc());
$obj->cc(1);
eq(true,$obj->is_cc());
$obj->cc(0);
eq(true,$obj->is_cc());
$obj->cc("");
eq(null,$obj->cc());

eq(false,$obj->is_dd());
$obj->dd(1);
eq(true,$obj->is_dd());
$obj->dd(0);
eq(true,$obj->is_dd());
$obj->dd(-1.2);
eq(-1.2,$obj->dd());

eq(false,$obj->is_ee());
$obj->ee(true);
eq(true,$obj->is_ee());
$obj->ee(false);
eq(false,$obj->is_ee());

eq(false,$obj->is_ff());
$obj->ff("2009/04/27 12:00:00");
eq(true,$obj->is_ff());

eq(false,$obj->is_ll());
$obj->ll("2009/04/27");
eq(true,$obj->is_ll());
	
eq(false,$obj->is_gg());
$obj->gg("12:00:00");
eq(true,$obj->is_gg());
eq(43200,$obj->gg());
$obj->gg("12:00");
eq(720,$obj->gg());
eq("12:00",$obj->fm_gg());

$obj->gg("12:00.345");
eq(720.345,$obj->gg());
eq("12:00.345",$obj->fm_gg());
try{
	$obj->gg("1:2:3:4");
	fail();
}catch(\InvalidArgumentException $e){
}
$obj->gg("20時40分50秒");
eq("20:40:50",$obj->fm_gg());

eq(false,$obj->is_hh());
$obj->hh("abc");
eq(true,$obj->is_hh());

eq(false,$obj->is_ii());
eq(false,$obj->is_ii("hoge"));
$obj->ii("hoge","abc");
eq(true,$obj->is_ii());
eq(true,$obj->is_ii("hoge"));
$obj->ii(array("A"=>"def","B"=>"ghi"));
eq(true,$obj->is_ii("A"));
eq(true,$obj->is_ii("B"));
eq("ghi",$obj->in_ii("B"));
$obj->rm_ii("A","B");
eq(null,$obj->in_ii("A"));
eq(null,$obj->in_ii("C"));
eq(null,$obj->rm_ii("C"));
eq(true,$obj->is_ii());
$obj->rm_ii();
eq(false,$obj->is_ii());

eq(false,$obj->is_jj());
eq(false,$obj->is_jj(0));
$obj->jj("abc");
eq(true,$obj->is_jj(0));
$obj->jj("def");
$obj->jj("ghi");
eq("def",$obj->in_jj(1));
eq(true,$obj->is_jj(1));
eq(true,$obj->is_jj(2));

try{
	$obj->jj(array("jkl","mno"));
	fail();
}catch(InvalidArgumentException $e){
}
$obj->kk("Abc@example.com");
$obj->kk(" Abc@example.com ");
eq("Abc@example.com",$obj->kk());
$obj->kk("aaa.bbb.ccc@example.com");
$obj->kk("aaa.bbb.ccc@example.aa.bb.com");

try{
	$obj->kk("aaa..bbb.ccc@example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk("aaa.bbb.ccc.@example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk("aaa.bbb.ccc@example.c");
	fail();
}catch(\InvalidArgumentException $e){
}

$obj->kk("123@example.com");
$obj->kk("user+mailbox/department=shipping@example.com");
$obj->kk("!#$%&'*+-/=?^_`.{|}~@example.com");

try{
	$obj->kk("Abc.@example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk("Abc..123@example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk(".Abc@example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk("Abc@.example.com");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->kk("Abc@example.com.");
	fail();
}catch(\InvalidArgumentException $e){
}
eq(null,$obj->nn());

try{
	$obj->nn("1004");
	fail();
}catch(\InvalidArgumentException $e){
}
$obj->nn("123451004");
eq(123451004,$obj->nn());
eq("12345",$obj->fm_nn("Y"));
$obj->nn("91004");
eq(91004,$obj->nn());
$obj->nn("20091004");
eq(20091004,$obj->nn());
$obj->nn("2009/10/04");
eq(20091004,$obj->nn());
$obj->nn("2009/10/4");
eq(20091004,$obj->nn());
$obj->nn("2009/1/4");
eq(20090104,$obj->nn());
$obj->nn("1900/1/4");
eq(19000104,$obj->nn());
$obj->nn("645 1 4");
eq(6450104,$obj->nn());
$obj->nn("645年1月4日");
eq(6450104,$obj->nn());
eq("645/01/04",$obj->fm_nn());
eq("645",$obj->fm_nn("Y"));
eq("6450104",$obj->fm_nn("Ymd"));
eq("645年01月04日",$obj->fm_nn("Y年m月d日"));
$obj->nn("1981-02-04");
eq(19810204,$obj->nn());

eq(false,$obj->is_mm());
$obj->mm("abc123_");
eq(true,$obj->is_mm());
try{
	$obj->mm("/abc");
	fail();
}catch(\InvalidArgumentException $e){
}
eq(false,$obj->is_oo());
$obj->oo(0);
eq(true,$obj->is_oo());
$obj->oo(123);
eq(123,$obj->oo());
$obj->oo("456");
eq(456,$obj->oo());
$obj->oo(-123);
eq(-123,$obj->oo());
	
try{
	$obj->oo("123F");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->oo(123.45);
	fail();
}catch(\InvalidArgumentException $e){
}
$obj->oo("123.0");

	
try{
	$obj->oo("123.000000001");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->oo(123.000000001);
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->oo("123.0000000001");
	fail();
}catch(\InvalidArgumentException $e){
}
try{
	$obj->oo(123.0000000001);
	fail();
}catch(\InvalidArgumentException $e){
}
$obj->oo(123.0);

$obj->qq(2);
eq(2,$obj->qq());
$obj->qq(3.123);
eq(3.12,$obj->qq());
$obj->qq(123.554);
eq(123.55,$obj->qq());
$obj->qq(123.555);
eq(123.55,$obj->qq());
$obj->qq(123.556);
eq(123.55,$obj->qq());
$obj->qq(0);
eq(0,$obj->qq());
$obj->qq(123456789.01);
eq(123456789.01,$obj->qq());
$obj->qq(123456789.1);
eq(123456789.1,$obj->qq());


