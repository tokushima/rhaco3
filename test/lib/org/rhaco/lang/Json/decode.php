<?php
$variable = "ABC";
eq($variable,\org\rhaco\lang\Json::decode('"ABC"'));
$variable = 10;
eq($variable,\org\rhaco\lang\Json::decode(10));
$variable = 10.123;
eq($variable,\org\rhaco\lang\Json::decode(10.123));
$variable = true;
eq($variable,\org\rhaco\lang\Json::decode("true"));
$variable = false;
eq($variable,\org\rhaco\lang\Json::decode("false"));
$variable = null;
eq($variable,\org\rhaco\lang\Json::decode("null"));
$variable = array(1,2,3);
eq($variable,\org\rhaco\lang\Json::decode("[1,2,3]"));
$variable = array(1,2,array(9,8,7));
eq($variable,\org\rhaco\lang\Json::decode("[1,2,[9,8,7]]"));
$variable = array(1,2,array(9,array(10,11),7));
eq($variable,\org\rhaco\lang\Json::decode("[1,2,[9,[10,11],7]]"));
	
$variable = array("A"=>"a","B"=>"b","C"=>"c");
eq($variable,\org\rhaco\lang\Json::decode('{"A":"a","B":"b","C":"c"}'));
$variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>"f","G"=>"g"));
eq($variable,\org\rhaco\lang\Json::decode('{"A":"a","B":"b","C":{"E":"e","F":"f","G":"g"}}'));
$variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>array("H"=>"h","I"=>"i"),"G"=>"g"));
eq($variable,\org\rhaco\lang\Json::decode('{"A":"a","B":"b","C":{"E":"e","F":{"H":"h","I":"i"},"G":"g"}}'));
	
$variable = array("A"=>"a","B"=>array(1,2,3),"C"=>"c");
eq($variable,\org\rhaco\lang\Json::decode('{"A":"a","B":[1,2,3],"C":"c"}'));
$variable = array("A"=>"a","B"=>array(1,array("C"=>"c","D"=>"d"),3),"C"=>"c");
eq($variable,\org\rhaco\lang\Json::decode('{"A":"a","B":[1,{"C":"c","D":"d"},3],"C":"c"}'));
	
$variable = array(array("a"=>1,"b"=>array("a","b",1)),array(null,false,true));
eq($variable,\org\rhaco\lang\Json::decode('[ {"a" : 1, "b" : ["a", "b", 1] }, [ null, false, true ] ]'));
	
eq(null,\org\rhaco\lang\Json::decode("[1,2,3,]"));
eq(null,\org\rhaco\lang\Json::decode("[1,2,3,,,]"));
	
if(extension_loaded("json")) eq(null,json_decode("[1,[1,2,],3]"));
eq(array(1,null,3),\org\rhaco\lang\Json::decode("[1,[1,2,],3]"));
eq(null,\org\rhaco\lang\Json::decode('{"A":"a","B":"b","C":"c",}'));
	
eq(array("hoge"=>"123,456"),\org\rhaco\lang\Json::decode('{"hoge":"123,456"}'));

// quote
eq(array("hoge"=>'123,"456'),\org\rhaco\lang\Json::decode('{"hoge":"123,\\"456"}'));
eq(array("hoge"=>"123,'456"),\org\rhaco\lang\Json::decode('{"hoge":"123,\'456"}'));
eq(array("hoge"=>'123,\\"456'),\org\rhaco\lang\Json::decode('{"hoge":"123,\\\\\\"456"}'));
eq(array("hoge"=>"123,\\'456"),\org\rhaco\lang\Json::decode('{"hoge":"123,\\\\\'456"}'));

// escape
eq(array("hoge"=>"\\"),\org\rhaco\lang\Json::decode('{"hoge":"\\\\"}'));
eq(array("hoge"=>"a\\"),\org\rhaco\lang\Json::decode('{"hoge":"a\\\\"}'));
eq(array("hoge"=>"t\\t"),\org\rhaco\lang\Json::decode('{"hoge":"t\\\\t"}'));
eq(array("hoge"=>"\tA"),\org\rhaco\lang\Json::decode('{"hoge":"\\tA"}'));

// value_error
try{
\org\rhaco\lang\Json::decode("{'hoge':'123,456'}");
fail();
}catch(\InvalidArgumentException $e){
}
