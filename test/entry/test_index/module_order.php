<?php
$b = b();
$b->do_get(test_map_url('test_index::module_order'));
eq(200,$b->status());
eq('345678910',$b->body());
