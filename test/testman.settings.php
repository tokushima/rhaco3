<?php
\testman\Conf::set('urls',\org\rhaco\Dt::get_urls());
\testman\Conf::set('ssl-verify',false);
\testman\Conf::set('coverage-dir',dirname(__DIR__).'/src');


\org\rhaco\Conf::set('org.rhaco.store.db.Dbc','autocommit',true);
