<?php
//get
eq("req=123",\org\rhaco\net\Query::get("123","req"));
eq("req[0]=123",\org\rhaco\net\Query::get(array(123),"req"));
eq("req[0]=123&req[1]=456&req[2]=789",\org\rhaco\net\Query::get(array(123,456,789),"req"));
eq("",\org\rhaco\net\Query::get(array(123,456,789)));
eq("abc=123&def=456&ghi=789",\org\rhaco\net\Query::get(array("abc"=>123,"def"=>456,"ghi"=>789)));
eq("req[0]=123&req[1]=&req[2]=789",\org\rhaco\net\Query::get(array(123,null,789),"req"));
eq("req[0]=123&req[2]=789",\org\rhaco\net\Query::get(array(123,null,789),"req",false));
	
eq("req=123&req=789",\org\rhaco\net\Query::get(array(123,null,789),"req",false,false));
eq("label=123&label=&label=789",\org\rhaco\net\Query::get(array("label"=>array(123,null,789)),null,true,false));

$obj = new \test\model\QueryModel();
$obj->id = 100;
$obj->value = "hogehoge";
eq("req[id]=100&req[value]=hogehoge&req[test]=TEST",\org\rhaco\net\Query::get($obj,"req"));
eq("id=100&value=hogehoge&test=TEST",\org\rhaco\net\Query::get($obj));




//expand_vars
$array = array();
eq(array(array("abc",123),array("def",456)),\org\rhaco\net\Query::expand_vars($array,array("abc"=>"123","def"=>456)));
eq(array(array("abc",123),array("def",456)),$array);
	
$array = array();
eq(array(array("hoge[abc]",123),array("hoge[def]",456)),\org\rhaco\net\Query::expand_vars($array,array("abc"=>"123","def"=>456),'hoge'));
eq(array(array("hoge[abc]",123),array("hoge[def]",456)),$array);
	
$array = array();
eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),\org\rhaco\net\Query::expand_vars($array,array("abc"=>"123","def"=>array("ABC"=>123,"DEF"=>456)),'hoge'));
eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),$array);
	
$obj = new \test\model\QueryModel();
$obj->id = 100;
$obj->value = "hogehoge";
	
$array = array();
eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),\org\rhaco\net\Query::expand_vars($array,$obj,"req"));
eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),$array);

