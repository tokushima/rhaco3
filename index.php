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
,'nomatch_redirect'=>'index'
,'patterns'=>array(
	''=>array('name'=>'index','template'=>'hoge.html')
	,'model'=>array('action'=>'test.flow.Model')
	,'board'=>array('action'=>'org.rhaco.flow.parts.Board')
	,'blog'=>array('action'=>'org.rhaco.flow.parts.Blog')
	,'dev'=>array('action'=>'org.rhaco.flow.parts.Developer','mode'=>'local,dev','modules'=>'org.rhaco.flow.module.SimpleAuth')
	,'dev/do_find/(.+)/xml'=>array('action'=>'org.rhaco.flow.parts.Developer::do_find')
	,'dev/do_detail/(.+)/xml'=>array('action'=>'org.rhaco.flow.parts.Developer::do_detail')
	
	,'fb'=>array('action'=>'org.rhaco.flow.parts.Sample','modules'=>'org.rhaco.service.flow.module.FacebookSimpleAuth')
	,'openid'=>array('action'=>'org.rhaco.flow.parts.Sample','modules'=>'org.rhaco.service.flow.module.OpenidSimpleAuth')	
	
	,'post'=>array('action'=>'org.rhaco.flow.parts.Sample::post')
	,'get'=>array('action'=>'org.rhaco.flow.parts.Sample::get')
)));

