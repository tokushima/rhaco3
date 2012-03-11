<?php
include_once('rhaco3.php');
/**
 * @name rhaco3 repository
 * @summary rhaco3のライブラリ群
 * ああああああああああ
 * いいいいいいいいいい
 */
$flow = new \org\rhaco\Flow();
$flow->output(array(''
,'nomatch_redirect'=>'/'
,'patterns'=>array(
	''=>array('template'=>'hoge.html')
	,'model'=>array('action'=>'test.flow.Model')
	,'dev'=>array('action'=>'org.rhaco.flow.parts.Developer','mode'=>'local,dev')
	,'dev/do_find/(.+)/xml'=>array('action'=>'org.rhaco.flow.parts.Developer::do_find')
	,'dev/do_detail/(.+)/xml'=>array('action'=>'org.rhaco.flow.parts.Developer::do_detail')
)));

