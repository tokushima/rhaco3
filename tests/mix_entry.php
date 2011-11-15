<?php

$b = b();

$b->do_get(test_map_url('test_index::sample_flow_exception_throw'));
eq('ERROR',$b->body());

$b->do_get(test_map_url('test_login::aaa'));
eq(200,$b->status());
eq(test_map_url('test_login::login'),$b->url());
eq('<result />',$b->body());

