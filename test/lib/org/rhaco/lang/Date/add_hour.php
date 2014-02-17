<?php
$time = time();
eq(date("Y-m-d H:i:s",$time+3600),date("Y-m-d H:i:s",\org\rhaco\lang\Date::add_hour(1,$time)));
eq(date("Y-m-d H:i:s",$time-3600),date("Y-m-d H:i:s",\org\rhaco\lang\Date::add_hour(-1,$time)));
