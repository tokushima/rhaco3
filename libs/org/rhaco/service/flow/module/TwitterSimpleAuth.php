<?php
namespace org\rhaco\service\flow\module;
/**
 * Twitter認証
 * @author tokushima
 */
class TwitterSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$twitter = new \org\rhaco\service\Twitter(\org\rhaco\Conf::get('consumer_key'),\org\rhaco\Conf::get('consumer_secret'));
		
		try{
			$twitter->auth();
			$user = $twitter->verify_credentials();
			$req->user(array('id'=>$user['id'],'name'=>$user['name']));
			return true;
		}catch(\Exception $e){
			
		}
		return false;
	}
}