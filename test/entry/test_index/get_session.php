<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->do_get(url('test_index::set_session'));

$b->do_get(url('test_index::get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());


$b = new \testman\Browser();
$b->do_get(url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',url('test_index::get_session'));
$b->do_get(url('test_index::set_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
eq(url('test_index::get_session'),$b->url());


$b = new \testman\Browser();
$b->do_get(url('test_index::get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',url('test_index::plain_noop'));
$b->do_get(url('test_index::set_session'));

$b->do_get(url('test_index::get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
