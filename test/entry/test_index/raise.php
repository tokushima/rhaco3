<?php
$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::raise'));
eq(403,$b->status());