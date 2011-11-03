<?php
/**
 * 簡易なHTTPサーバを起動する
 */
$__settings__ = \org\rhaco\io\File::read(getcwd().'/__settings__.php');
if(is_file($__settings__)){
	$src = file_get_contents($__settings__);
	//if(strpos($src,module_package()) === false){
	//	\org\rhaco\io\File::write(App::path("__settings__.php"),$src."\nimport('".module_package()."');");
}
$self = new \org\rhaco\net\listener\Runserver();
//if($req->is_vars("php")) $self->php_cmd($req->in_vars("php"));

$req = new \org\rhaco\Request();
$server = new \org\rhaco\net\listener\SocketListener();
$server->set_object_module($self);
$server->start($req->in_vars('address','localhost'),$req->in_vars('port',8080));


