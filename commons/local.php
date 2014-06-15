<?php
set_include_path(dirname(__DIR__).'/src'.PATH_SEPARATOR.get_include_path());

date_default_timezone_set('Asia/Tokyo');

\org\rhaco\Conf::set(array(
	'org.rhaco.store.db.Dao'=>array(
		'connection'=>array(
			'org.rhaco.flow.module.SessionDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test','user'=>'root','password'=>'root'),
			'org.rhaco.net.mail.module.SmtpBlackholeDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test','user'=>'root','password'=>'root'),
			'org.rhaco.store.queue.module.Dao.QueueDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test','user'=>'root','password'=>'root'),
			'org.rhaco.store.db.Dao.CrossChild'=>array(),
			'test.model.CrossChild'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test','user'=>'root','password'=>'root'),
			'test.model'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test','user'=>'root','password'=>'root'),
				
//			'org.rhaco.flow.module.SessionDao'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local_session.db','host'=>dirname(__DIR__)),
//			'org.rhaco.net.mail.module.SmtpBlackholeDao'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__)),
//			'org.rhaco.store.queue.module.Dao.QueueDao'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__)),
//			'org.rhaco.store.db.Dao.CrossChild'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__)),
//			'test.model.CrossChild'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__)),
//			'test.model'=>array('type'=>'org.rhaco.store.db.module.Sqlite','dbname'=>'local.db','host'=>dirname(__DIR__)),
		)
	),
	'org.rhaco.Flow'=>array(
		'exception_log_level'=>'warn',
		'exception_log_ignore'=>array(
			'Unauthorized.+RequestFlow'
		),
		//'app_url'=>'http://localhost/rhaco3',
	),
	'org.rhaco.Template'=>array('display_exception'=>true),
	'org.rhaco.flow.module.SimpleAuth'=>array('auth'=>array('user_name'=>md5(sha1('password')))),
	'org.rhaco.Log'=>array(
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/rhaco3.log',
		'stdout'=>true,
		'nl2str'=>'<br />',	
	),
	'org.rhaco.io.File'=>array('work_dir'=>dirname(__DIR__).'/work/'),
	'test.WebTest'=>array(
		'base_url'=>'http://localhost/'.basename(dirname(__DIR__)).'/',
	)
));
\org\rhaco\Object::set_module(array(
	'org.rhaco.net.Session'=>array('org.rhaco.flow.module.SessionDao'),
	'org.rhaco.store.queue.Queue'=>array('org.rhaco.store.queue.module.Dao'),
	'org.rhaco.net.mail.Mail'=>array('org.rhaco.net.mail.module.SmtpBlackholeDao'),
));

