<?php
namespace org\rhaco\service;
/**
 * OpenID
 * @incomplete
 * @author tokushima
 * 
 * @see http://gitorious.org/lightopenid/lightopenid/trees/master
 * openid.php -> _extlibs/LightOpenID.php
 */
class OpenID extends \org\rhaco\flow\parts\RequestFlow{
	public function get_template_modules(){
		return array(
					new \org\rhaco\flow\module\Exceptions()
				);
	}
	/**
	 * @automap
	 * @request string $return_to
	 */
	public function index(){
		$openid = new \LightOpenID(\org\rhaco\Request::current_url());
		if($this->is_vars('return_to')) $openid->returnUrl = $this->in_vars('return_to');
		
		if(!$openid->mode){
			$this->rm_sessions('openid_attributes');
			
			if($this->is_post() && $this->is_vars('openid_identifier')){
				$openid->identity = $this->in_vars('openid_identifier');
				$openid->required = array('contact/email');
				$openid->optional = array('namePerson','namePerson/friendly');
				\org\rhaco\net\http\Header::redirect($openid->authUrl());
			}
		}else if($openid->mode == 'cancel'){
			$this->redirect_by_method('cancel');
		}else{
			$this->sessions('openid_attributes',$openid->getAttributes());
			$this->redirect_by_method('success');			
		}
	}
	/**
	 * @automap
	 * Enter description here ...
	 */
	public function return_to(){
		$openid = new \LightOpenID(\org\rhaco\Request::current_url());
		if($openid->mode == 'cancel'){
			$this->redirect_by_method('cancel');
		}else{
			$this->sessions('openid_attributes',$openid->getAttributes());
			var_dump($this->in_sessions('openid_attributes'));
		}
	}
	/**
	 * @automap
	 * Enter description here ...
	 */
	public function cancel(){
	}
	/**
	 * @automap
	 * Enter description here ...
	 */
	public function success(){
		var_dump($this->in_sessions('openid_attributes'));
	}
}