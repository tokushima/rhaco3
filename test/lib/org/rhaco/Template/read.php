<?php
$src = <<< 'PRE'
{$abc} ABC
{$def} DEF
{$ghi} GHI
PRE;
$result = <<< 'PRE'
123 ABC
456 DEF
789 GHI
PRE;
$t = new \org\rhaco\Template();
$t->vars("abc",123);
$t->vars("def",456);
$t->vars("ghi",789);
eq($result,$t->get($src));
