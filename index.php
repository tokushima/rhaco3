<?php
include_once('bootstrap.php');

$flow = new \sandra\Flow();
$flow->execute([
	'patterns'=>[
		'dt'=>['action'=>'sandra.Dt']
	]
]);
