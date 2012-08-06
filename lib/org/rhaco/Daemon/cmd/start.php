<?php
/**
 * start daemon
 * @param string $php execute php file
 */
$pid = isset($params['d']) ? $params['d'] : null;
$parent = isset($params['parent']) ? $params['parent'] : null;
$opt = array(
			'exec_php'=>(isset($params['exec']) ? $params['exec'] : null),
			'name'=>(isset($params['name']) ? $params['name'] : null),
			'clients'=>(isset($params['clients']) ? $params['clients'] : 1),
			'sleep'=>(isset($params['sleep']) ? $params['sleep'] : null),
			'dir'=>(isset($params['dir']) ? $params['dir'] : null),
			'uid'=>(isset($params['uid']) ? $params['uid'] : null),
			'euid'=>(isset($params['euid']) ? $params['euid'] : null),
			'gid'=>(isset($params['gid']) ? $params['gid'] : null),
			'egid'=>(isset($params['egid']) ? $params['egid'] : null),
		);
if(!empty($parent)){
	$r = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$parent));
	if(!is_subclass_of($r->getName(),'\org\rhaco\Daemon')) throw new \ReflectionException($r->getName().' must be an org.rhaco.Daemon');
	$parent = $r->getName();
}else{
	$parent = '\org\rhaco\Daemon';
}
call_user_func_array(array($parent,'start'),array($pid,$opt));


