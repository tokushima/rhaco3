<?php
$b = new \testman\Browser();
$b->vars('user_name','aaaa');
$b->vars('password','bbbb');
$b->do_get(url('test_login::login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
