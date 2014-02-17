<?php
$x = new \org\rhaco\Xml("test",123);
eq("<test>123</test>",$x->get());
$x = new \org\rhaco\Xml("test",new \org\rhaco\Xml("hoge","AAA"));
eq("<test><hoge>AAA</hoge></test>",$x->get());
$x = new \org\rhaco\Xml("test");
eq("<test />",$x->get());
$x = new \org\rhaco\Xml("test");
$x->close_empty(false);
eq("<test></test>",$x->get());
$x = new \org\rhaco\Xml("test");
$x->attr("abc",123);
$x->attr("def",456);
eq('<test abc="123" def="456" />',$x->get());

