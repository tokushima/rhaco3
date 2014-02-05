<?php

$yml = pre('
 		--- hoge
 		a: mapping
 		foo: bar
 		---
 		- a
 		- sequence
 		');
$obj1 = (object)array("header"=>"hoge","nodes"=>array("a"=>"mapping","foo"=>"bar"));
$obj2 = (object)array("header"=>"","nodes"=>array("a","sequence"));
$result = array($obj1,$obj2);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		---
		This: top level mapping
		is:
		- a
		- YAML
		- document
		');
$obj1 = (object)array("header"=>"","nodes"=>array("This"=>"top level mapping","is"=>array("a","YAML","document")));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		--- !recursive-sequence &001
		- * 001
		- * 001
		');
$obj1 = (object)array("header"=>"!recursive-sequence &001","nodes"=>array("* 001","* 001"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		a sequence:
		- one bourbon
		- one scotch
		- one beer
		');
$obj1 = (object)array("header"=>"","nodes"=>array("a sequence"=>array("one bourbon","one scotch","one beer")));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		a scalar key: a scalar value
		');
$obj1 = (object)array("header"=>"","nodes"=>array("a scalar key"=>"a scalar value"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		- a plain string
		- -42
		- 3.1415
		- 12:34
		- 123 this is an error
		');
$obj1 = (object)array("header"=>"","nodes"=>array("a plain string",-42,3.1415,"12:34","123 this is an error"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		- >
		This is a multiline scalar which begins on
		the next line. It is indicated by a single
		carat.
		');
$obj1 = (object)array("header"=>"","nodes"=>array("This is a multiline scalar which begins on the next line. It is indicated by a single carat."));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		- |
		QTY  DESC		 PRICE TOTAL
		===  ====		 ===== =====
		1  Foo Fighters  $19.95 $19.95
		2  Bar Belles	$29.95 $59.90
		');
$rtext = pre('
		QTY  DESC		 PRICE TOTAL
		===  ====		 ===== =====
		1  Foo Fighters  $19.95 $19.95
		2  Bar Belles	$29.95 $59.90
		');
$obj1 = (object)array("header"=>"","nodes"=>array($rtext));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		-
		name: Mark McGwire
		hr:   65
		avg:  0.278
		-
		name: Sammy Sosa
		hr:   63
		avg:  0.288
		');
$obj1 = (object)array("header"=>"","nodes"=>array(
		array("name"=>"Mark McGwire","hr"=>65,"avg"=>0.278),
		array("name"=>"Sammy Sosa","hr"=>63,"avg"=>0.288)));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		hr:  65	# Home runs
		avg: 0.278 # Batting average
		rbi: 147   # Runs Batted In
		');
$obj1 = (object)array("header"=>"","nodes"=>array("hr"=>65,"avg"=>0.278,"rbi"=>147));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));

$yml = pre('
		name: Mark McGwire
		accomplishment: >
		Mark set a major league
		home run record in 1998.
		stats: |
		65 Home Runs
		0.278 Batting Average
		');
$obj1 = (object)array("header"=>"","nodes"=>array(
		"name"=>"Mark McGwire",
		"accomplishment"=>"Mark set a major league home run record in 1998.",
		"stats"=>"65 Home Runs\n0.278 Batting Average"));
$result = array($obj1);
eq($result,\org\rhaco\lang\Yaml::decode($yml));
