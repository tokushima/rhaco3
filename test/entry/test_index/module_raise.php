<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::module_raise'));
eq(403,$b->status());
mneq('INDEX',$b->body());

meq('BEFORE_FLOW_HANDLE',$b->body());
meq('[EXCEPTION]',$b->body());

meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());

meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
