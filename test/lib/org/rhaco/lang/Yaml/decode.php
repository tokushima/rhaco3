<?php

$yml = <<< 'PRE'
--- hoge
a: mapping
foo: bar
---
- a
- sequence
PRE;
$obj1 = (object)array("header"=>"hoge","nodes"=>array("a"=>"mapping","foo"=>"bar"));
$obj2 = (object)array("header"=>"","nodes"=>array("a","sequence"));
$result = array($obj1,$obj2);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
---
This: top level mapping
is:
	- a
	- YAML
	- document
PRE;
$obj1 = (object)array("header"=>"","nodes"=>array("This"=>"top level mapping","is"=>array("a","YAML","document")));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
--- !recursive-sequence &001
- * 001
- * 001
PRE;
$obj1 = (object)array("header"=>"!recursive-sequence &001","nodes"=>array("* 001","* 001"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
a sequence:
	- one bourbon
	- one scotch
	- one beer
PRE;
$obj1 = (object)array("header"=>"","nodes"=>array("a sequence"=>array("one bourbon","one scotch","one beer")));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
a scalar key: a scalar value
PRE;
$obj1 = (object)array("header"=>"","nodes"=>array("a scalar key"=>"a scalar value"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
- a plain string
- -42
- 3.1415
- 12:34
- 123 this is an error
PRE;
$obj1 = (object)array("header"=>"","nodes"=>array("a plain string",-42,3.1415,"12:34","123 this is an error"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = <<< 'PRE'
- >
	This is a multiline scalar which begins on
	the next line. It is indicated by a single
	carat.
PRE;
$obj1 = (object)array("header"=>"","nodes"=>array("This is a multiline scalar which begins on the next line. It is indicated by a single carat."));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));
