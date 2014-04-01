<?php
include_once('bootstrap.php');

$flow = new \org\rhaco\Flow();
$flow->output(array(
	'patterns'=>array(
		''=>array('template'=>'index.html'),
		'dt'=>array('action'=>'org.rhaco.Dt'),
	)
));

