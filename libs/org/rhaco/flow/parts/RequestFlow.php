<?php
namespace org\rhaco\flow\parts;
/**
 * Requestを含むFlowインタフェース
 * @author tokushima
 * @conf string{} $session_group セッションの範囲名, エントリ名=>セッショングループ名('*' or '\w+')
 */
class RequestFlow extends \org\rhaco\Object implements \IteratorAggregate, \org\rhaco\flow\FlowInterface{
	private $put_block;
	private $map_args;
	private $package_maps = array();
	private $maps = array();
	private $select_map_name;
	private $select_map;
	private $theme;

	private $sess;
	private $req;
	private $login_id;
	private $anon_login;
	
	protected function __new__(){
		$d = debug_backtrace(false);
		$d = array_pop($d);
		$dir = dirname($d['file']);
		$entry = substr(basename($d['file']),0,-4);
		$session_group = \org\rhaco\Conf::get('session_group');
		$group = (isset($session_group[$entry])) ? $session_group[$entry] : '';
		$sess_name = ($group == '*') ? $dir : ($group == '' ? ($dir.'/'.$entry) : (($group[0] == '@') ? $group : $dir.'#'.$group));
		$this->req = new \org\rhaco\Request();
		$this->sess = new \org\rhaco\net\Session(md5($sess_name));
		foreach($this->sess->in_vars('_saved_vars_',array()) as $k => $v) $this->req->vars($k,$v);
		foreach($this->sess->in_vars('_saved_exceptions_',array()) as $e) \org\rhaco\Exceptions::add($e[0],$e[1]);
		$this->sess->rm_vars('_saved_vars_');
		$this->sess->rm_vars('_saved_exceptions_');
		$this->login_id = $sess_name.'_LOGIN_';
	}
	protected function __anon__($d){
		$this->anon_login = self::anon_decode($d,'login');
	}
	/**
	 * ログインしているユーザのモデル
	 * @throws \LogicException
	 * @return mixed
	 */
	public function user(){
		if(func_num_args() > 0){
			$user = func_get_arg(0);
			if(isset($this->anon_login['type']) && !empty($this->anon_login['type'])){
				$class = str_replace('.',"\\",$this->anon_login['type']);
				if($class[0] != "\\") $class= "\\".$class;
				if(!($user instanceof $class)) throw new \LogicException('user must be an of '.$this->anon_login['type']);
			}
			$this->sessions($this->login_id.'USER',$user);
		}
		return $this->in_sessions($this->login_id.'USER');
	}
	/**
	 * テーマをセットする
	 * @param string $theme
	 */
	protected function theme($theme){
		$this->theme = $theme;
	}
	/**
	 * (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator(){
		return $this->req->getIterator();
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::set_maps()
	 */
	public function set_maps($maps){
		$this->maps = $maps;
		foreach($maps as $p => $m){
			if($m['name'] == $this->select_map_name){
				$this->select_map = $m;
				break;
			}
		}
		if(isset($this->select_map['='])){
			foreach($maps as $u => $m){
				if(isset($m['=']) && $m['pkg_id'] == $this->select_map['pkg_id']) $this->package_maps[$u] = $maps[$u];
			}
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::set_select_map_name()
	 */
	public function set_select_map_name($name){
		$this->select_map_name = $name;
	}
	/**
	 * mapを返す
	 * @return array
	 */
	protected function maps(){
		return $this->maps;
	}
	/**
	 * blockを上書きする
	 * @param sting $path
	 */
	protected function set_block($path){
		$this->put_block = $path;
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::get_block()
	 */
	public function get_block(){
		return $this->put_block;
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::get_theme()
	 */
	public function get_theme(){
		return $this->theme;
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::get_template_modules()
	 */
	public function get_template_modules(){
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::set_args()
	 */
	public function set_args($args){
		$this->map_args = $args;
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::before()
	 */
	public function before(){
		if(method_exists($this,'__before__')) $this->__before__();
		if($this->has_object_module('before_flow_handle')){
			/**
			 * 前処理
			 * @param self $this
			 */
			$this->object_module('before_flow_handle',$this);
		}
		if(!$this->is_login() && ((isset($this->anon_login['require']) && $this->anon_login['require'] === true) || $this->has_object_module('login_condition'))) $this->login_required();
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::after()
	 */
	public function after(){
		if(method_exists($this,'__after__')) $this->__after__();
		if($this->has_object_module('after_flow_handle')){
			/**
			 * 後処理
			 * @param self $this
			 */
			$this->object_module('after_flow_handle',$this);
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see libs/org/rhaco/flow/org\rhaco\flow.FlowInterface::exception()
	 */
	public function exception(){
		if($this->has_object_module('exception_flow_handle')){
			/**
			 * 例外発生時の処理
			 * @param self $this
			 */
			$this->object_module('exception_flow_handle',$this);
		}
	}
	/**
	 * ログインしていない場合にログイン処理を実行する
	 * @throws \LogicException
	 */
	public function login_required(){
		if(!$this->is_login() && isset($this->select_map['method']) && $this->select_map['method'] != 'do_login'){
			if($this->has_object_module('before_login_required')) {
				/**
				 * 未ログイン時のログイン処理の前処理
				 * @param self $this
				 */
				$this->object_module('before_login_required',$this);
			}
			if(!$this->is_login()){
				if(!$this->is_sessions('logined_redirect_to')){
					$current = \org\rhaco\Request::current_url().\org\rhaco\Request::request_string(true);
					foreach($this->maps as $k => $m){
						if((isset($m['name']) && $m['name'] == 'logout') || (isset($m['method']) && $m['method'] == 'do_logout')){
							if(isset($m['format']) && $current == $m['format']) $current = null;
						}
					}
					if($current !== null) $this->sessions('logined_redirect_to',$current);
				}
				$this->save_current_vars();
				foreach($this->package_maps as $k => $m){
					if($m['method'] == 'do_login' && isset($m['format'])) return \org\rhaco\net\http\Header::redirect($m['format']);
				}
				foreach($this->maps as $k => $m){
					if(((isset($m['name']) && $m['name'] == 'login')) || (isset($m['method']) && $this->select_map['class'] == $m['class'] && $m['method'] == 'do_login') 
						&& isset($m['format'])) return \org\rhaco\net\http\Header::redirect($m['format']);
				}
				throw new \LogicException('name `login` not found');
			}
		}
	}
	/**
	 * POSTされたか
	 * @return boolean
	 */
	public function is_post(){
		return $this->req->is_post();
	}
	/**
	 * 添付ファイル情報の取得
	 * @param string $n
	 * @return array
	 */
	public function in_files($n){
		return $this->req->in_files($n);
	}
	/**
	 * 添付されたファイルがあるか
	 * @param array $file_info
	 * @return boolean
	 */
	public function has_file($file_info){
		return $this->req->has_file($file_info);
	}
	/**
	 * 添付ファイルのオリジナルファイル名の取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_original_name($file_info){
		return $this->req->file_original_name($file_info);
	}
	/**
	 * 添付ファイルのファイルパスの取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_path($file_info){
		return $this->req->file_path($file_info);
	}
	/**
	 * 添付ファイルを移動します
	 * @param array $file_info
	 * @param string $newname
	 */
	public function move_file($file_info,$newname){
		return $this->req->move_file($file_info,$newname);
	}
	/**
	 * クッキーへの書き出し
	 * @param string $name 書き込む変数名
	 * @param int $expire 有効期限 (+ time)
	 * @param string $path パスの有効範囲
	 * @param boolean $subdomain サブドメインでも有効とするか
	 * @param boolean $secure httpsの場合のみ書き出しを行うか
	 */
	protected function write_cookie($name,$expire=null,$path=null,$subdomain=false,$secure=false){
		$this->req->write_cookie($name,$expire,$path,$subdomain,$secure);
	}
	/**
	 * クッキーから削除
	 * 登録時と同条件のものが削除される
	 * @param string $name クッキー名
	 */
	protected function delete_cookie($name,$path=null,$subdomain=false,$secure=false){
		$this->req->delete_cookie($name,$path,$subdomain,$secure);
	}
	/**
	 * クッキーから呼び出された値か
	 * @param string $name
	 * @return boolean
	 */
	protected function is_cookie($name){
		return $this->req->is_cookie($name);
	}	
	/**
	 * pathinfo または argv
	 * @return string
	 */
	protected function args(){
		return $this->req->args();
	}
	/**
	 * Exceptionを保存し、次回リクエスト時に展開する(org.rhaco.Exceptionsに格納される)
	 * @param Exception $exception
	 * @param string $name
	 */
	protected function save_exception(\Exception $exception,$name=null){
		$exceptions = $this->in_sessions('_saved_exceptions_');
		if(!is_array($exceptions)) $exceptions = array();
		$exceptions[] = array($exception,$name);
		$this->sessions('_saved_exceptions_',$exceptions);
	}
	/**
	 * 現在の変数を全て保存し、次回リクエスト時に展開する
	 * @throws InvalidArgumentException
	 */
	protected function save_current_vars(){
		foreach($this->req as $k => $v){
			if(is_object($v)){
				$ref = new \ReflectionClass(get_class($v));
				if(substr($ref->getFileName(),-4) !== '.php') throw new \InvalidArgumentException($k.' is not permitted');
			}
		}
		$vars = array();
		foreach($this->req as $k => $v) $vars[$k] = $v;
		$this->sessions('_saved_vars_',$vars);
	}
	/**
	 * 値をセットする
	 * @param string $key
	 * @param mixed $val
	 */
	public function vars($key,$val){
		$this->req->vars($key,$val);
	}
	/**
	 * 定義済みの値から一つ取得する
	 * @param string $n 取得する定義名
	 * @param mixed $d 値が存在しない場合の代理値
	 * @return mixed
	 */
	public function in_vars($n,$d=null){
		return $this->req->in_vars($n,$d);
	}
	/**
	 * 値を削除する
	 * @param string $n 削除する定義名
	 */
	public function rm_vars($n=null){
		call_user_func_array(array($this->req,'rm_vars'),func_get_args());
	}
	/**
	 * 指定のキーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_vars($n){
		return $this->req->is_vars($n);
	}
	/**
	 * 定義済みの一覧を返す
	 * @return array
	 */
	public function ar_vars(){
		return $this->req->ar_vars();
	}
	/**
	 * セッションにセットする
	 * @param string $key
	 * @param mixed $val
	 */
	public function sessions($key,$val){
		$this->sess->vars($key,$val);
	}
	/**
	 * セッションから取得する
	 * @param string $n 取得する定義名
	 * @param mixed $d セッションが存在しない場合の代理値
	 * @return mixed
	 */
	public function in_sessions($n,$d=null){
		return $this->sess->in_vars($n,$d);
	}
	/**
	 * セッションから削除する
	 * @param string $n 削除する定義名
	 */
	public function rm_sessions($n){
		call_user_func_array(array($this->sess,'rm_vars'),func_get_args());
	}
	/**
	 * 指定のキーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_sessions($n){
		return $this->sess->is_vars($n);
	}
	/**
	 * 指定されたマップ名のURLを返す
	 * @param string $name
	 * @throws \LogicException
	 */
	protected function map_url($name){
		$args = func_get_args();
		$name = array_shift($args);
		$arg = $this->map_arg($name,null);
		if($arg === null) $arg = $name;
		foreach($this->maps as $k => $m){
			if($m['name'] == $arg) return vsprintf($m['format'],$args);
		}
		throw new \LogicException('map `'.$arg.'` not found');
	}
	/**
	 * 指定されたマップ名のURLへリダイレクトする
	 * @param string $name
	 */
	protected function redirect_by_map($name){
		$args = func_get_args();
		\org\rhaco\net\http\Header::redirect(call_user_func_array(array($this,'map_url'),$args));
	}
	/**
	 * 自身のメソッドにマッピングされたURLへリダイレクトする(パッケージのみ)
	 * @param string $method
	 */
	protected function redirect_by_method($method){
		$args = func_get_args();
		array_shift($args);
		foreach($this->package_maps as $u => $m){
			if($m['method'] == $method) return \org\rhaco\net\http\Header::redirect(vsprintf($m['format'],$args));
		}
	}
	/**
	 * mapで定義されたarg値
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	protected function map_arg($name,$default=null){
		return (array_key_exists($name,$this->map_args)) ? $this->map_args[$name] : $default;
	}	

	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function is_login(){
		return ($this->in_sessions($this->login_id) !== null);
	}
	/**
	 * ログイン
	 * @arg string $login_redirect ログイン後にリダイレクトされるマップ名
	 * @automap
	 */
	public function do_login(){
		if($this->is_login() || $this->silent() || $this->login()){
			$redirect_to = $this->in_sessions('logined_redirect_to');
			$this->rm_sessions('logined_redirect_to');
			/**
			 * ログイン成功時の処理
			 * @param self $this
			 */
			$this->object_module('after_do_login',$this);
			if(!empty($redirect_to)) \org\rhaco\net\http\Header::redirect($redirect_to);
			if($this->map_arg('login_redirect') !== null) $this->redirect_by_map('login_redirect');
			foreach($this->package_maps as $k => $m){
				if($m['method'] == 'index' && strpos($m['format'],'%s') === false) \org\rhaco\net\http\Header::redirect($m['format']);
			}
		}
		if(!$this->is_login()){
			\org\rhaco\net\http\Header::send_status(401);
			if(!\org\rhaco\Exceptions::has()) \org\rhaco\Exceptions::add(new \LogicException('Unauthorized'),'do_login');
		}
	}
	/**
	 * ログアウト
	 * @arg string $logout_redirect ログアウト後にリダイレクトされるマップ名
	 * @automap
	 */
	public function do_logout(){
		/**
		 * ログアウト前処理
		 * @param self $this
		 */
		$this->object_module('before_do_logout',$this);
		$this->rm_sessions('logined_redirect_to');
		$this->logout();
		if($this->map_arg('logout_redirect') !== null) $this->redirect_by_map('logout_redirect');
		foreach($this->package_maps as $k => $m){
			if($m['method'] == 'index' && strpos($m['format'],'%s') === false) \org\rhaco\net\http\Header::redirect($m['format']);
		}
		$this->vars('login',$this->is_login());
	}
	/**
	 * ログインする
	 * POSTの場合のみ処理される
	 * @return boolean
	 */
	public function login(){
		if($this->is_login()) return true;
		/**
		 * ログイン条件
		 * @param self $this
		 * @return boolean
		 */
		if(!$this->has_object_module('login_condition') || $this->object_module('login_condition',$this) === false){
			/**
			 * ログイン失敗
			 * @param self $this
			 */
			$this->object_module('login_invalid',$this);
			return false;
		}
		$this->sessions($this->login_id,$this->login_id);
		session_regenerate_id(true);
		/**
		 * ログインの後処理
		 * @param self $this
		 */
		$this->object_module('after_login',$this);
		return true;
	}
	/**
	 * 後処理、失敗処理の無いログイン
	 * GETの場合のみ処理される
	 * クッキーをもちいた自動ログイン等に利用する
	 * @return boolean
	 */
	public function silent(){
		if($this->is_login()) return true;
		/**
		 * ログイン条件
		 * @param self $this
		 * @return boolean
		 */
		if($this->is_post() || !$this->has_object_module('silent_login_condition') || $this->object_module('silent_login_condition',$this->req) === false){
			return false;
		}
		$this->sessions($this->login_id,$this->login_id);
		return true;
	}
	/**
	 * ログアウトする
	 */
	public function logout(){
		$this->rm_sessions($this->login_id.'USER');
		$this->rm_sessions($this->login_id);
		session_regenerate_id(true);
	}
	/**
	 * 何もしない
	 */
	final public function noop(){
	}
	/**
	 * 利用不可とする
	 * マッピングに利用する
	 */
	final public function method_not_allowed(){
		\org\rhaco\net\http\Header::send_status(405);
		throw new \LogicException('Method Not Allowed');
	}
}
