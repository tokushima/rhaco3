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

	public function __construct(){
		if('' != ($pathinfo = (array_key_exists('PATH_INFO',$_SERVER)) ? $_SERVER['PATH_INFO'] : null)){
			if($pathinfo[0] != '/') $pathinfo = '/'.$pathinfo;
			$this->args = preg_replace("/(.*?)\?.*/","\\1",$pathinfo);
		}
		if(isset($_SERVER['REQUEST_METHOD'])){
			$args = func_get_args();
			if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){					
				if(isset($_POST) && is_array($_POST)){
					foreach($_POST as $k => $v) $this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
				}
				if(isset($_FILES) && is_array($_FILES)){
					foreach($_FILES as $k => $v) $this->files[$k] = $v;
				}
			}else if(isset($_GET) && is_array($_GET)){
				foreach($_GET as $k => $v) $this->vars[$k] = (get_magic_quotes_gpc() && is_string($v)) ? stripslashes($v) : $v;
			}
			if(isset($_COOKIE) && is_array($_COOKIE)){
				foreach($_COOKIE as $k => $v){
					if($k[0] != '_' && $k != session_name()) $this->vars[$k] = $v;
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
	 * @see http://jp2.php.net/manual/ja/class.iteratoraggregate.php
	 */
	public function getIterator(){
		return new \ArrayIterator($this->vars);
		/***
			$req = new self();
			$req->vars('abc',1);
			$req->vars('def',2);
			$req->vars('ghi',3);
			
			$i = 0;
			$keys = array('abc','def','ghi');
			$values = array(1,2,3);
			foreach($req as $k => $v){
				eq($keys[$i],$k);
				eq($values[$i],$v);
				$i++;
			}
		 */
	}
	/**
	 * 現在のURLを返す
	 * @return string
	 */
	static public function current_url($port_https=443,$port_http=80){
		$port = isset($_SERVER['HTTPS']) ? (($_SERVER['HTTPS'] === 'on') ? $port_https : $port_http) : null;
		if(!isset($port)){
			if(isset($_SERVER['HTTP_X_FORWARDED_PORT'])){
				$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
			}else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){
				$port = ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? $port_https : $port_http;
			}else if(isset($_SERVER['SERVER_PORT']) && !isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
				$port = $_SERVER['SERVER_PORT'];
			}else{
				$port = $port_http;
			}
		}
		$server = preg_replace("/^(.+):\d+$/","\\1",isset($_SERVER['HTTP_X_FORWARDED_HOST']) ?
					$_SERVER['HTTP_X_FORWARDED_HOST'] :
					(
						isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 
						(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '')
					));
		$path = isset($_SERVER['REQUEST_URI']) ? 
					preg_replace("/^(.+)\?.*$/","\\1",$_SERVER['REQUEST_URI']) : 
					(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '') : '');
		if($port != $port_http && $port != $port_https) $server = $server.':'.$port;
		if(empty($server)) return null;
		return (($port == $port_https) ? 'https' : 'http').'://'.preg_replace("/^(.+?)\?.*/","\\1",$server).$path;
	}	
	/**
	 * 現在のリクエストクエリを返す
	 * @return string
	 */
	static public function request_string(){
		return (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'].'&' : '').file_get_contents('php://input');
	}
	/**
	 * POSTされたか
	 * @return boolean
	 */
	public function is_post(){
		return (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
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
	public function file_orginal_name($file_info){
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
		if(!$this->has_file($file_info)) throw new LogicException('file not found ');
		if(!is_dir(dirname($newname))) mkdir(dirname($newname),0777,true);
		rename($file_info['tmp_name'],$newname);
	}
}
