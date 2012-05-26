<?php
/**
 * Daemon tool
 * @param string $d pid file path
 */
$php = isset($params['php']) ? $params['php'] : null;
$pid = isset($params['d']) ? $params['d'] : '';
$opt = array(
			'name'=>(isset($params['name']) ? $params['name'] : null),
		);
\org\rhaco\Daemon::stop($php,$pid,$opt);

