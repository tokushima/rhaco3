<?php
/**
 * start daemon
 * @param string $exec
 * @param string $pid
 * @param string $parent
 * @param string $exec
 * @param string $name
 * @param integer $clients
 * @param integer $sleep
 * @param string $dir
 * @param string $uid
 * @param string $euid
 * @param string $egid
 * @param string $php execute php file
 */
$opt = array(
			'exec_php'=>$exec,
			'name'=>$name,
			'clients'=>(empty($clients) ? 1 : $clients),
			'sleep'=>$sleep,
			'dir'=>$dir,
			'uid'=>$uid,
			'euid'=>$euid,
			'gid'=>$gid,
			'egid'=>$egid,
		);
if(!empty($parent)){
	$r = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$parent));
	if(!is_subclass_of($r->getName(),'\org\rhaco\Daemon')) throw new \ReflectionException($r->getName().' must be an org.rhaco.Daemon');
	$parent = $r->getName();
}else{
	$parent = '\org\rhaco\Daemon';
}
call_user_func_array(array($parent,'start'),array($pid,$opt));


