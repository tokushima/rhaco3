<?php
$b = b();

$b->do_get(test_map_url('after'));
eq(200,$b->status());
eq(test_map_url('after_to'),$b->url());

$b->do_get(test_map_url('after_arg1'));
eq(200,$b->status());
eq(test_map_url('after_to_arg1','ABC'),$b->url());

$b->do_get(test_map_url('after_arg2'));
eq(200,$b->status());
eq(test_map_url('after_to_arg2','ABC','DEF'),$b->url());



$b->do_get(test_map_url('post_after'));
eq(200,$b->status());
eq(test_map_url('post_after'),$b->url());

$b->do_post(test_map_url('post_after'));
eq(200,$b->status());
eq(test_map_url('post_after_to'),$b->url());

$b->do_post(test_map_url('post_after_arg1'));
eq(200,$b->status());
eq(test_map_url('post_after_to_arg1','ABC'),$b->url());

$b->do_post(test_map_url('post_after_arg2'));
eq(200,$b->status());
eq(test_map_url('post_after_to_arg2','ABC','DEF'),$b->url());
