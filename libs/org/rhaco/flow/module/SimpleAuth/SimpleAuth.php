<?php
namespace org\rhaco\flow\module;
require(__DIR__.'/SimpleAuthException.php');
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
	 * Requestのモジュール
	 * @conf string $auth string ユーザ,string md5(sha1(パスワード))
	 * @param Request $request
	 * @return boolean
	 */
	public function login_condition(\org\rhaco\Request $request){
		if(empty($this->users)) $this->users = \org\rhaco\Conf::get_array('auth');

		$password = $request->in_vars('password');
		$request->rm_vars('password');
		if(sizeof($this->users) % 2 !== 0) throw new SimpleAuth\SimpleAuthException();
		for($i=0;$i<sizeof($this->users);$i+=2){
			list($user,$pass) = array($this->users[$i],$this->users[$i+1]);
			if($request->is_post() && $request->in_vars('login') === $user && md5(sha1($password)) === $pass){
				return true;
			}
		}
		return false;
	}
	/**
	 * Requestのモジュール
	 */
	public function login_invalid(\org\rhaco\Request $request){
		$t = new \org\rhaco\Template();
		$t->cp($request);
		$t->output(__DIR__.'/resources/templates/login.html');
	}
}
