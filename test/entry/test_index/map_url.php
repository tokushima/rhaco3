<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::map_url'));

if(\org\rhaco\Conf::appmode() == 'mamp'){
	meq('test_index/noop',$b->body());
	meq('test_login/aaa',$b->body());
}else{
	meq('test_index.php/noop',$b->body());
	meq('test_login.php/aaa',$b->body());
}