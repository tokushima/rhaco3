<?php
namespace test\flow\module;
/**
 * ログインのテスト
 * @author tokushima
 *
 */
class CoreTestLogin{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $request){
		if($request->is_post()){
			$password = $request->in_vars('password');
			$request->rm_vars('password');

			if($request->in_vars('user_name') == 'hogeuser' && $password == 'hogehoge'){
				$user = new \org\rhaco\Object();
				$user->nickname = 'hogeuser';
				$user->code = '1234';
				
				$request->user($user);
				return true;
			}
		}
		return false;
	}
}