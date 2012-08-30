<?php
$b = b();
$b->do_get(test_map_url('login'));
eq(401,$b->status());
eq(test_map_url('login'),$b->url());
meq('<message group="do_login" class="LogicException" type="LogicException">Unauthorized</message>',$b->body());

