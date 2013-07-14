<?php
include_once('bootstrap.php');

$flow = new \sandra\Flow();
$flow->execute([
	'patterns'=>[
		''=>['action'=>'local.Pages']
	]
]);
