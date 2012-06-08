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
	
	public function __construct($client_id,$client_secret){
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	public function set_access_token($access_token){
		$this->access_token = $access_token;
		return $this;
	}
	public function get_access_token($scope=null){
		$current = \org\rhaco\Request::current_url();		
		$http = new \org\rhaco\net\Http();
		$req = new \org\rhaco\Request();
		$sess = new \org\rhaco\net\Session();
		
		$code = $req->in_vars('code');
		
		if(empty($code)){
			$sess->vars('state',md5(uniqid(rand(),true)));		
			$http->vars('client_id',$this->client_id);
			$http->vars('redirect_uri',$current);
			$http->vars('state',$sess->in_vars('state'));
			$http->vars('scope',$scope);
			$http->do_redirect('https://www.facebook.com/dialog/oauth');
		}
		if($req->in_vars('state') == $sess->in_vars('state')){
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
		if(empty($this->access_token)) $this->get_access_token('user_photos');
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me');
		return json_decode($http->body(),true);
	}
	public function albums(){
		if(empty($this->access_token)) $this->get_access_token('user_photos');
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/me/albums');
		$data = json_decode($http->body(),true);
		// TODO paginatorどうしよ
		return $data['data'];
	}
	public function photos($album_id){
		if(empty($this->access_token)) $this->get_access_token('user_photos');
		$http = new \org\rhaco\net\Http();
		$http->vars('access_token',$this->access_token);
		$http->do_get('https://graph.facebook.com/'.$album_id.'/photos');
		$data = json_decode($http->body(),true);
		// TODO paginatorどうしよ
		return $data['data'];
	}
}