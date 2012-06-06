<?php
namespace org\rhaco\service;
/**
 * 
 * @incomplete
 * @author tokushima
 *
 */
class Facebook extends \org\rhaco\flow\parts\RequestFlow{
	private $client_id;
	private $client_secret;
	private $access_token;
	
	protected function __new__($client_id=null,$client_secret=null){
		$this->application($client_id, $client_secret);
		parent::__new__();
	}
	public function application($client_id,$client_secret){
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		return $this;
	}
	public function set_access_token($access_token){
		$this->access_token = $access_token;
		return $this;
	}
	public function get_access_token($scope=null){
		$current = \org\rhaco\Request::current_url();		
		$http = new \org\rhaco\net\Http();
		$code = $this->in_vars('code');
		
		if(empty($code)){
			$this->sessions('state',md5(uniqid(rand(),true)));		
			$http->vars('client_id',$this->client_id);
			$http->vars('redirect_uri',$current);
			$http->vars('state',$this->in_sessions('state'));
			$http->vars('scope',$scope);
			$http->do_redirect('https://www.facebook.com/dialog/oauth');
		}
		if($this->in_vars('state') == $this->in_sessions('state')){
			$http->vars('client_id',$this->client_id);
			$http->vars('client_secret',$this->client_secret);
			$http->vars('redirect_uri',$current);
			$http->vars('code',$code);
			$http->do_get('https://graph.facebook.com/oauth/access_token');
			parse_str($http->body(),$params);
			$this->access_token = $params['access_token'];
			return $this->access_token;
		}
		throw new \RuntimeException('');
	}
	public function me(){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me');
		return json_decode($http->body(),true);
	}
	public function albums(){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me/albums');
		return json_decode($http->body(),true);
	}
}