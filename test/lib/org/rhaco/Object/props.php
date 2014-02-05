<?php
$obj = new \test\model\ObjectProps();
eq(array("aaa","bbb","ddd","fff"),array_keys($obj->props()));
eq(array(1,2,4,6),array_values($obj->props()));
