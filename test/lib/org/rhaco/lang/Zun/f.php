<?php
$text = <<< PRE
[[[ほげほげ]]]
PRE;
$result = <<< PRE
<pre>ほげほげ</pre>
PRE;
$obj = new \org\rhaco\lang\Zun();
eq($result,$obj->f($text));
