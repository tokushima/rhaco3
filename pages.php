<?php
include_once('bootstrap.php');

$flow = new \sandra\Flow();
$flow->execute([
	'patterns'=>[
		''=>['action'=>'sandra.pages.Editor']
	]
]);
