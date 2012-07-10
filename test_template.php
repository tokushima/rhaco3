<?php
include_once('rhaco3.php');

$template = new \org\rhaco\Template();
$template->vars('name','Sample');
$template->vars('data_array',array('ABC','DEF','GHI'));
$template->output(__FILE__);

?>
<rt:template>
<html>
<body>
 <h1>{$name}</h1>
 <rt:loop param="data_array" var="data">
 	<p>{$data}</p>
 </rt:loop>
</body>
</html>
</rt:template>
