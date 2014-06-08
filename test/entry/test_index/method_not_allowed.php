<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::method_not_allowed'));
eq(405,$b->status());
meq('<message group="" type="LogicException">Method Not Allowed</message>',$b->body());
