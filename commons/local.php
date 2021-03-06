<?php
\org\rhaco\Conf::set(array(
	'org.rhaco.store.db.Dao'=>array(
		'connection'=>array(
			'org.rhaco.flow.module.SessionDao'=>array('dbname'=>'local_session.db'),
			'org.rhaco.net.mail.module.SmtpBlackholeDao'=>array('dbname'=>'local1.db'),
			'org.rhaco.store.queue.module.Dao.QueueDao'=>array('dbname'=>'local2.db'),
			'org.rhaco.store.db.Dao.CrossChild'=>array('dbname'=>'local3.db'),

			'test.model.CrossChild'=>array('dbname'=>'local4.db'),
			'test.model'=>array('dbname'=>'local5.db'),
			'*'=>array('dbname'=>'local6.db'),
		)
	),
	'org.rhaco.store.db.module.Sqlite'=>array(
		'host'=>dirname(__DIR__).'/work/db'
	),
	'org.rhaco.Flow'=>array(
		'exception_log_level'=>'warn',
		'exception_log_ignore'=>array(
			'Unauthorized.+RequestFlow'
		),
//		'app_url'=>'http://localhost:8000',
//		'app_url'=>'http://localhost/rhaco3',
	),
	'org.rhaco.Template'=>array('display_exception'=>true),
	'org.rhaco.flow.module.SimpleAuth'=>array('auth'=>array('user_name'=>md5(sha1('password')))),
	'org.rhaco.Log'=>array(
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/rhaco3.log',
		'stdout'=>true,
//		'nl2str'=>'<br />',	
	),
	'org.rhaco.io.File'=>array(
		'work_dir'=>dirname(__DIR__).'/work/'
	),
	'org.rhaco.Dt'=>array(
		'use_vendor'=>array(
			'org.rhaco.store.queue.module.Dao.QueueDao',
			'org.rhaco.net.mail.module.SmtpBlackholeDao',
			'org.rhaco.flow.module.SessionDao',
		)
	),
));
\org\rhaco\Object::set_module(array(
	'org.rhaco.net.Session'=>array('org.rhaco.flow.module.SessionDao'),
	'org.rhaco.store.queue.Queue'=>array('org.rhaco.store.queue.module.Dao'),
	'org.rhaco.net.mail.Mail'=>array('org.rhaco.net.mail.module.SmtpBlackholeDao'),
));

