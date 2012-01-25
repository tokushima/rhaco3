<?php
use \org\rhaco\Conf;
Conf::set('org.rhaco.store.db.Dao','org.rhaco','{"type":"org.rhaco.store.db.module.Mysql","dbname":"app"}');
Conf::set('org.rhaco.store.db.Dao','org.rhaco.store.db.Dao','{"dbname":"testA"}');
Conf::set('org.rhaco.store.db.Dao','org.rhaco.store.db.Dao.CrossChild','{"dbname":"testB"}');
Conf::set('org.rhaco.store.db.Dao','test','{"dbname":"app"}');
 


Conf::set('org.rhaco.Template','display_exception',true);
Conf::set('org.rhaco.Log','level','error');
Conf::set('org.rhaco.flow.module.SimpleAuth','auth','user_name',md5(sha1('password')));


\org\rhaco\Log::set_module(new \org\rhaco\io\log\Growl());
\org\rhaco\Log::set_module(new \org\rhaco\io\log\File());
\org\rhaco\io\FileChangedLog::set_module(new \org\rhaco\io\log\Growl());
\org\rhaco\io\FileChangedLog::set_module(new \org\rhaco\io\log\File());


