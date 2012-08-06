<?php
namespace org\rhaco\service;
/**
 * Twitterを操作する
 * @see https://dev.twitter.com/apps/
 * @see https://dev.twitter.com/apps/new
 * @see https://dev.twitter.com/docs
 * @author tokushima
 *
 */
class Twitter{
	private $consumer_key;
	private $consumer_secret;
	private $access_token;
	private $access_key;
	
	public function __construct($consumer_key,$consumer_secret){
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
	}
	private function enc($var){
		return str_replace(array('+','%7E'),array(' ','~'),rawurlencode($var));
	}
	private function authorization_query($method,$url,$oauth_consumer_key,$oauth_consumer_secret,$opt=array()){
		$authorization = array_merge($opt,array(
			'oauth_nonce'=>md5(uniqid(rand(),true)),
			'oauth_timestamp'=>time(),
			'oauth_consumer_key'=>$oauth_consumer_key,
			'oauth_signature_method'=>'HMAC-SHA1',
			'oauth_version'=>'1.0',
		));
		ksort($authorization);
		
		$signature_array = array();
		foreach($authorization as $k => $v) $signature_array[] = $this->enc($k).'='.$this->enc($v);
		$signature = implode('&',$signature_array);
		
		$signature = $method.'&'.$this->enc($url).'&'.$this->enc($signature);	
		$key = $this->enc($oauth_consumer_secret).'&'.(isset($authorization['oauth_token_secret']) ? $this->enc($authorization['oauth_token_secret']) : '');
		$authorization['oauth_signature'] = base64_encode(hash_hmac('sha1',$signature,$key,true));
		return $authorization;
	}
	private function authorization_oauth_header($authorization_query){
		$oauth_head_array = array();
		foreach($authorization_query as $k => $v){
			if(substr($k,0,5) == 'oauth') $oauth_head_array[$k] = sprintf('%s="%s"',$this->enc($k),$this->enc($v));
		}
		return 'OAuth '.implode(',',$oauth_head_array);
	}
	/**
	 * request_tokenを取得する
	 * @param string $callback_url
	 */
	public function get_request_token($callback_url){
		$request_token_url = 'https://api.twitter.com/oauth/request_token';
		$authorization_query = $this->authorization_query(
									'GET',
									$request_token_url,
									$this->consumer_key,
									$this->consumer_secret,
									array('oauth_callback'=>$callback_url)
								);		
		$http = new \org\rhaco\net\Http();
		$http->cp($authorization_query);
		$http->do_get($request_token_url);
		$http->do_redirect('https://api.twitter.com/oauth/authorize?'.$http->body());
	}
	/**
	 * access_tokenを取得する
	 * @return $this
	 * @throws Twitter\exception\OAuthException
	 */
	public function get_access_token(){
		if(!empty($this->access_token)) return $this;
		$req = new \org\rhaco\Request();
		if(!$req->is_vars('denied') && $req->is_vars('oauth_verifier')){
			$access_token_url = 'https://api.twitter.com/oauth/access_token';
			$authorization_query = $this->authorization_query(
										'GET',
										$access_token_url,
										$this->consumer_key,
										$this->consumer_secret
										,array(
											'oauth_token'=>$req->in_vars('oauth_token')
											,'oauth_verifier'=>$req->in_vars('oauth_verifier')
										)
									);
			$http = new \org\rhaco\net\Http();
			$http->header('Authorization',$this->authorization_oauth_header($authorization_query));
			$http->do_get($access_token_url);
			
			if($http->status() == 200){
				parse_str($http->body(),$param);
				$this->access_token = $param['oauth_token'];
				$this->access_key = $param['oauth_token_secret'];
				
				return $this;
			}
		}
		throw new Twitter\exception\OAuthException('request token failed');
	}
	/**
	 * Access tokenをセットする
	 * @param string $access_token
	 * @param string $access_token_secret
	 * @return $this
	 */
	public function set_access_token($access_token,$access_token_secret){
		$this->access_token = $access_token;
		$this->access_key = $access_token_secret;
		return $this;
	}
	/**
	 * ユーザの情報を返す
	 * @return array
	 */
	public function verify_credentials(){
		$verify_credentials_url = 'https://api.twitter.com/1/account/verify_credentials.json';
		$authorization_query = $this->authorization_query(
								'GET',
								$verify_credentials_url,
								$this->consumer_key,
								$this->consumer_secret,
								array(
									'oauth_token'=>$this->access_token
									,'oauth_token_secret'=>$this->access_key
								));
		$http = new \org\rhaco\net\Http();
		$http->header('Authorization',$this->authorization_oauth_header($authorization_query));	
		$http->do_get($verify_credentials_url);
		$result = json_decode($http->body(),true);
		if(isset($result['error'])) throw new Twitter\exception\OAuthException('request token failed');
		return $result;
	}
	/**
	 * Access tokenを取得する
	 * @return string
	 */
	public function access_token(){
		if(empty($this->access_token)) $this->auth();
		return $this->access_token;
	}
	/**
	 * Access token secreを取得する
	 * @return string
	 */
	public function access_key(){
		if(empty($this->access_key)) $this->auth();
		return $this->access_key;
	}
	/**
	 * 認証する
	 * @throws Twitter\exception\OAuthException
	 */
	public function auth(){
		if(!empty($this->access_token)) return $this;
		$req = new \org\rhaco\Request();
		
		try{
			if($req->in_vars('get_request_token') === 'true'){
				$this->get_access_token();
				return $this;
			}
		}catch(Twitter\exception\OAuthException $e){
		}
		$callback_url = \org\rhaco\Request::current_url();
		if(strpos($callback_url,'?') === false) $callback_url = $callback_url.'?';
		if(substr($callback_url,-1) !== '&') $callback_url = $callback_url.'&';
		$callback_url = $callback_url.'get_request_token=true';
		$this->get_request_token($callback_url);

		throw new Twitter\exception\OAuthException('request token failed');
	}
}
