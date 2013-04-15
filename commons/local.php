<?php
date_default_timezone_set('Asia/Tokyo');

\org\rhaco\Conf::set(array(
	'org.rhaco.store.db.Dao'=>array(
		'connection'=>array(
			'org.rhaco'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'app')
			,'org.rhaco.store.db.Dao'=>array('dbname'=>'testA')
			,'org.rhaco.store.db.Dao.CrossChild'=>array('dbname'=>'testB')
			,'test'=>array('dbname'=>'app')
		)
	),
	'org.rhaco.Flow'=>array('exception_log_level'=>'warn'),
	'org.rhaco.Template'=>array('display_exception'=>true),
	'org.rhaco.flow.module.SimpleAuth'=>array('auth'=>array('user_name'=>md5(sha1('password')))),
	'org.rhaco.Log'=>array('level'=>'info'),
));



//\org\rhaco\net\Session::set_module('org.rhaco.flow.module.SessionDao');
//\org\rhaco\Template::set_module('org.rhaco.store.template.File');
\org\rhaco\Log::set_module('org.rhaco.io.log.Growl');
\org\rhaco\Log::set_module(new \org\rhaco\io\log\OneFile());
\org\rhaco\Log::set_module(new \org\rhaco\io\log\File());


//\org\rhaco\Conf::set('org.rhaco.Flow','app_url','http://127.0.0.1:8888/rhaco3');

