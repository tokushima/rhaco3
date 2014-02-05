<?php
$src = pre('
		abc {$abc}
		def {$def}
		ghi {$ghi}
		');
$result = pre('
		abc 123
		def 456
		ghi 789
		');
$t = new \org\rhaco\Template();
$t->vars("abc",123);
$t->vars("def",456);
$t->vars("ghi",789);
eq($result,$t->get($src));
