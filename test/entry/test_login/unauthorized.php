<?php
$b = b();
$b->do_post(test_map_url('test_login::login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
