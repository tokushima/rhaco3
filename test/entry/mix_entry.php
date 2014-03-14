<?php

$b = new \chaco\Browser();

$b->do_get(test_map_url('test_index::sample_flow_exception_throw'));
eq('ERROR',$b->body());

$b->do_get(test_map_url('test_login::aaa'));
eq(401,$b->status());
eq(test_map_url('test_login::login'),$b->url());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());

