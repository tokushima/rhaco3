<?php
/**
 * ログIDの複合化
 * @param string id ログID
 */
$id = isset($params['id']) ? $params['id'] : null;

if(empty($id)) throw new \InvalidArgumentException('`id` required');
$str = \org\rhaco\Exceptions::parse_id($id);
\org\rhaco\lang\AnsiEsc::println($str);
