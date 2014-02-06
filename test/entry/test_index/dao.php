<?php
$b = b();
$b->do_post(test_map_url('test_index::dao/insert'));
$b->do_post(test_map_url('test_index::dao/get'));
meq('<string>abcdefg</string><text />',$b->body());
eq(200,$b->status());
meq('<string>abcdefg</string><text />',$b->body());

$b = b();
$b->do_post(test_map_url('test_index::dao/update'));
$b->do_post(test_map_url('test_index::dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = b();
$b->do_post(test_map_url('test_index::dao/delete'));

$b = b();
$b->do_post(test_map_url('test_index::dao/insert'));
$b->do_post(test_map_url('test_index::dao/get'));
meq('<string>abcdefg</string><text />',$b->body());

$b = b();
$b->do_post(test_map_url('test_index::dao/update'));
$b->do_post(test_map_url('test_index::dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = b();
$b->do_post(test_map_url('test_index::dao/delete'));
eq('<result />',$b->body());

