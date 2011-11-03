<?php
namespace org\rhaco\io\log;
/**
 * ログをPOSTする
 * @author tokushima
 */
class Post{
	private $url;
	private $http;
	private $var_name;
		
	public function __construct($url=null,$var_name='msg'){
		$this->http = new \org\rhaco\net\Http();
		$this->url = $url;
		$this->var_name = $var_name;
	}
	public function debug(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	public function info(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	public function warn(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	public function error(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
}
