<?php
$obj1 = new \test\model\ObjectFm();
$obj1->aaa(5);
$obj1->bbb(true);
eq(5,$obj1->fm_aaa());
eq('true',$obj1->fm_bbb());
eq(2,$obj1->fm_ccc());
