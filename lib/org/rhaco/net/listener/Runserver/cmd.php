<?php
/**
 * 簡易なHTTPサーバを起動する
 * @param string $address アドレス
 * @param integer $port ポート
 */
if(empty($address)) $address = 'localhost';
if(empty($port)) $port = 8080;
$self = new \org\rhaco\net\listener\Runserver();
$server = new \org\rhaco\net\listener\SocketListener();
$server->set_object_module($self);
$server->start($address,$port);


