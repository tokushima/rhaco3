<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::rt_exceptions'));
meq('hoge',$b->body());
