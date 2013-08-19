<?php
include_once('bootstrap.php');

/**
 * @name rhaco.org
 * @summary site
 */
\org\rhaco\Flow::out(array(''
,'modules'=>'test.flow.module.CoreTestLogin'
,'patterns'=>array(
	'login_url'=>array('name'=>'login','action'=>'test.CoreTestLoginFlow::do_login')
	,'logout_url'=>array('name'=>'logout','action'=>'test.CoreTestLoginFlow::do_logout')
	,'aaa'=>array('name'=>'aaa','action'=>'test.CoreTestLoginFlow::aaa')
)));

/***
#login
$b = b();
$b->do_get(test_map_url('login'));
eq(401,$b->status());
eq(test_map_url('login'),$b->url());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
*/
/***
#aaa_to_login
$b = b();
$b->do_get(test_map_url('aaa'));
eq(401,$b->status());
eq(test_map_url('login'),$b->url());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
*/
/***
#unauthorized
$b = b();
$b->do_post(test_map_url('login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
*/
/***
$b = b();
$b->vars('user_name','aaaa');
$b->vars('password','bbbb');
$b->do_get(test_map_url('login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
*/
/***
$b = b();
$b->vars('user_name','aaaa');
$b->vars('password','bbbb');
$b->do_post(test_map_url('login'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());
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

/***
#logout
$b = b();

$b->vars('user_name','hogeuser');
$b->vars('password','hogehoge');
$b->do_post(test_map_url('login'));
eq(200,$b->status());
eq('<result><user_name>hogeuser</user_name></result>',$b->body());

$b->do_post(test_map_url('aaa'));
eq(200,$b->status());
eq('<result><user><nickname>hogeuser</nickname><code>1234</code></user></result>',$b->body());

$b->do_post(test_map_url('logout'));
eq(200,$b->status());
eq('<result><login>false</login></result>',$b->body());

$b->do_post(test_map_url('aaa'));
eq(401,$b->status());
meq('<message group="do_login" type="LogicException">Unauthorized</message>',$b->body());

*/


