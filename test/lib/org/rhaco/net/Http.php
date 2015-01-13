<?php
$http = new \org\rhaco\net\Http();
$http->do_get(url('test_index::webtest'));

$explode_head = $http->explode_head();
eq(true,!empty($explode_head));
eq(true,is_array($explode_head));

$head = $http->head();
eq(true,!empty($head));
eq(true,is_string($head));



$http->cp(array('a'=>1,'b'=>2,'c'=>3));
$http->do_get(url('test_index::webtest'));
eq('a=>1'.PHP_EOL.'b=>2'.PHP_EOL.'c=>3'.PHP_EOL,$http->body());

