<?php
include(__DIR__.'/mix_entry.php');

$b = new \testman\Browser();
$b->do_get(url('test_index::noop'));
eq(200,$b->status());
meq('<init_var>INIT</init_var>',$b->body());


