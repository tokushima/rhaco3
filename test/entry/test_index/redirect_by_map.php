<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::redirect_by_map_method_a'));
eq(200,$b->status());
eq('REDIRECT_A',$b->body());

$b->do_get(url('test_index::redirect_by_map_method_b'));
eq(200,$b->status());
eq('REDIRECT_B',$b->body());

$b->do_get(url('test_index::redirect_by_map_method_c'));
eq(200,$b->status());
eq('REDIRECT_C',$b->body());
