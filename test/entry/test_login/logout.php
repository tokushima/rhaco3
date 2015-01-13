<?php
$b = new \testman\Browser();

$b->vars('user_name','hogeuser');
$b->vars('password','hogehoge');
$b->do_post(url('test_login::login'));
eq(200,$b->status());
meq('<user_name>hogeuser</user_name>',$b->body());

$b->do_post(url('test_login::aaa'));
eq(200,$b->status());
meq('<user><nickname>hogeuser</nickname><code>1234</code></user>',$b->body());

$b->do_post(url('test_login::logout'));
eq(200,$b->status());
meq('<login>false</login>',$b->body());

$b->do_post(url('test_login::aaa'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());

