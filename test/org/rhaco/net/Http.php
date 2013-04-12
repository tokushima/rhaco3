<?php
$http = new \org\rhaco\net\Http();
$http->do_get('http://localhost/rhaco3/testweb/abc.php');

$explode_head = $http->explode_head();
eq(true,!empty($explode_head));
eq(true,is_array($explode_head));

$head = $http->head();
eq(true,!empty($head));
eq(true,is_string($head));

