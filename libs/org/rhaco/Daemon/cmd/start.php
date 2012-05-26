<?php
/**
 * Daemon tool
 * @param string $f php file path
 * @param number $w seccond
 * @param string $s pid file
 */
$php = isset($params['f']) ? $params['f'] : null;
$pid_file = isset($params['s']) ? $params['s'] : null;
$max = isset($params['max']) ? $params['max'] : 1;
$wait = isset($params['w']) ? $params['w'] : 0;

\org\rhaco\Daemon::start($php,$pid_file,$max,$wait);

