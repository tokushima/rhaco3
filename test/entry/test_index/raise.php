<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::raise'));
eq(403,$b->status());
