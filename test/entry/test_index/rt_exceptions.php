<?php
$b = b();
$b->do_get(test_map_url('test_index::rt_exceptions'));
meq('hoge',$b->body());
