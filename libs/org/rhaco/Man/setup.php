<?php
/**
 * Class descriptions
 * @param string $value package path
 * @param string $m method name
 */
if(!isset($_ENV['params']['value'])){
	$libs = array();
	$len = 0;
	foreach(\org\rhaco\Man::libs() as $k => $v){
		$info = \org\rhaco\Man::class_info($k);
		list($libs[$k]) = explode("\n",$info['description']);
		if(strlen($k) > $len) $len = strlen($k);
	}
	ksort($libs);
	foreach($libs as $k => $v){
		print(' '.str_pad($k,$len).' '.$v.PHP_EOL);
	}
	exit;
}
if(isset($_ENV['params']['m'])){
	$rtn = \org\rhaco\Man::method_info($_ENV['params']['value'],$_ENV['params']['m']);
	print("\n".'class '.$_ENV['params']['value'].' in method '.$rtn['method_name'].':'.PHP_EOL);
	print(' Description:'.PHP_EOL);
	print('   '.str_replace("\n","\n   ",$rtn['description']).PHP_EOL);
	print("\n".' Parameter:'.PHP_EOL);
	$len = \org\rhaco\lang\Text::length(array_keys($rtn['params']));
	foreach($rtn['params'] as $k => $v){
		print(sprintf('   %s%s : [%s%s] %s',($v[1] ? '&' : ' '),str_pad($k,$len),$v[0],($v[2] ? '='.(isset($v[3]) ? $v[3] : 'null') : ''),$v[4]).PHP_EOL);
	}
	print("\n".' Return:'.PHP_EOL);
	print(sprintf('   %s %s',$rtn['return'][0],$rtn['return'][1]).PHP_EOL);
}else{
	$rtn = \org\rhaco\Man::class_info($_ENV['params']['value']);
	print("\n".'class '.$rtn['package'].':'.PHP_EOL);
	print(' Description:');
	print('   '.str_replace("\n","\n   ",$rtn['description']));
	
	list($static_methods,$methods,$properties) = array($rtn['static_methods'],$rtn['methods'],$rtn['properties']);
	$len = \org\rhaco\lang\Text::length(array_merge(array_keys($static_methods),array_keys($methods),array_keys($properties)));

	if(!empty($static_methods)){
		print("\n".'  Static methods defined here:'.PHP_EOL);
		foreach($static_methods as $k => $v) print('    '.str_pad($k,$len).' : '.$v.PHP_EOL);
	}
	if(!empty($methods)){
		print("\n".'  Methods defined here:'.PHP_EOL);
		foreach($methods as $k => $v) print('    '.str_pad($k,$len).' : '.$v.PHP_EOL);
	}
	if(!empty($properties)){
		print("\n".'  Properties defined here:'.PHP_EOL);
		foreach($properties as $k => $v) print('    '.str_pad($k,$len).' : ('.$v[0].') '.$v[1].PHP_EOL);
	}
}
print(PHP_EOL);
