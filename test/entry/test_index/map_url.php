<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::map_url'));
meq('test_index.php/noop',$b->body());
meq('test_login.php/aaa',$b->body());
