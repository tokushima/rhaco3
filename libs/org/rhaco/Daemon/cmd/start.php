<?php
/**
 * Daemon tool
 * @param string $php execute php file
 */
$php = isset($params['php']) ? $params['php'] : null;
$pid = isset($params['d']) ? $params['d'] : '';
$opt = array(
			'name'=>(isset($params['name']) ? $params['name'] : null),
			'clients'=>(isset($params['clients']) ? $params['clients'] : 1),
			'sleep'=>(isset($params['sleep']) ? $params['sleep'] : null),
			'dir'=>(isset($params['dir']) ? $params['dir'] : null),
			'uid'=>(isset($params['uid']) ? $params['uid'] : null),
			'euid'=>(isset($params['euid']) ? $params['euid'] : null),
			'gid'=>(isset($params['gid']) ? $params['gid'] : null),
			'egid'=>(isset($params['egid']) ? $params['egid'] : null),
		);
\org\rhaco\Daemon::start($php,$pid,$opt);

