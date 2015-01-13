<?php
$b = new \testman\Browser();

$b->do_get(url('test_index::sample_flow_exception_throw'));
eq('ERROR',$b->body());

