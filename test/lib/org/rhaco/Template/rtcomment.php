<?php
$src = '123<rt:comment>aaaaaaaa</rt:comment>456';
$t = new \org\rhaco\Template();
eq('123456',$t->get($src));
