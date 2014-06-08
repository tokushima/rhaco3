<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::sample_flow_exception_throw_xml'));
eq(403,$b->status());
meq('<message group="" type="LogicException">error</message></error>',$b->body());
