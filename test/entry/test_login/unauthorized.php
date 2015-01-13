<?php
$b = new \testman\Browser();
$b->do_post(url('test_login::login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
