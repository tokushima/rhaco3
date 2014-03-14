<?php
$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::module_throw_exception'));
eq(403,$b->status());
meq('<message group="" type="LogicException">flow handle begin exception</message>',$b->body());
