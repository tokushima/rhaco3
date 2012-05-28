<?php
/**
 * Daemon tool
 * @param string $d pid file path
 */
$pid = isset($params['d']) ? $params['d'] : '';
$parent = isset($params['parent']) ? $params['parent'] : null;
$opt = array(
			'exec_php'=>(isset($params['exec']) ? $params['exec'] : null),
			'name'=>(isset($params['name']) ? $params['name'] : null),
		);
if(!empty($parent)){
	$r = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$parent));
	if(!is_subclass_of($r->getName(),'\org\rhaco\Daemon')) throw new \ReflectionException($r->getName().' must be an org.rhaco.Daemon');
	$parent = $r->getName();
}else{
	$parent = '\org\rhaco\Daemon';
}
call_user_func_array(array($parent,'stop'),array($pid,$opt));


