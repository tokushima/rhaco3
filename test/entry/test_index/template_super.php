<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::template_super_a'));
eq('abcd',$b->body());

$b->do_get(url('test_index::template_super_b'));
eq('xc',$b->body());
