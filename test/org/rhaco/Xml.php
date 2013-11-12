<?php
$obj = new \test\model\XmlModel();

$self = new \org\rhaco\Xml('abc',$obj);
eq('<abc><aaa>hoge</aaa><ccc>123</ccc></abc>',$self->get());

$e = explode('\\',get_class($obj));
$n = array_pop($e);

$obj1 = clone($obj);
$obj2 = clone($obj);
$obj3 = clone($obj);
$obj2->ccc(456);
$obj3->ccc(789);
$arr = array($obj1,$obj2,$obj3);
$self = new \org\rhaco\Xml('abc',$arr);
eq(
		sprintf('<abc>'
				.'<%s><aaa>hoge</aaa><ccc>123</ccc></%s>'
				.'<%s><aaa>hoge</aaa><ccc>456</ccc></%s>'
				.'<%s><aaa>hoge</aaa><ccc>789</ccc></%s>'
				.'</abc>',
				$n,$n,$n,$n,$n,$n
		),$self->get());
