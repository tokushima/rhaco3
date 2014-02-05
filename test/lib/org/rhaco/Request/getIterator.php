<?php
$req = new \org\rhaco\Request();
$req->rm_vars();
$req->vars('abc',1);
$req->vars('def',2);
$req->vars('ghi',3);

$i = 0;
$keys = array('abc','def','ghi');
$values = array(1,2,3);
foreach($req as $k => $v){
	eq($keys[$i],$k);
	eq($values[$i],$v);
	$i++;
}
