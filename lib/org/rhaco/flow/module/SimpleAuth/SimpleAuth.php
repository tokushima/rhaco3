<?php
namespace org\rhaco\flow\module;
/**
 * 単純な認証モジュール
 * @author tokushima
 */
class SimpleAuth{
	private $users = array();
	
	public function __construct(){
		$this->users = func_get_args();
	}
	/**
	 * @module org.rhaco.flow.parts.RequestFlow
	 * @conf string{} $auth ユーザ:md5(sha1(パスワード))
	 * @param \org\rhaco\flow\parts\RequestFlow $request
	 * @return boolean
	 */
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		if(empty($this->users)) $this->users = \org\rhaco\Conf::get('auth');
		if($req->is_post() 
			&& isset($this->users[$req->in_vars('user_name')]) 
			&& $this->users[$req->in_vars('user_name')] == md5(sha1($req->in_vars('password')))
		){
			return true;
		}
		return false;
	}
}
