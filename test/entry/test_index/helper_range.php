<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::helper_range'));
meq('A1234A',$b->body());
meq('B12345B',$b->body());
meq('C12345678C',$b->body());
