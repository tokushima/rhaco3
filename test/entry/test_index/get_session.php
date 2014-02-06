<?php
$b = b();
$b->do_get(test_map_url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->do_get(test_map_url('test_index::set_session'));

$b->do_get(test_map_url('test_index::get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());


$b = b();
$b->do_get(test_map_url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',test_map_url('test_index::get_session'));
$b->do_get(test_map_url('test_index::set_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
eq(test_map_url('test_index::get_session'),$b->url());


$b = b();
$b->do_get(test_map_url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',test_map_url('test_index::plain_noop'));
$b->do_get(test_map_url('test_index::set_session'));

$b->do_get(test_map_url('test_index::get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
