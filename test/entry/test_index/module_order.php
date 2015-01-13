<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::module_order'));
eq(200,$b->status());
eq('345678910',$b->body());
