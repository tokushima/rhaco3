<?php
namespace org\rhaco\service\flow\module;
/**
 * Facebook認証
 * @incomplete
 * @author tokushima
 * @see https://developers.facebook.com/docs/authentication/server-side/
 * @see https://developers.facebook.com/docs/authentication/permissions/
 * 
 * @see https://www.facebook.com/settings?tab=applications
 */
class FacebookSimpleAuth{
	public function login_condition(\org\rhaco\flow\parts\RequestFlow $req){
		$my_url = \org\rhaco\Request::current_url();
		$client_id = \org\rhaco\Conf::get('client_id');
		$client_secret = \org\rhaco\Conf::get('client_secret');
		$scope = \org\rhaco\Conf::get('scope'); // permissions. comma separated
		
		$http = new \org\rhaco\net\Http();
		$code = $req->in_vars('code');
		
		if(empty($code)){
			$req->sessions('state',md5(uniqid(rand(),true)));		
			$http->vars('client_id',$client_id);
			$http->vars('redirect_uri',$my_url);
			$http->vars('state',$req->in_sessions('state'));
			$http->vars('scope',$scope);
			$http->do_redirect('https://www.facebook.com/dialog/oauth');
		}
		if($req->in_vars('state') == $req->in_sessions('state')){
			$http->vars('client_id',$client_id);
			$http->vars('redirect_uri',$my_url);
			$http->vars('client_secret',$client_secret);
			$http->vars('code',$code);
			$http->do_get('https://graph.facebook.com/oauth/access_token');
			
			$response = $http->body();
			$params = null;
			parse_str($response, $params);
			$access_token = $params['access_token'];
			
			$http->vars('access_token',$access_token);
			$http->do_get('https://graph.facebook.com/me');
			$ret = json_decode($http->body(),true);
			if(isset($ret['error']) && $ret['error']['type'] == 'OAuthException') return false;
			$ret['access_token'] = $access_token;
			$req->user((object)$ret);
			return true;
		}
		return false;
	}
}