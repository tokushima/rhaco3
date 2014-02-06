<?php

$src = '<rt:if param="abc">hoge</rt:if>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",true);
eq($result,$t->get($src));

$src = '<rt:if param="abc" value="xyz">hoge</rt:if>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc","xyz");
eq($result,$t->get($src));
	
$src = '<rt:if param="abc" value="1">hoge</rt:if>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",1);
eq($result,$t->get($src));

$src = '<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>';
$result = 'bb';
$t = new \org\rhaco\Template();
$t->vars("abc",1);
eq($result,$t->get($src));

$src = '<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>';
$result = 'aa';
$t = new \org\rhaco\Template();
$t->vars("abc",2);
eq($result,$t->get($src));

$src = '<rt:if param="abc" value="{$a}">bb<rt:else />aa</rt:if>';
$result = 'bb';
$t = new \org\rhaco\Template();
$t->vars("abc",2);
$t->vars("a",2);
eq($result,$t->get($src));

$src = '<rt:loop param="aaa" var="c"><rt:if param="{$c}" value="{$a}">A<rt:else />{$c}</rt:if></rt:loop>';
$result = '1A345';
$t = new \org\rhaco\Template();
$t->vars("abc",2);
$t->vars("a",2);
$t->vars('aaa',range(1,5));
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \org\rhaco\Template();
$t->vars("abc",array(1));
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \org\rhaco\Template();
$t->vars("abc",array());
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \org\rhaco\Template();
$t->vars("abc",true);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \org\rhaco\Template();
$t->vars("abc",false);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \org\rhaco\Template();
$t->vars("abc","a");
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \org\rhaco\Template();
$t->vars("abc","");
eq($result,$t->get($src));
	
$src = '<rt:if param="abc" value="-1">hoge</rt:if>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",-1);
eq($result,$t->get($src));
	
$src = '<rt:if param="abc" value="0">hoge</rt:if>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",0);
eq($result,$t->get($src));


$src = '<rt:notif param="abc">hoge</rt:notif>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",false);
eq($result,$t->get($src));

$src = '<rt:notif param="abc" value="0">hoge</rt:notif>';
$result = 'hoge';
$t = new \org\rhaco\Template();
$t->vars("abc",1);
eq($result,$t->get($src));



