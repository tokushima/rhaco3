<?php
include_once('rhaco3.php');

\org\rhaco\Flow::out(array(''
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
	# error
	$b = b();
	$b->vars('integer','123F');
	$b->do_post(test_map_url('insert'));
	eq(200,$b->status());
	eq('<error><message group="integer" class="org.rhaco.store.db.exception.InvalidArgumentException" type="InvalidArgumentException">integer must be an integer</message></error>',$b->body());
 */
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
