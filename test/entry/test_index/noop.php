<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::noop'));
eq(200,$b->status());
eq('<result><init_var>INIT</init_var></result>',$b->body());
