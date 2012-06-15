v<?php
/**
 * エントリファイルを書き出す
 * @param string $name エントリ名
 */
$name = isset($params['name']) ? $params['name'] : 'index';
$path = getcwd().'/'.$name.'.php';

$src = file_get_contents(dirname(__DIR__).'/resources/entry/template_php');

file_put_contents($path,$src);



