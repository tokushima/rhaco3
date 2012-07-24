<?php
use \org\rhaco\Conf;

\org\rhaco\Conf::set('org.rhaco.store.db.Dao','connection',array(
		'org.rhaco'=>array('type'=>'org.rhaco.store.db.module.Mysql','dbname'=>'app')
		,'org.rhaco.store.db.Dao'=>array('dbname'=>'testA')
		,'org.rhaco.store.db.Dao.CrossChild'=>array('dbname'=>'testB')
		,'test'=>array('dbname'=>'app')
));


\org\rhaco\Conf::set('org.rhaco.Template','display_exception',true);
\org\rhaco\Conf::set('org.rhaco.flow.module.SimpleAuth','auth',array('user_name'=>md5(sha1('password'))));


//\org\rhaco\Conf::set_module('org.rhaco.net.Session','org.rhaco.flow.module.SessionDao');
//\org\rhaco\Conf::set_module('org.rhaco.Template','org.rhaco.store.template.File');


\org\rhaco\Log::set_module('org.rhaco.io.log.Growl');
\org\rhaco\Log::set_module('org.rhaco.io.log.File');



