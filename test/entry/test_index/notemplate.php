<?php
$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::notemplate'));
eq(200,$b->status());
eq('<result><abc>ABC</abc><newtag><hoge>HOGE</hoge></newtag></result>',$b->body());