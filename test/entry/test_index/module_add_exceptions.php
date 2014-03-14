<?php
$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::module_add_exceptions'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW',$b->body());
meq('AFTER_FLOW',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
