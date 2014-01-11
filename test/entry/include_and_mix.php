<?php
include(__DIR__.'/mix_entry.php');

$b = b();
$b->do_get(test_map_url('test_index::noop'));
eq(200,$b->status());
meq('<init_var>INIT</init_var>',$b->body());

eq(true,true);

