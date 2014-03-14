<?php
$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::helper_range'));
meq('A1234A',$b->body());
meq('B12345B',$b->body());
meq('C12345678C',$b->body());
