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
	 * 
	 * @conf string $auth string ユーザ,string md5(sha1(パスワード))
	 * @param \org\rhaco\flow\parts\RequestFlow $request
	 * @return boolean
	 */
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $request){
		if(empty($this->users)) $this->users = \org\rhaco\Conf::get_array('auth');

		$password = $request->in_vars('password');
		$request->rm_vars('password');
		if(sizeof($this->users) % 2 !== 0) throw new SimpleAuth\SimpleAuthException();
		for($i=0;$i<sizeof($this->users);$i+=2){
			list($user,$pass) = array($this->users[$i],$this->users[$i+1]);
			if($request->is_post() && $request->in_vars('user_name') === $user && md5(sha1($password)) === $pass){
				return true;
			}
		}
		return false;
	}
}
