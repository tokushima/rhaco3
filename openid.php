<?php
include_once('rhaco3.php');

$flow = new \org\rhaco\Flow();
$flow->output(array(''
,'patterns'=>array(
	''=>array('action'=>'org.rhaco.service.OpenID')
	,'dev'=>array('action'=>'org.rhaco.flow.parts.Developer')
)));

