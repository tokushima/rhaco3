<?php
namespace org\rhaco\service\flow\module;
/**
 * Instagram認証
 * @author tokushima
 */
class InstagramSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$gram = new \org\rhaco\service\Instagram(\org\rhaco\Conf::get('client_id'), \org\rhaco\Conf::get('client_secret'));
		
		try{
			$gram->get_access_token(\org\rhaco\Conf::get('scope','basic'));
			$user = $gram->me();
			$req->user(array(
				'id'=>$user['id'],
				'name'=>$user['username']
			));
			return true;
		}catch(\Exception $e){
		\org\rhaco\Log::error($e);	
		}
		return false;
	}
}