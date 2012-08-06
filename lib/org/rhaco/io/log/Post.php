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
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function debug(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function info(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function warn(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function error(\org\rhaco\Log $log,$id){
		if($this->url !== null){
			$this->http->vars($this->var_name,(string)$log);
			$this->http->do_post($this->url);
		}
	}
}
