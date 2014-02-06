<?php
$b = b();
$b->do_get(test_map_url('test_index::template_super_a'));
eq('abcd',$b->body());

$b->do_get(test_map_url('test_index::template_super_b'));
eq('xc',$b->body());
