<?php
date_default_timezone_set('Asia/Tokyo');

\org\rhaco\Conf::set(array(
	'org.rhaco.store.db.Dao'=>array(
		'connection'=>array(
			'org.rhaco.store.db.Dao.CrossChild'=>array()
//			,'org.rhaco.flow.module.SessionDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'app','user'=>'root','password'=>'root')
			,'org.rhaco.flow.module.SessionDao'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__))
			,'test.model.TestModel'=>array('con'=>'org.rhaco.flow.module.SessionDao')
			,'*'=>array()
		)
	),
	'org.rhaco.Flow'=>array(
		'exception_log_level'=>'warn',
		'exception_log_ignore'=>array(
			'Unauthorized.+RequestFlow'
		)
	),
	'org.rhaco.Template'=>array('display_exception'=>true),
	'org.rhaco.flow.module.SimpleAuth'=>array('auth'=>array('user_name'=>md5(sha1('password')))),
	'org.rhaco.Log'=>array(
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/output.log',
		'stdout'=>true,
	
	),
	'org.rhaco.io.File'=>array('work_dir'=>dirname(__DIR__).'/work/'),
));

\org\rhaco\Object::set_module(array(
	'org.rhaco.net.Session'=>array('org.rhaco.flow.module.SessionDao')
));
// test session dao
\org\rhaco\flow\module\SessionDao::create_table();
\test\model\TestModel::create_table();

