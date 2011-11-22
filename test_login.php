<?php
include_once('rhaco3.php');
/**
 * @name rhaco.org
 * @summary site
 */
$flow = new \org\rhaco\Flow();
$flow->output(array(''
,modules=>'test.flow.module.CoreTestLogin'
,patterns=>array(
	'login'=>array(name=>'login',action=>'test.CoreTestLoginFlow::do_login')
	,'logout'=>array(name=>'logout',action=>'test.CoreTestLoginFlow::do_logout')
	,'aaa'=>array(name=>'aaa',action=>'test.CoreTestLoginFlow::aaa')
)));

/***
#login
$b = b();
$b->do_get(test_map_url('login'));
eq(200,$b->status());
eq(test_map_url('login'),$b->url());
eq('<result />',$b->body());
*/
/***
#aaa_to_login
$b = b();
$b->do_get(test_map_url('aaa'));
eq(200,$b->status());
eq(test_map_url('login'),$b->url());
eq('<result />',$b->body());
*/
/***
#unauthorized
$b = b();
$b->do_post(test_map_url('login'));
eq(401,$b->status());
eq('<error><message group="do_login" type="LogicException">Unauthorized</message></error>',$b->body());
*/
/***
$b = b();
$b->vars('user_name','aaaa');
$b->vars('password','bbbb');
$b->do_get(test_map_url('login'));
eq(200,$b->status());
eq('<result><user_name>aaaa</user_name><password>bbbb</password></result>',$b->body());
*/
/***
$b = b();
$b->vars('user_name','aaaa');
$b->vars('password','bbbb');
$b->do_post(test_map_url('login'));
eq(401,$b->status());
eq('<error><message group="do_login" type="LogicException">Unauthorized</message></error>',$b->body());
*/
/***
$b = b();
$b->vars('user_name','hogeuser');
$b->vars('password','hogehoge');
$b->do_post(test_map_url('login'));
eq(200,$b->status());
eq('<result><user_name>hogeuser</user_name></result>',$b->body());
*/

/***
$b = b();
$b->vars('user_name','hogeuser');
$b->vars('password','hogehoge');
$b->do_post(test_map_url('login'));
eq(200,$b->status());
eq('<result><user_name>hogeuser</user_name></result>',$b->body());
$b->do_post(test_map_url('aaa'));
eq('<result><user><nickname>hogeuser</nickname><code>1234</code></user></result>',$b->body());
*/
