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
	,'dev'=>array('action'=>'org.rhaco.flow.parts.Developer'
				,'modules'=>array(
//					'org.rhaco.flow.module.LoginRequired'
//					,'org.rhaco.flow.module.SimpleAuth'
				))
)));

