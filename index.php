<?php
include_once('rhaco3.php');
/**
 * @name rhaco3 repository
 * @summary rhaco3のライブラリ群
 * さまざまなライブラリ
 * これまでもこれからも
 */
$flow = new \org\rhaco\Flow();
$flow->output(array(''
,nomatch_redirect=>'/'
//,error_redirect=>'/'
,modules=>array(
	'org.rhaco.flow.module.TwitterBootstrapPagination',
)
,patterns=>array(
	''=>array(action=>'org.rhaco.flow.parts.Developer')
)));
