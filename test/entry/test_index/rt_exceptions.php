<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::rt_exceptions'));
meq('hoge',$b->body());
