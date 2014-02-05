<?php
$variable = array(1,2,3);
eq("[1,2,3]",\org\rhaco\lang\Json::encode($variable));
$variable = "ABC";
eq("\"ABC\"",\org\rhaco\lang\Json::encode($variable));
$variable = 10;
eq(10,\org\rhaco\lang\Json::encode($variable));
$variable = 10.123;
eq(10.123,\org\rhaco\lang\Json::encode($variable));
$variable = true;
eq("true",\org\rhaco\lang\Json::encode($variable));

$variable = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
eq('["foo","bar",[1,2,"baz"],[3,[4]]]',\org\rhaco\lang\Json::encode($variable));

$variable = array("foo"=>"bar",'baz'=>1,3=>4);
eq('{"foo":"bar","baz":1,"3":4}',\org\rhaco\lang\Json::encode($variable));

$variable = array("type"=>"hoge","name"=>"fuga");
eq('{"type":"hoge","name":"fuga"}',\org\rhaco\lang\Json::encode($variable));

# array
$variable = array("name"=>"hoge","type"=>"fuga");
eq('{"name":"hoge","type":"fuga"}',\org\rhaco\lang\Json::encode($variable));

$variable = array("aa","name"=>"hoge","type"=>"fuga");
eq('{"0":"aa","name":"hoge","type":"fuga"}',\org\rhaco\lang\Json::encode($variable));

$variable = array("aa","hoge","fuga");
eq('["aa","hoge","fuga"]',\org\rhaco\lang\Json::encode($variable));

$variable = array("aa","hoge","fuga");
eq('["aa","hoge","fuga"]',\org\rhaco\lang\Json::encode($variable));

$variable = array(array("aa"=>1),array("aa"=>2),array("aa"=>3));
eq('[{"aa":1},{"aa":2},{"aa":3}]',\org\rhaco\lang\Json::encode($variable));
