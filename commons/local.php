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
	'org.rhaco.Log'=>array(
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/output.log',
	
	),
	'org.rhaco.io.File'=>array('work_dir'=>dirname(__DIR__).'/work/'),
));
\org\rhaco\Object::set_module(array(
	'org.rhaco.net.Session'=>array('org.rhaco.flow.module.SessionDao')
));
