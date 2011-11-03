<?php
namespace test\flow\module;
/**
 * ログインのテスト
 * @author tokushima
 *
 */
class CoreTestLogin{
	public function login_condition(\org\rhaco\Request $request){
		if($request->is_post()){
			$password = $request->in_vars('password');
			$request->rm_vars('password');

			if($request->in_vars('user_name') == 'hogeuser' && $password == 'hogehoge'){
				return true;
			}
		}
		return false;
	}
}