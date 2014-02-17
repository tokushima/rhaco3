<?php
$time = time();
eq(date("Y-m-d H:i:s",$time+(3600*24)),date("Y-m-d H:i:s",\org\rhaco\lang\Date::add_day(1,$time)));
eq(date("Y-m-d H:i:s",$time-(3600*24)),date("Y-m-d H:i:s",\org\rhaco\lang\Date::add_day(-1,$time)));

