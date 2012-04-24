<?php
namespace org\rhaco\service;
/**
 * OpenID
 * @incomplete
 * @author tokushima
 */
class OpenID extends \org\rhaco\flow\parts\RequestFlow{
	private $store_path;

	static public function __import__(){
		\org\rhaco\Pear::load(
			'Auth_OpenID_Consumer',
			'Auth_OpenID_FileStore',
			'Auth_OpenID_SReg',
			'Auth_OpenID_PAPE'
		);
		if(substr(strtolower(php_uname('s')),0,7) == 'windows'){
			if(!extension_loaded('curl')) throw new \RuntimeException('curl extension not found');
			if(!extension_loaded('openssl')) throw new \RuntimeException('openssl extension not found');
			define('Auth_OpenID_RAND_SOURCE',NULL);
		}else{
			if(!is_readable('/dev/urandom')) define('Auth_OpenID_RAND_SOURCE',NULL);
		}
	}
	
	protected function __init__(){
		$this->store_path = \org\rhaco\io\File::work_path('cacert.pem');
	}
	public function get_template_modules(){
		return array(
					new \org\rhaco\flow\module\Exceptions()
				);
	}
	
	
	/**
	 * @automap
	 */
	public function index(){
		
	}

	/**
	 * @automap
	 */
	public function begin(){
		$store = new \Auth_OpenID_FileStore($this->store_path);
		$consumer = new \Auth_OpenID_Consumer($store);
		
		$trust_root = $this->is_vars('root_url') ? $this->in_vars('root_url') : $this->in_sessions('root_url');
		$return_to = $this->is_vars('return_url') ? $this->in_vars('return_url') : $this->in_sessions('return_url');
		$this->sessions('root_url',$trust_root);
		$this->sessions('return_url',$return_to);
		
		if($this->is_vars('id')){
			$openid = $this->in_vars('id');
		
			$error_message = "";
			$auth_request = $consumer->begin($openid);
			if(!$auth_request) $error_message = "OpenID が正しくありません";
		
			if($error_message == ''){
				// nickname 等が必要無い場合はここを実行する必要はない
				$sreg_request = \Auth_OpenID_SRegRequest::build(
						// Required
						array('nickname'),
						// Optional
						array('fullname', 'email','gender')
					);
				if($sreg_request){
					$auth_request->addExtension($sreg_request);
				}
				if($auth_request->shouldSendRedirect()){
					$redirect_url = $auth_request->redirectURL( $trust_root, $return_to );
		
					if(\Auth_OpenID::isFailure($redirect_url)){
						$error_message = "サーバーにリダイレクトできません:".$redirect_url->message;
					}else{
						header("Location: ".$redirect_url);
					}
				}else{
					$form_id = 'openid_message';
					$form_html = $auth_request->htmlMarkup($trust_root,$return_to,false,array('id'=>$form_id));
					if(\Auth_OpenID::isFailure($form_html)){
						$error_message = "サーバーにリダイレクトできません(HTML):".$form_html->message;
					}else{
						\org\rhaco\Log::error($form_html);
						$this->vars('form_html',$form_html);
					}
				}
			}
		}
		$this->vars('error_message',$error_message);
	}
	
	/**
	 * @automap
	 */
	public function return_to(){
		$store = new \Auth_OpenID_FileStore($this->store_path);  
		$consumer = new \Auth_OpenID_Consumer($store);

		$trust_root = $this->is_vars('root_url') ? $this->in_vars('root_url') : $this->in_sessions('root_url');
		$return_to = $this->is_vars('return_url') ? $this->in_vars('return_url') : $this->in_sessions('return_url');
		$this->sessions('root_url',$trust_root);
		$this->sessions('return_url',$return_to);		
		
		$response = $consumer->complete($return_to);
		if($response->status == Auth_OpenID_CANCEL) throw new \LogicException('キャンセルされました');
		if($response->status == Auth_OpenID_FAILURE) throw new \LogicException($response->message);
		if($response->status == Auth_OpenID_SUCCESS){
			$sreg_resp = \Auth_OpenID_SRegResponse::fromSuccessResponse($response);
			$sreg = $sreg_resp->contents();
			$id = $response->getDisplayIdentifier();
			
			foreach($sreg as $k => $v){
				$this->vars($k,$v);
				$this->sessions($k,$v);
			}
			$this->vars('id',$id);
			$this->sessions('id',$id);
		}
	}
}