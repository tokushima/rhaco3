<?php
$b = b();
$b->do_get(test_map_url('login'));
eq(200,$b->status());
eq(test_map_url('login'),$b->url());
eq('<result />',$b->body());

