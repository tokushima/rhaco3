<?php
$b = b();
$b->do_get(test_map_url('test_index::under_var'));
eq(200,$b->status());
meq('hogehoge',$b->body());
meq('ABC',$b->body());
meq('INIT',$b->body());
