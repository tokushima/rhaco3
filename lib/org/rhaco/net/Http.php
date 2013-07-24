<?php
namespace org\rhaco\net;
/**
 * HTTP接続クラス
 * @author tokushima
 *
 */
class Http{
	private $resource;
	private $agent;
	private $timeout = 30;
	private $redirect_max = 20;
	private $redirect_count = 1;

	private $request_header = array();
	private $request_vars = array();
	private $request_file_vars = array();
	private $head;
	private $body;
	private $cookie = array();
	private $url;
	private $status;
	
	private $user;
	private $password;

	public function __construct($agent=null,$timeout=30,$redirect_max=20){
		$this->agent = $agent;
		$this->timeout = (int)$timeout;
		$this->redirect_max = (int)$redirect_max;
	}
	/**
	 * 最大リダイレクト回数を設定
	 * @param integer $redirect_max
	 */
	public function redirect_max($redirect_max){
		$this->redirect_max = (integer)$redirect_max;
	}
	/**
	 * タイムアウト時間を設定
	 * @param integer $timeout
	 */
	public function timeout($timeout){
		$this->timeout = (int)$timeout;
	}
	/**
	 * ユーザエージェントを設定
	 * @param string $agent
	 */
	public function agent($agent){
		$this->agent = $agent;
	}
	/**
	 * Basic認証
	 * @param string $user ユーザ名
	 * @param string $password パスワード
	 */
	public function basic($user,$password){
		$this->user = $user;
		$this->password = $password;
		return $this;
	}
	public function __toString(){
		return $this->body();
	}
	/**
	 * ヘッダを設定
	 * @param string $key
	 * @param string $value
	 */
	public function header($key,$value=null){
		$this->request_header[$key] = $value;
	}
	/**
	 * クエリを設定
	 * @param string $key
	 * @param string $value
	 */
	public function vars($key,$value=null){
		if(is_bool($value)) $value = ($value) ? 'true' : 'false'; 
		$this->request_vars[$key] = $value;
		if(isset($this->request_file_vars[$key])) unset($this->request_file_vars[$key]);
	}
	/**
	 * クエリにファイルを設定
	 * @param string $key
	 * @param string $filename
	 */
	public function file_vars($key,$filename){
		$this->request_file_vars[$key] = $filename;
		if(isset($this->request_vars[$key])) unset($this->request_vars[$key]);
	}
	/**
	 * cURL 転送用オプションを設定する
	 * @param string $key
	 * @param mixed $value
	 */
	public function setopt($key,$value){
		if(!isset($this->resource)) $this->resource = curl_init();
		curl_setopt($this->resource,$key,$value);
	}
	/**
	 * 結果のヘッダを取得
	 * @return string
	 */
	public function head(){
		return $this->head;
	}
	/**
	 * 結果の本文を取得
	 * @return string
	 */
	public function body(){
		return ($this->body === null || is_bool($this->body)) ? '' : $this->body;
	}
	/**
	 * 結果のURLを取得
	 * @return string
	 */
	public function url(){
		return $this->url;
	}
	/**
	 * 結果のステータスを取得
	 * @return integer
	 */
	public function status(){
		return $this->status;
	}
	/**
	 * HEADリクエスト
	 * @param string $url
	 */
	public function do_head($url){
		return $this->request('HEAD',$url);
	}
	/**
	 * PUTリクエスト
	 * @param string $url
	 */
	public function do_put($url){
		return $this->request('PUT',$url);
	}
	/**
	 * DELETEリクエスト
	 * @param string $url
	 */
	public function do_delete($url){
		return $this->request('DELETE',$url);
	}
	/**
	 * GETリクエスト
	 * @param string $url
	 */
	public function do_get($url){
		return $this->request('GET',$url);
	}
	/**
	 * POSTリクエスト
	 * @param string $url
	 */
	public function do_post($url){
		return $this->request('POST',$url);
	}
	/**
	 * GETリクエストでダウンロードする
	 * @param string $url
	 * @param string $filename
	 */
	public function do_download($url,$filename){
		return $this->request('GET',$url,$filename);
	}
	/**
	 * POSTリクエストでダウンロードする
	 * @param string $url
	 * @param string $filename
	 */
	public function do_post_download($url,$filename){
		return $this->request('POST',$url,$filename);
	}
	/**
	 * ヘッダ情報をハッシュで取得する
	 * @return string{}
	 */
	public function explode_head(){
		$result = array();
		foreach(explode("\n",$this->head) as $h){
			if(preg_match("/^(.+?):(.+)$/",$h,$match)) $result[trim($match[1])] = trim($match[2]);
		}
		return $result;
	}
	/**
	 * 配列、またはオブジェクトから値を設定する
	 * @param array|object $vars
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function cp($vars){
		if(is_array($vars)){
			foreach($vars as $k => $v) $this->request_vars[$k] = $v;
		}else if(is_object($vars)){
			if(in_array('Traversable',class_implements($vars))){
				foreach($vars as $k => $v) $this->request_vars[$k] = $v;
			}else{
				foreach(get_object_vars($vars) as $k => $v) $this->request_vars[$k] = $v;
			}
		}else{
			throw new \InvalidArgumentException('must be an of array');
		}
		return $this;
	}
	/**
	 * ヘッダデータを書き込む処理
	 * @param resource $resource
	 * @param string $data
	 * @return number
	 */
	public function callback_head($resource,$data){
		$this->head .= $data;
		return strlen($data);
	}
	/**
	 * データを書き込む処理
	 * @param resource $resource
	 * @param string $data
	 * @return number
	 */
	public function callback_body($resource,$data){
		$this->body .= $data;
		return strlen($data);
	}
	private function request($method,$url,$download_path=null){
		if(!isset($this->resource)) $this->resource = curl_init();
		$url_info = parse_url($url);
		$cookie_base_domain = (isset($url_info['host']) ? $url_info['host'] : '').(isset($url_info['path']) ? $url_info['path'] : '');
		if(isset($url_info['query'])){
			parse_str($url_info['query'],$vars);
			foreach($vars as $k => $v){
				if(!isset($this->request_vars[$k])) $this->request_vars[$k] = $v;
			}
			list($url) = explode('?',$url,2);
		}
		switch($method){
			case 'POST': curl_setopt($this->resource,CURLOPT_POST,true); break;
			case 'GET': curl_setopt($this->resource,CURLOPT_HTTPGET,true); break;
			case 'HEAD': curl_setopt($this->resource,CURLOPT_NOBODY,true); break;
			case 'PUT': curl_setopt($this->resource,CURLOPT_PUT,true); break;
			case 'DELETE': curl_setopt($this->resource,CURLOPT_CUSTOMREQUEST,'DELETE'); break;
		}
		switch($method){
			case 'POST':
				if(!empty($this->request_file_vars)){
					$vars = array();
					if(!empty($this->request_vars)){
						foreach(explode('&',http_build_query($this->request_vars)) as $q){
							$s = explode('=',$q,2);
							$vars[urldecode($s[0])] = isset($s[1]) ? urldecode($s[1]) : null;
						}
					}
					foreach(explode('&',http_build_query($this->request_file_vars)) as $q){
						$s = explode('=',$q,2);
						if(isset($s[1])){
							if(!is_file($f=urldecode($s[1]))) throw new \RuntimeException($f.' not found');
							$vars[urldecode($s[0])] = '@'.$f;
						}
					}
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,$vars);
				}else{
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,http_build_query($this->request_vars));
				}
				break;
			case 'GET':
			case 'HEAD':
			case 'PUT':
			case 'DELETE':
				$url = $url.(!empty($this->request_vars) ? '?'.http_build_query($this->request_vars) : '');
		}
		curl_setopt($this->resource,CURLOPT_URL,$url);
		curl_setopt($this->resource,CURLOPT_FOLLOWLOCATION,false);
		curl_setopt($this->resource,CURLOPT_HEADER,false);
		curl_setopt($this->resource,CURLOPT_RETURNTRANSFER,false);
		curl_setopt($this->resource,CURLOPT_FORBID_REUSE,true);
		curl_setopt($this->resource,CURLOPT_FAILONERROR,false);
		curl_setopt($this->resource,CURLOPT_TIMEOUT,$this->timeout);
		
		if(!empty($this->user)){
			curl_setopt($this->resource,CURLOPT_USERPWD,$this->user.':'.$this->password);
		}		
		if(!isset($this->request_header['Expect'])){
			$this->request_header['Expect'] = null;
		}
		if(!isset($this->request_header['Cookie'])){
			$cookies = '';
			foreach($this->cookie as $domain => $cookie_value){
				if(strpos($cookie_base_domain,$domain) === 0 || strpos($cookie_base_domain,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
					foreach($cookie_value as $k => $v){
						if(!$v['secure'] || ($v['secure'] && substr($url,0,8) == 'https://')) $cookies .= sprintf('%s=%s; ',$k,$v['value']);
					}
				}
			}
			curl_setopt($this->resource,CURLOPT_COOKIE,$cookies);
		}
		if(!isset($this->request_header['User-Agent'])){
			curl_setopt($this->resource,CURLOPT_USERAGENT,
					(empty($this->agent) ?
							(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null) :
							$this->agent
					)
			);
		}
		if(!isset($this->request_header['Accept']) && isset($_SERVER['HTTP_ACCEPT'])){
			$this->request_header['Accept'] = $_SERVER['HTTP_ACCEPT'];
		}
		if(!isset($this->request_header['Accept-Language']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
			$this->request_header['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		if(!isset($this->request_header['Accept-Charset']) && isset($_SERVER['HTTP_ACCEPT_CHARSET'])){
			$this->request_header['Accept-Charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
		}
		
		curl_setopt($this->resource,CURLOPT_HTTPHEADER,
			array_map(function($k,$v){
					return $k.': '.$v;
				}
				,array_keys($this->request_header)
				,$this->request_header
			)
		);
		curl_setopt($this->resource,CURLOPT_HEADERFUNCTION,array($this,'callback_head'));
		
		if(empty($download_path)){
			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,array($this,'callback_body'));
		}else{
			if(!is_dir(dirname($download_path))) mkdir(dirname($download_path),0777,true);
			$fp = fopen($download_path,'wb');
				
			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,function($c,$data) use(&$fp){
				if($fp) fwrite($fp,$data);
				return strlen($data);
			});
		}
		$this->request_header = $this->request_vars = array();
		$this->head = $this->body = '';
		curl_exec($this->resource);
		if(!empty($download_path) && $fp){
			fclose($fp);
		}

		$this->url = trim(curl_getinfo($this->resource,CURLINFO_EFFECTIVE_URL));
		$this->status = curl_getinfo($this->resource,CURLINFO_HTTP_CODE);

		if($err_code = curl_errno($this->resource) > 0){
			if($err_code == 47) return $this;
			throw new \RuntimeException($err_code.': '.curl_error($this->resource));
		}
		if(preg_match_all('/Set-Cookie:[\s]*(.+)/i',$this->head,$match)){
			$unsetcookie = $setcookie = array();
			foreach($match[1] as $cookies){
				$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
				$cookie_domain = $cookie_base_domain;
				$cookie_path = '/';
				$secure = false;
		
				foreach(explode(';',$cookies) as $cookie){
					$cookie = trim($cookie);
					if(strpos($cookie,'=') !== false){
						list($k,$v) = explode('=',$cookie,2);
						$k = trim($k);
						$v = trim($v);
						switch(strtolower($k)){
							case 'expires': $cookie_expires = ctype_digit($v) ? (int)$v : strtotime($v); break;
							case 'domain': $cookie_domain = preg_replace('/^[\w]+:\/\/(.+)$/','\\1',$v); break;
							case 'path': $cookie_path = $v; break;
							default:
								$cookie_name = $k;
								$cookie_value = $v;
						}
					}else if(strtolower($cookie) == 'secure'){
						$secure = true;
					}
				}
				$cookie_domain = substr(\org\rhaco\net\Path::absolute('http://'.$cookie_domain,$cookie_path),7);
				if($cookie_expires !== null && $cookie_expires < time()){
					if(isset($this->cookie[$cookie_domain][$cookie_name])) unset($this->cookie[$cookie_domain][$cookie_name]);
				}else{
					$this->cookie[$cookie_domain][$cookie_name] = array('value'=>$cookie_value,'expires'=>$cookie_expires,'secure'=>$secure);
				}
			}
		}
		curl_close($this->resource);
		unset($this->resource);
		
		if($this->redirect_count++ < $this->redirect_max){
			switch($this->status){
				case 300:
				case 301:
				case 302:
				case 303:
				case 307:
					if(preg_match('/Location:[\040](.*)/i',$this->head,$redirect_url)){
						return $this->request('GET',trim(\org\rhaco\net\Path::absolute($url,$redirect_url[1])),$download_path);
					}
			}
		}
		$this->redirect_count = 1;				
		return $this;
	}
	public function __destruct(){
		if(isset($this->resource)) curl_close($this->resource);
	}
}
