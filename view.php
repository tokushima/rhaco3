<?php
include_once('bootstrap.php');

$flow = new \sandra\Flow();
$flow->execute([
	'patterns'=>[
		'(.+)'=>['action'=>'sandra.flow.parts.PatternBlocks::select'
					,'template'=>'index.html'
				],
		'dt'=>['action'=>'sandra.Dt']
	]		
]);
