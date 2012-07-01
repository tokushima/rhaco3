<?php
namespace org\rhaco\service\flow\module;
/**
 * google Openid認証
 * @author tokushima
 */
class MixiOpenidSimpleAuth{
	/**
	 * @module org.rhaco.flow.parts.RequestFlow
	 * @param org.rhaco.flow.parts.RequestFlow $req
	 */
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$openid = new \LightOpenID(\org\rhaco\Request::current_url());
		if($req->is_vars('return_to')) $openid->returnUrl = $req->in_vars('return_to');
	
		if(!$openid->mode){
			$openid->identity = 'http://mixi.jp';
			$openid->required = array('namePerson/friendly','contact/email');
			\org\rhaco\net\http\Header::redirect($openid->authUrl());
		}else if($openid->mode == 'id_res'){
			$user = $openid->getAttributes();
			$req->user(array(
				'id'=>$openid->data['openid_claimed_id'],
				'name'=>($user['namePerson/friendly'])
			));
			return true;
		}
		return false;
	}
}