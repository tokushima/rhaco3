<?php
/**
 * Class descriptions
 * @arg package
 * @param string $m method name
 * @param string $module
 */
try{
	$rtn = \org\rhaco\Dt::class_info($arg);
}catch(\Exception $e){
	$libs = array();
	$len = 0;
	foreach(\org\rhaco\Dt::classes() as $k => $v){
		if(empty($arg) || strpos(strtolower($k),strtolower($arg)) !== false){
			$info = \org\rhaco\Dt::class_info($k);
			list($libs[$k]) = explode("\n",$info['description']);
			if(strlen($k) > $len) $len = strlen($k);
		}
	}
	ksort($libs);
	foreach($libs as $k => $v){
		print(' '.str_pad($k,$len).' '.$v.PHP_EOL);
	}
	exit;
}
if(!empty($m)){
	$rtn = \org\rhaco\Dt::method_info($arg,$m);
	print("\n".'class '.$arg.' in method '.$rtn['method_name'].':'.PHP_EOL);
	print(' Description:'.PHP_EOL);
	print('   '.str_replace("\n","\n   ",$rtn['description']).PHP_EOL);
	print("\n".' Parameter:'.PHP_EOL);
	$len = \org\rhaco\lang\Text::length(array_keys($rtn['params']));
	foreach($rtn['params'] as $k => $v){
		print(sprintf('   %s%s : [%s%s] %s',($v[1] ? '&' : ' '),str_pad($k,$len),$v[0],($v[2] ? '='.(isset($v[3]) ? $v[3] : 'null') : ''),$v[4]).PHP_EOL);
	}
	print("\n".' Return:'.PHP_EOL);
	if(!empty($rtn['return'])){
		print(sprintf('   %s %s',$rtn['return'][0],$rtn['return'][1]).PHP_EOL);
	}
}else if(!empty($module)){
	$rtn = \org\rhaco\Dt::class_info($arg);
	if(!isset($rtn['modules'][$module])) throw new \RuntimeException('module `'.$module.'` not found');
	$module_info = $rtn['modules'][$module];
	
	print("\n".'class '.$arg.' in module '.$module.':'.PHP_EOL);
	print(' Description:'.PHP_EOL);
	print('   '.str_replace("\n","\n   ",$module_info[0]).PHP_EOL);
	print("\n".' Parameter:'.PHP_EOL);
	$len = \org\rhaco\lang\Text::length(array_keys($module_info[1]));
	foreach($module_info[1] as $p){
		print('    '.str_pad('',$len).'    ('.$p[1].') '.$p[0].' : '.$p[2].PHP_EOL);
	}	
}else{
	$rtn = \org\rhaco\Dt::class_info($arg);
	print("\n".'class '.$rtn['package'].':'.PHP_EOL);
	print('  Version: '.$rtn['version'].PHP_EOL.PHP_EOL);
	print('  Description:'.PHP_EOL);
	print('   '.str_replace("\n","\n  ",$rtn['description']).PHP_EOL.PHP_EOL);
	
	list($static_methods,$methods,$protected_static_methods,$protected_methods,$properties,$modules) = array($rtn['static_methods'],$rtn['methods'],$rtn['protected_static_methods'],$rtn['protected_methods'],$rtn['properties'],$rtn['modules']);
	$len = \org\rhaco\lang\Text::length(array_merge(array_keys($static_methods),array_keys($methods),array_keys($properties),array_keys($modules)));

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
	if(!empty($modules)){
		print("\n".'  Modules defined here:'.PHP_EOL);
		foreach($modules as $k => $v){
			list($summary) = explode(PHP_EOL,$v[0]);
			print('    '.str_pad($k,$len).' : '.$summary.PHP_EOL);
		}
	}
	
	if(!empty($protected_static_methods)){
		print("\n".'  Protected static methods defined here:'.PHP_EOL);
		foreach($protected_static_methods as $k => $v) print('    '.str_pad($k,$len).' : '.$v.PHP_EOL);
	}
	if(!empty($protected_methods)){
		print("\n".'  Protected Methods defined here:'.PHP_EOL);
		foreach($protected_methods as $k => $v) print('    '.str_pad($k,$len).' : '.$v.PHP_EOL);
	}
}
print(PHP_EOL);
