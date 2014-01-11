<?php
$b = b();

$b->do_get(test_map_url('test_index::after'));
eq(200,$b->status());
eq(test_map_url('test_index::after_to'),$b->url());

$b->do_get(test_map_url('test_index::after_arg1'));
eq(200,$b->status());
eq(test_map_url('test_index::after_to_arg1','ABC'),$b->url());

$b->do_get(test_map_url('test_index::after_arg2'));
eq(200,$b->status());
eq(test_map_url('test_index::after_to_arg2','ABC','DEF'),$b->url());



$b->do_get(test_map_url('test_index::post_after'));
eq(200,$b->status());
eq(test_map_url('test_index::post_after'),$b->url());

$b->do_post(test_map_url('test_index::post_after'));
eq(200,$b->status());
eq(test_map_url('test_index::post_after_to'),$b->url());

$b->do_post(test_map_url('test_index::post_after_arg1'));
eq(200,$b->status());
eq(test_map_url('test_index::post_after_to_arg1','ABC'),$b->url());

$b->do_post(test_map_url('test_index::post_after_arg2'));
eq(200,$b->status());
eq(test_map_url('test_index::post_after_to_arg2','ABC','DEF'),$b->url());
