<?php
$t = new \org\rhaco\Template();
$src = <<< 'PRE'
		<rt:loop param="abc" counter="loop_counter" key="loop_key" var="loop_var">
{$loop_counter}: {$loop_key} => {$loop_var} hoge
		</rt:loop>
PRE;
$result =  <<< 'PRE'
		1: A => 456 hoge
		2: B => 789 hoge
		3: C => 010 hoge
		4: D => 999 hoge
		
PRE;
$t = new \org\rhaco\Template();
$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
eq($result,$t->get($src));

// multi
$t = new \org\rhaco\Template();
$src = '<rt:loop param="abc" var="a"><rt:loop param="abc" var="b">{$a}{$b}</rt:loop>-</rt:loop>';
$result = '1112-2122-';
$t->vars('abc',array(1,2));
eq($result,$t->get($src));

// evenodd
$t = new \org\rhaco\Template();
$src = '<rt:loop param="abc" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>';
$result = '1[odd]2[even]3[odd]4[even]5[odd]6[even]';
$t->vars('abc',array(1,2,3,4,5,6));
eq($result,$t->get($src));



