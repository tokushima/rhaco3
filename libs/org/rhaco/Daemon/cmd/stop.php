<?php
/**
 * Daemon tool
 * @param string $d pid file path
 */
$pid = isset($params['d']) ? $params['d'] : '';
$opt = array(
			'exec_php'=>(isset($params['exec']) ? $params['exec'] : null),
			'name'=>(isset($params['name']) ? $params['name'] : null),
		);
\org\rhaco\Daemon::stop($pid,$opt);

