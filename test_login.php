<?php
include_once(__DIR__.'/bootstrap.php');

\org\rhaco\Flow::out(array(
'modules'=>'test.flow.module.CoreTestLogin',
'secure'=>true,
'patterns'=>array(
	'login_url'=>array('name'=>'login','action'=>'test.CoreTestLoginFlow::do_login'),
	'logout_url'=>array('name'=>'logout','action'=>'test.CoreTestLoginFlow::do_logout'),
	'aaa'=>array('name'=>'aaa','action'=>'test.CoreTestLoginFlow::aaa'),
		
	'secure'=>array('name'=>'secure','template'=>'secure.html'),
)));



