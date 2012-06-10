<?php
namespace org\rhaco\service;
/**
 * 
 * @incomplete
 * @author tokushima
 * @see https://developers.facebook.com/docs/reference/api/photo/
 */
class Facebook{
	private $client_id;
	private $client_secret;
	private $access_token;
	private $code;
	private $permissions;
	private $me_id;
	
	public function __construct($client_id,$client_secret){
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	public function set_access_token($access_token){
		$this->access_token = $access_token;
		return $this;
	}
	public function me_id($me_id){
		$this->me_id = $me_id;
		return $this;
	}
	public function require_permissions($perm){
		if(!$this->has_permissions($perm)) $this->get_access_token($perm,null,true);
		return $this;
	}
	public function has_permissions($perm){
		if(empty($this->me_id)){
			if(empty($this->access_token)) $this->get_access_token($perm);			
			$this->me();
		}
		if(empty($this->permissions)){
			$http = new \org\rhaco\net\Http();
			$http->vars('access_token',$this->access_token);
			$http->do_get('https://graph.facebook.com/'.$this->me_id.'/permissions');
			$data = json_decode($http->body(),true);
			$this->check_error($data);
			$this->permissions = $data['data'][0];
		}
		foreach(explode(',',$perm) as $p){
			if(!isset($this->permissions[$p])) return false;
		}
		return true;
	}
	public function get_access_token($scope=null,$redirect_url=null,$reset=false){
		if(empty($redirect_url)) $redirect_url = \org\rhaco\Request::current_url();
		$sess = new \org\rhaco\net\Session();
		$http = new \org\rhaco\net\Http();
		$req = new \org\rhaco\Request();
		
		if(empty($this->code) || $reset){
			if($req->is_vars('code')){
				$this->code = $req->in_vars('code');
			}else{
				$sess->vars('state',md5(uniqid(rand(),true)));		
				$http->vars('client_id',$this->client_id);
				$http->vars('redirect_uri',$redirect_url);
				$http->vars('state',$sess->in_vars('state'));
				$http->vars('scope',$scope);
				$http->do_redirect('https://graph.facebook.com/oauth/authorize');
			}
		}
		if($req->in_vars('state') == $sess->in_vars('state')){
			$http->vars('client_id',$this->client_id);
			$http->vars('client_secret',$this->client_secret);
			$http->vars('redirect_uri',$redirect_url);
			$http->vars('code',$this->code);
			$http->do_get('https://graph.facebook.com/oauth/access_token');
			parse_str($http->body(),$params);
			$this->access_token = $params['access_token'];
		}
		return $this;
	}
	public function me(){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me');
		$data = json_decode($http->body(),true);
		$this->check_error($data);
		$this->me_id = $data['id'];
		return $data;
	}
	public function albums(&$next_url=null){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me/albums');
		$data = json_decode($http->body(),true);
		$this->check_error($data);
		$next_url = $this->check_next($data);
		
		return $data['data'];
	}
	public function photos($album_id,$next_url=null){
		if(empty($this->access_token)) $this->get_access_token();
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/'.$album_id.'/photos');
		$data = json_decode($http->body(),true);
		$this->check_error($data);
		$next_url = $this->check_next($data);
		return $data['data'];
	}
	private function check_error($data){
		if(isset($data['error'])){
			if($data['error']['type'] == 'OAuthException') throw new \org\rhaco\service\Facebook\exception\OAuthException($data['error']['message']);
			if($data['error']['type'] == 'GraphMethodException') throw new \org\rhaco\service\Facebook\exception\GraphMethodException($data['error']['message']);
		}
	}
	private function check_next($data){
		$next = null;
		if(isset($data['paging'])){
			if(isset($data['paging']['next'])) $next = $data['paging']['next'];
		}
		return $next;
	}
}