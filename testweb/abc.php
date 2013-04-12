<?php
if(isset($_GET)){
	foreach($_GET as $k => $v){
		print($k.'=>'.$v.PHP_EOL);
	}
}