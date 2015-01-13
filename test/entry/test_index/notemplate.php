<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::notemplate'));
eq(200,$b->status());
eq('<result><abc>ABC</abc><newtag><hoge>HOGE</hoge></newtag></result>',$b->body());
