<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_login::login'));
eq(401,$b->status());
eq(test_map_url('test_login::login'),$b->url());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());


$b = new \testman\Browser();
$b->vars('user_name','hogeuser');
$b->vars('password','hogehoge');
$b->do_post(test_map_url('test_login::login'));
eq(200,$b->status());
eq('<result><user_name>hogeuser</user_name></result>',$b->body());

$b->do_post(test_map_url('test_login::aaa'));
eq(200,$b->status());
eq('<result><user><nickname>hogeuser</nickname><code>1234</code></user></result>',$b->body());
