<?php
$b = b();
$b->do_get(test_map_url('test_index::put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('NONE',$b->body());

$b = b();
$b->vars('hoge','a');
$b->do_get(test_map_url('test_index::put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('a',$b->body());
mneq('CCC',$b->body());

$b = b();
$b->vars('hoge','b');
$b->do_get(test_map_url('test_index::put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('b',$b->body());
mneq('CCC',$b->body());

