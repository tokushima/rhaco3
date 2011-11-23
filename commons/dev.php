<?php
use \org\rhaco\Conf;
Conf::set('org.rhaco.store.db.Dao','org.rhaco','{"type":"org.rhaco.store.db.module.Mysql","dbname":"app","user":"root","password":"root"}');
Conf::set('org.rhaco.store.db.Dao','org.rhaco.store.db.Dao','{"dbname":"testA"}');
Conf::set('org.rhaco.store.db.Dao','org.rhaco.store.db.Dao.CrossChild','{"dbname":"testB"}');
 
Conf::set('org.rhaco.Log','level','info');


\org\rhaco\Log::set_module(new \org\rhaco\io\log\Growl());
\org\rhaco\Log::set_module(new \org\rhaco\io\log\File());
\org\rhaco\io\FileChangedLog::set_module(new \org\rhaco\io\log\Growl());
\org\rhaco\io\FileChangedLog::set_module(new \org\rhaco\io\log\File());

