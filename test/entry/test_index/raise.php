<?php
$b = b();
$b->do_get(test_map_url('test_index::raise'));
eq(403,$b->status());
