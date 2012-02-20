<?php
include_once('rhaco3.php');

$flow = new \org\rhaco\Flow();
$flow->output(array(''
,'modules'=>array(
	'org.rhaco.flow.module.Dao'
)
,'patterns'=>array(
	'insert'=>array('name'=>'insert','action'=>'test.flow.Model::insert')
	,'update'=>array('name'=>'update','action'=>'test.flow.Model::update')
	,'delete'=>array('name'=>'delete','action'=>'test.flow.Model::delete')
	,'get'=>array('name'=>'get','action'=>'test.flow.Model::get')
)));
/***
 * $b = b();
 * $b->do_post(test_map_url('insert'));
 * $b->do_post(test_map_url('get'));
 * eq(200,$b->status());
 * meq('<string>abcdefg</string><text />',$b->body());
 * 
 * $b = b();
 * $b->do_post(test_map_url('update'));
 * $b->do_post(test_map_url('get'));
 * meq('<string>abcdefg</string><text>xyz</text>',$b->body());
 * 
 * $b = b();
 * $b->do_post(test_map_url('delete'));
 * 
 * $b = b();
 * $b->do_post(test_map_url('insert'));
 * $b->do_post(test_map_url('get'));
 * meq('<string>abcdefg</string><text />',$b->body());
 * 
 * $b = b();
 * $b->do_post(test_map_url('update'));
 * $b->do_post(test_map_url('get'));
 * meq('<string>abcdefg</string><text>xyz</text>',$b->body());
 * 
 * $b = b();
 * $b->do_post(test_map_url('delete'));
 * eq('<result />',$b->body());
 */
