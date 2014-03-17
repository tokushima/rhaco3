<?php
/**
 * stop daemon
 * @param string $pid
 * @param string $parent
 * @param string $exec
 * @param string $name
 */
$opt = array(
			'exec_php'=>$exec,
			'name'=>$name,
		);
if(!empty($parent)){
	$r = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$parent));
	if(!is_subclass_of($r->getName(),'\org\rhaco\Daemon')) throw new \ReflectionException($r->getName().' must be an org.rhaco.Daemon');
	$parent = $r->getName();
}else{
	$parent = '\org\rhaco\Daemon';
}
call_user_func_array(array($parent,'stop'),array($pid,$opt));


