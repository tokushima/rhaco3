<?php
$t = new \org\rhaco\flow\module\Helper();
$str = "あいうえお12345かきくけこ";
eq("あいう",$t->trim_width($str,7));
	
$t = new \org\rhaco\flow\module\Helper();
$str = "あいうえお12345かきくけこ";
eq("あいう...",$t->trim_width($str,7,"..."));
	
$t = new \org\rhaco\flow\module\Helper();
$str = "あいうえお12345かきくけこ";
eq("あいうえお123...",$t->trim_width($str,13,"..."));

$t = new \org\rhaco\flow\module\Helper();
$str = "あいうえお12345かきくけこ";
eq("あいうえお12345かきくけこ",$t->trim_width($str,50,"..."));
	
$t = new \org\rhaco\flow\module\Helper();
$str = "あいうえお12345かきくけこ";
eq("あいうえお12345かきくけこ",$t->trim_width($str,30,"..."));
