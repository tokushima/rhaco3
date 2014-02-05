<?php
$t = new \org\rhaco\Template();
$src = pre('
		<rt:loop param="abc" counter="loop_counter" key="loop_key" var="loop_var">
{$loop_counter}: {$loop_key} => {$loop_var}
		</rt:loop>
		hoge
		');
$result = pre('
		1: A => 456
		2: B => 789
		3: C => 010
		4: D => 999
		hoge
		');
$t = new \org\rhaco\Template();
$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
eq($result,$t->get($src));

// multi
$t = new \org\rhaco\Template();
$src = pre('<rt:loop param="abc" var="a"><rt:loop param="abc" var="b">{$a}{$b}</rt:loop>-</rt:loop>');
$result = pre('1112-2122-');
$t->vars('abc',array(1,2));
eq($result,$t->get($src));

// evenodd
$t = new \org\rhaco\Template();
$src = pre('<rt:loop param="abc" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>');
$result = pre('1[odd]2[even]3[odd]4[even]5[odd]6[even]');
$t->vars('abc',array(1,2,3,4,5,6));
eq($result,$t->get($src));



