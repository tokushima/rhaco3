<?php
namespace org\rhaco\service;
/**
 * @see http://instagram.com/developer/clients/manage/
 * @see http://www.dcrew.jp/ja-instagram-api-doc-v1/
 * @author tokushima
 *
 */
class Instagram{
	private $client_id;
	private $client_secret;
	private $access_token;
	private $code;
	private $me_id;
	
	public function __construct($client_id,$client_secret){
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	/**
	 * アクセストークンを取得する
	 * @return string
	 */
	public function access_token(){
		if(empty($this->access_token)) $this->get_access_token();
		return $this->access_token;
	}
	/**
	 * アクセストークンをセットする
	 * @param string $access_token
	 * @return $this
	 */
	public function set_access_token($access_token){
		$this->access_token = $access_token;
		return $this;
	}
	/**
	 * facebook idを取得する
	 * @return integer
	 */
	public function me_id(){
		if(empty($this->me_id)) $this->me();
		return $this->me_id;
	}
	/**
	 * facebook idを設定する
	 * @param integer $me_id
	 * @return $this
	 */
	public function set_me_id($me_id){
		$this->me_id = $me_id;
		return $this;
	}
	/**
	 * アクセストークンを取得する
	 * @param string $scope 
	 * @param string $redirect_url
	 * @param boolean $reset
	 * @return $this
	 */
	public function get_access_token($scope='basic',$redirect_url=null,$reset=false){
		if(empty($redirect_url)) $redirect_url = \org\rhaco\Request::current_url();
		$sess = new \org\rhaco\net\Session();
		$http = new \org\rhaco\net\Http();
		$req = new \org\rhaco\Request();
		
		if(empty($this->code) || $reset){
			if($req->is_vars('code')){
				$this->code = $req->in_vars('code');
			}else{
				$state = md5(uniqid(rand(),true));
				if(strpos($redirect_url,'?') === false) $redirect_url = $redirect_url.'?';
				if(substr($redirect_url,-1) !== '&') $redirect_url = $redirect_url.'&';
				$redirect_url = $redirect_url.'state='.$state;
				
				$sess->vars('state',$state);
				$http->vars('redirect_uri',$redirect_url);
				$http->vars('client_id',$this->client_id);
				$http->vars('scope',empty($scope) ? 'basic' : str_replace(',',' ',$scope));
				$http->vars('response_type','code');
				$http->do_redirect('https://api.instagram.com/oauth/authorize');
			}
		}
		if($req->is_vars('state') && $req->in_vars('state') == $sess->in_vars('state')){
			if(strpos($redirect_url,'?') === false) $redirect_url = $redirect_url.'?';
			if(substr($redirect_url,-1) !== '&') $redirect_url = $redirect_url.'&';
			$redirect_url = $redirect_url.'state='.$req->in_vars('state');
			
			$http->vars('client_id',$this->client_id);
			$http->vars('client_secret',$this->client_secret);
			$http->vars('redirect_uri',$redirect_url);
			$http->vars('grant_type','authorization_code');
			$http->vars('code',$this->code);
			$http->do_post('https://api.instagram.com/oauth/access_token');

			\org\rhaco\Log::error(json_decode($http->body(),true));
			$data = json_decode($http->body(),true);
			$this->access_token = $data['access_token'];
		}
		return $this;
	}
	/**
	 * ユーザ情報を取得する
	 * @return array
	 */
	public function me(){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();

		$http->vars('access_token',$this->access_token);
		$http->do_get('https://api.instagram.com/v1/users/self');
		$data = json_decode($http->body(),true);
		$this->check_error($data);
		$this->me_id = $data['data']['id'];
		return $data['data'];
	}
	/**
	 * ユーザのフィードを取得する
	 * @return array
	 */
	public function feed(){
		return $this->get('https://api.instagram.com/v1/users/self/feed');
	}
	public function media_recent(){
		return $this->get('https://api.instagram.com/v1/users/self/media/recent/');
	}
	private function get($url,$vars=array()){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();

		$http->vars('access_token',$this->access_token);
		$http->do_get($url);
		$data = json_decode($http->body(),true);		
		$this->check_error($data);
		return $data['data'];
	}
	private function check_error($data){
		if(isset($data['error'])){
			if($data['error']['type'] == 'OAuthException') throw new \org\rhaco\service\Instagram\exception\OAuthException($data['error']['message']);
		}
	}
}