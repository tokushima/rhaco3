<?php

$b = new \testman\Browser();

$b->do_get(url('test_index::sample_flow_exception_throw'));
eq('ERROR',$b->body());


$b->do_get(url('test_login::aaa'));
eq(401,$b->status());
eq(url('test_login::login'),$b->url());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
