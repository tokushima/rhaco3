<?php
/**
 * Daemon tool
 * @param string $value php path
 * @param integer $wait micro seccond
 */
$php = $value;
$pid_file = isset($params['out']) ? $params['out'] : null;
$max = isset($params['max']) ? $params['max'] : 1;
$wait_microsec = isset($params['wait']) ? $params['wait'] : 1000;

\org\rhaco\Daemon::start($value,$pid_file,$max,$wait_microsec);

