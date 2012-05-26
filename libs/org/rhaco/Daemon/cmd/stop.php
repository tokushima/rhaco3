<?php
/**
 * Daemon tool
 * @param string $s pid file
 * /var/run/****.pid
 */
$pid_file = isset($params['s']) ? $params['s'] : null;
\org\rhaco\Daemon::stop($pid_file);

