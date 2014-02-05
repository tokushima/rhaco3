<?php
$x = new \org\rhaco\Xml("abc","<def>123</def><def>456</def><def>789</def>");
$r = array(123,456,789);
$i = 0;
foreach($x->in("def") as $d){
	eq($r[$i],$d->value());
	$i++;
}
$x = new \org\rhaco\Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
$r = array(123,456,789);
$i = 0;
foreach($x->in("def") as $d){
	eq($r[$i],$d->value());
	$i++;
}
$x = new \org\rhaco\Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
$r = array(456,789);
$i = 0;
foreach($x->in("def",1) as $d){
	eq($r[$i],$d->value());
	$i++;
}
$x = new \org\rhaco\Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
$r = array(456);
$i = 0;
foreach($x->in("def",1,1) as $d){
	eq($r[$i],$d->value());
	$i++;
}
$x = new \org\rhaco\Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
$i = 0;
foreach($x->in(array("def","abc")) as $d){
	$i++;
}
eq(6,$i);
$x = new \org\rhaco\Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><ghi>000</ghi><abc>GHI</abc><def>789</def>");
$i = 0;
foreach($x->in(array("def","abc")) as $d){
	$i++;
}
eq(6,$i);