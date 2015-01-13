<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::module_map'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW_HANDLE',$b->body());
meq('AFTER_FLOW_HANDLE',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_EXEC_TEMPLATE',$b->body());
meq('AFTER_EXEC_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
