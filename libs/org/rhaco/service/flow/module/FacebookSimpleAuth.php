<?php
namespace org\rhaco\service\flow\module;
/**
 * Facebook認証
 * @author tokushima
 * @see https://developers.facebook.com/docs/authentication/server-side/
 * @see https://developers.facebook.com/docs/authentication/permissions/
 * 
 * @see https://www.facebook.com/settings?tab=applications
 */
class FacebookSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$fb = new \org\rhaco\service\Facebook(\org\rhaco\Conf::get('client_id'), \org\rhaco\Conf::get('client_secret'));
		
		try{
			$fb->get_access_token(\org\rhaco\Conf::get('scope'));
			$user = $fb->me();
			$req->user(array('id'=>$user['id'],'name'=>$user['name']));
			return true;
		}catch(\Exception $e){
			
		}
		return false;
	}
}