<?php
/**
 * Configuration list 
 */
$all = \org\rhaco\Conf::all();
$length = 0;
foreach($all as $p => $confs){
	foreach($confs as $n => $conf){
		if($length < strlen($p.$n)) $length= strlen($p.$n);
	}
}
print('Config list:'.PHP_EOL);
foreach($all as $p => $confs){
	foreach($confs as $n => $conf){
		print('    '.(\org\rhaco\Conf::exists($p,$n) ? '[*]' : '[-]').' '.str_pad($p.'@'.$n,$length+1).' : '.(empty($conf) ? '' : sprintf('(%s) %s',$conf[0],$conf[1])).PHP_EOL);
	}
}
