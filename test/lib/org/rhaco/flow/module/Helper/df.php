<?php
$t = new \org\rhaco\flow\module\Helper();
$time = time();
eq(date("YmdHis",$time),$t->df("YmdHis",$time));
