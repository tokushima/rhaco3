<?php
\org\rhaco\Conf::set(array(
	'org.rhaco.store.db.Dao'=>array(
			'connection'=>array(
					'org.rhaco.flow.module.SessionDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
					'org.rhaco.net.mail.module.SmtpBlackholeDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
					'org.rhaco.store.queue.module.Dao.QueueDao'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
					'org.rhaco.store.db.Dao.CrossChild'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),

					'test.model.CrossChild'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
					'test.model'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
					'*'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'rhaco3test'),
			)
	)
));

\org\rhaco\Conf::set('org.rhaco.Flow','app_url','http://localhost/rhaco3');
\org\rhaco\Conf::set('org.rhaco.Flow','rewrite_entry',true);

include('local.php');
