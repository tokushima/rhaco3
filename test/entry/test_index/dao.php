<?php
$b = new \testman\Browser();
$b->do_post(url('test_index::dao/insert'));
$b->do_post(url('test_index::dao/get'));
meq('<string>abcdefg</string><text />',$b->body());
eq(200,$b->status());
meq('<string>abcdefg</string><text />',$b->body());

$b = new \testman\Browser();
$b->do_post(url('test_index::dao/update'));
$b->do_post(url('test_index::dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = new \testman\Browser();
$b->do_post(url('test_index::dao/delete'));

$b = new \testman\Browser();
$b->do_post(url('test_index::dao/insert'));
$b->do_post(url('test_index::dao/get'));
meq('<string>abcdefg</string><text />',$b->body());

$b = new \testman\Browser();
$b->do_post(url('test_index::dao/update'));
$b->do_post(url('test_index::dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = new \testman\Browser();
$b->do_post(url('test_index::dao/delete'));
eq('<result />',$b->body());

