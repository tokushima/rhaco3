<?php
$p = "<abc><def>111</def></abc>";
if(eq(true,\org\rhaco\Xml::set($x,$p))){
	eq("abc",$x->name());
}
$p = "<abc><def>111</def></abc>";
if(eq(true,\org\rhaco\Xml::set($x,$p,"def"))){
	eq("def",$x->name());
	eq(111,$x->value());
}
$p = "aaaa";
eq(false,\org\rhaco\Xml::set($x,$p));
$p = "<abc>sss</abc>";
eq(false,\org\rhaco\Xml::set($x,$p,"def"));
$p = "<abc>sss</a>";
if(eq(true,\org\rhaco\Xml::set($x,$p))){
	eq("<abc />",$x->get());
}
$p = "<abc>0</abc>";
if(eq(true,\org\rhaco\Xml::set($x,$p))){
	eq("abc",$x->name());
	eq("0",$x->value());
}