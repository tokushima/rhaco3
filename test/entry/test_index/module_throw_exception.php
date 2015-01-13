<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::module_throw_exception'));
eq(403,$b->status());
meq('<message group="" type="LogicException">flow handle begin exception</message>',$b->body());
