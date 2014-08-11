<?php
namespace org\rhaco;
/**
 * リクエストを処理する
 * @author tokushima
 */
class Request implements \IteratorAggregate{
	private $vars = array();
	private $files = array();
	private $args;
	private $_method;

	public function __construct(){
		if('' != ($pathinfo = (array_key_exists('PATH_INFO',$_SERVER)) ? $_SERVER['PATH_INFO'] : null)){
			if($pathinfo[0] != '/') $pathinfo = '/'.$pathinfo;
			$this->args = preg_replace("/(.*?)\?.*/","\\1",$pathinfo);
		}
		if(isset($_SERVER['REQUEST_METHOD'])){
			$args = func_get_args();
			if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
				if(isset($_POST) && is_array($_POST)){
					if(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])){
						$this->_method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
					}
					foreach($_POST as $k => $v){
						$this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
					}
				}
				if(isset($_FILES) && is_array($_FILES)){
					$marge_func = function($arr,$pk,$files,&$map) use(&$marge_func){
						if(is_array($arr)){
							foreach($arr as $k => $v){
								$marge_func($v,array_merge($pk,array($k)),$files,$map);
							}
						}else{
							$ks = implode('',array_map(function($v){ return '[\''.$v.'\']';},$pk));
							foreach(array('name','type','tmp_name','tmp_name','size') as $k){
								eval('$map'.$ks.'[\''.$k.'\']=$files[\''.$k.'\']'.$ks.';');
							}
						}
					};
					foreach($_FILES as $k => $v){
						if(is_array($v['name'])){
							$this->files[$k] = array();
							$marge_func($v['name'],array(),$v,$this->files[$k]);
						}else{
							$this->files[$k] = $v;
						}
					}
				}
			}else if(isset($_GET) && is_array($_GET)){
				foreach($_GET as $k => $v) $this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
			}
			if(isset($_COOKIE) && is_array($_COOKIE)){
				foreach($_COOKIE as $k => $v){
					if(ctype_alpha($k[0]) && $k != session_name()) $this->vars[$k] = $v;
				}
			}
			if(isset($this->vars['_method'])){
				if(empty($this->_method)){
					$this->_method = strtoupper($this->vars['_method']);
				}
				unset($this->vars['_method']);
			}
			if(empty($this->_method)){
				$this->_method = $_SERVER['REQUEST_METHOD'];
			}
			if(
				isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json' &&
				($this->_method == 'PUT' || $this->_method == 'DELETE' || $this->_method == 'POST')
			){
				$json = json_decode(file_get_contents('php://input'),true);
				if(is_array($json)){
					foreach($json as $k => $v){
						$this->vars[$k] = $v;
					}
				}
			}
		}else if(isset($_SERVER['argv'])){
			$argv = $_SERVER['argv'];
			array_shift($argv);
			if(isset($argv[0]) && $argv[0][0] != '-'){
				$this->args = implode(' ',$argv);
			}else{
				$size = sizeof($argv);
				for($i=0;$i<$size;$i++){
					if($argv[$i][0] == '-'){
						if(isset($argv[$i+1]) && $argv[$i+1][0] != '-'){
							$this->vars[substr($argv[$i],1)] = $argv[$i+1];
							$i++;
						}else{
							$this->vars[substr($argv[$i],1)] = '';
						}
					}
				}
			}
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator(){
		return new \ArrayIterator($this->vars);
	}
	/**
	 * 現在のURLを返す
	 * @param number $port_https
	 * @param number $port_http
	 * @return string
	 */
	static public function current_url($port_https=443,$port_http=80){
		$server = self::host($port_https,$port_http);
		if(empty($server)) return null;
		$path = isset($_SERVER['REQUEST_URI']) ?
					preg_replace("/^(.+)\?.*$/","\\1",$_SERVER['REQUEST_URI']) :
					(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '') : '');
		return $server.$path;
	}
	/**
	 * 現在のホスト
	 * @param number $port_https
	 * @param number $port_http
	 * @return string
	 */
	static public function host($port_https=443,$port_http=80){
		$port = $port_http;
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
			$port = $port_https;
		}else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'){
			$port = $port_https;
		}else if(isset($_SERVER['HTTP_X_FORWARDED_PORT'])){
			$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
		}else if(isset($_SERVER['SERVER_PORT']) && !isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
			$port = $_SERVER['SERVER_PORT'];
		}
		$server = preg_replace("/^(.+):\d+$/","\\1",isset($_SERVER['HTTP_X_FORWARDED_HOST']) ?
					$_SERVER['HTTP_X_FORWARDED_HOST'] :
					(
						isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
						(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '')
					));
		if($port != $port_http && $port != $port_https) $server = $server.':'.$port;
		if(empty($server)) return null;
		return (($port == $port_https) ? 'https' : 'http').'://'.preg_replace("/^(.+?)\?.*/","\\1",$server);
	}
	/**
	 * 現在のリクエストクエリを返す
	 * @param boolean $sep 先頭に?をつけるか
	 * @return string
	 */
	static public function request_string($sep=false){
		$query = ((isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'].'&' : '').file_get_contents('php://input');
		return (($sep && !empty($query)) ? '?' : '').$query;
	}
	/**
	 * GET
	 * @return boolean
	 */
	public function is_get(){
		return ($this->_method == 'GET');
	}
	/**
	 * POST
	 * @return boolean
	 */
	public function is_post(){
		return ($this->_method == 'POST');
	}
	/**
	 * PUT
	 * @return boolean
	 */
	public function is_put(){
		return ($this->_method == 'PUT');
	}
	/**
	 * DLETE
	 * @return boolean
	 */
	public function is_delete(){
		return ($this->_method == 'DELETE');
	}
	/**
	 * CLIで実行されたか
	 * @return boolean
	 */
	public function is_cli(){
		return (php_sapi_name() == 'cli' || !isset($_SERVER['REQUEST_METHOD']));
	}
	/**
	 * ユーザエージェント
	 * @return string
	 */
	static public function user_agent(){
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}
	/**
	 * クッキーへの書き出し
	 * @param string $name 書き込む変数名
	 * @param int $expire 有効期限(秒) (+ time)
	 * @param string $path パスの有効範囲
	 * @param boolean $subdomain サブドメインでも有効とするか
	 * @param boolean $secure httpsの場合のみ書き出しを行うか
	 */
	public function write_cookie($name,$expire=null,$path=null,$subdomain=false,$secure=false){
		if($expire === null) $expire = 1209600;
		$domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		if($subdomain && substr_count($domain,'.') >= 2) $domain = preg_replace("/.+(\.[^\.]+\.[^\.]+)$/","\\1",$domain);
		if(empty($path)) $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		setcookie($name,$this->in_vars($name),time() + $expire,$path,$domain,$secure);
	}
	/**
	 * クッキーから削除
	 * 登録時と同条件のものが削除される
	 * @param string $name クッキー名
	 */
	public function delete_cookie($name,$path=null,$subdomain=false,$secure=false){
		$domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		if($subdomain && substr_count($domain,'.') >= 2) $domain = preg_replace("/.+(\.[^\.]+\.[^\.]+)$/","\\1",$domain);
		if(empty($path)) $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		setcookie($name,null,time() - 1209600,$path,$domain,$secure);
		$this->rm_vars($name);
	}
	/**
	 * クッキーから呼び出された値か
	 * @param string $name
	 * @return boolean
	 */
	public function is_cookie($name){
		return (isset($_COOKIE[$name]));
	}
	/**
	 * pathinfo または argv
	 * @return string
	 */
	public function args(){
		return $this->args;
	}
	/**
	 * 変数の設定
	 * @param string $key
	 * @param mixed $value
	 */
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	/**
	 * 変数の取得
	 * @param string $n
	 * @param mixed $d 未定義の場合の値
	 * @return mixed
	 */
	public function in_vars($n,$d=null){
		return array_key_exists($n,$this->vars) ? $this->vars[$n] : $d;
	}
	/**
	 * キーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_vars($n){
		return array_key_exists($n,$this->vars);
	}
	/**
	 * 変数の削除
	 */
	public function rm_vars(){
		if(func_num_args() === 0){
			$this->vars = array();
		}else{
			foreach(func_get_args() as $n) unset($this->vars[$n]);
		}
	}
	/**
	 * 変数の一覧を返す
	 * @return array
	 */
	public function ar_vars(){
		return $this->vars;
	}
	public function ar_files(){
		return $this->files;
	}
	/**
	 * 添付ファイル情報の取得
	 * @param string $n
	 * @return array
	 */
	public function in_files($n){
		return array_key_exists($n,$this->files) ? $this->files[$n] :  null;
	}
	/**
	 * 添付されたファイルがあるか
	 * @param array $file_info
	 * @return boolean
	 */
	public function has_file($file_info){
		return isset($file_info['tmp_name']) && is_file($file_info['tmp_name']);
	}
	/**
	 * 添付ファイルのオリジナルファイル名の取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_original_name($file_info){
		return isset($file_info['name']) ? $file_info['name'] : null;
	}
	/**
	 * 添付ファイルのファイルパスの取得
	 * @param array $file_info
	 * @return string
	 */
	public function file_path($file_info){
		return isset($file_info['tmp_name']) ? $file_info['tmp_name'] : null;
	}
	/**
	 * 添付ファイルを移動します
	 * @param array $file_info
	 * @param string $newname
	 */
	public function move_file($file_info,$newname){
		if(!$this->has_file($file_info)) throw new \LogicException('file not found ');
		if(!is_dir(dirname($newname))) \org\rhaco\io\File::mkdir(dirname($newname));
		copy($file_info['tmp_name'],$newname);
		chmod($newname,0777);
		unlink($file_info['tmp_name']);
	}
}
