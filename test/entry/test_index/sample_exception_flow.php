<?php
$b = b();

$b->do_get(test_map_url('test_index::sample_flow_exception_throw'));
eq('ERROR',$b->body());

