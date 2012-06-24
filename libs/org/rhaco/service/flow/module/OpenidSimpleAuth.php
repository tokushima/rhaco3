<?php
namespace org\rhaco\service\flow\module;
/**
 * Openid認証
 * @author tokushima
 */
class OpenidSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$openid = new \LightOpenID(\org\rhaco\Request::current_url());
		if($req->is_vars('return_to')) $openid->returnUrl = $req->in_vars('return_to');
	
		if(!$openid->mode){
			$openid_identifier = $req->in_vars('openid_identifier');
			if(empty($openid_identifier)) throw new \RuntimeException('openid_identifier not found');
			
			$openid->identity = $openid_identifier;
			$openid->required = array('contact/email');
			$openid->optional = array('namePerson','namePerson/friendly');
			\org\rhaco\net\http\Header::redirect($openid->authUrl());
		}else if($openid->mode == 'id_res'){
			$user = $openid->getAttributes();
			$req->user(array(
				'id'=>(isset($openid->data['openid_claimed_id']) ? $openid->data['openid_claimed_id'] : null),
				'name'=>(isset($user['namePerson/friendly']) ? $user['namePerson/friendly'] : null)
			));
			return true;
		}
		return false;
	}
}