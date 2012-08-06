<?php
/**
 * 簡易なHTTPサーバを起動する
 */
$__settings__ = \org\rhaco\io\File::read(getcwd().'/__settings__.php');
$self = new \org\rhaco\net\listener\Runserver();
$req = new \org\rhaco\Request();
$server = new \org\rhaco\net\listener\SocketListener();
$server->set_object_module($self);
$server->start($req->in_vars('address','localhost'),$req->in_vars('port',8080));


