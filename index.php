<?php
include_once('rhaco3.php');
/**
 * @name lib
 * @summary 
 */
$flow = new \org\rhaco\Flow();
$flow->output(array(''
,modules=>'test.flow.module.CoreTestLogin'
,patterns=>array(
	''=>array(action=>'org.rhaco.flow.parts.Developer')
)));
