<?php
$text = pre('
		[[[
			
		ほげほげ
		]]]
		');
$result = pre('
		<pre>
			
		ほげほげ
		</pre>
		');
$obj = new \org\rhaco\lang\Zun();
eq($result,$obj->f($text));
