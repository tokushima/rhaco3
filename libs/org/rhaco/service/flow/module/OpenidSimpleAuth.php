<?php
namespace org\rhaco\service\flow\module;
/**
 * Openid認証
 * @author tokushima
 * @incomplete
 * openid_identifier http://mixi.jp
 * openid_identifier https://www.google.com/accounts/o8/id
 */
class OpenidSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$openid = new \LightOpenID(\org\rhaco\Request::current_url());
		$openid_identifier = $req->in_vars('openid_identifier');
		if($req->is_vars('return_to')) $openid->returnUrl = $req->in_vars('return_to');
	
		if(!$openid->mode){
			$req->rm_sessions('openid_attributes');
			
			if(!empty($openid_identifier)){
				$openid->identity = $openid_identifier;
				$openid->required = array('contact/email');
				$openid->optional = array('namePerson','namePerson/friendly');
				\org\rhaco\net\http\Header::redirect($openid->authUrl());
			}
		}else if($openid->mode == 'id_res'){
			$req->user($openid->getAttributes());
			return true;
		}
		return false;
	}
}