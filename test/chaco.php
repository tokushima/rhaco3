<?php
namespace chaco{
	class Conf{
		static private $conf = array();
		
		static public function set($array){
			self::$conf = $array;
		}
		static public function get($name,$default=null){
			if(isset(self::$conf[$name])){
				return self::$conf[$name];
			}
			return $default;
		}
		static public function has($name){
			return array_key_exists($name,self::$conf);
		}
	}
	class AssertFailure extends \Exception{		
		private $expectation;
		private $result;
		
		public function ab($expectation,$result){
			$this->expectation = $expectation;
			$this->result = $result;
			return $this;
		}
		public function expectation(){
			return $this->expectation;
		}
		public function result(){
			return $this->result;
		}
	}
	class Runner{
		static private $resultset = array();
		static private $start_time;

		static private function include_setup_teardown($test_file,$include_file){
			if(strpos($test_file,__DIR__) === 0){
				$inc = array();
				$dir = dirname($test_file);
				while(strlen($dir) >= strlen(__DIR__)){
					if(is_file($f=($dir.'/'.$include_file))) array_unshift($inc,$f);
					$dir = dirname($dir);
				}
				if($include_file == '__teardown__.php'){
					krsort($inc);
				}
				foreach($inc as $i) include($i);
			}else if(is_file($f=(dirname($test_file).$include_file))){
				include($f);
			}
		}
		static public function exec($test_path){
			self::include_setup_teardown($test_path,'__setup__.php');
			
			self::$start_time = microtime(true);
			try{
				ob_start();
				include($test_path);
				$res = ob_get_clean();
			
				if(preg_match('/(Parse|Fatal) error:.+/',$res,$m)){
					$err = (preg_match('/syntax error.+code on line\s*(\d+)/',$res,$line) ?
							'Parse error: syntax error '.$test_path.' code on line '.$line[1]
							: $m[0]);
				}
				$res = array(1,0);
			}catch(\chaco\AssertFailure $e){
				list($debug) = $e->getTrace();
				$res = array(-1,0,$debug['file'],$debug['line'],$e->getMessage(),$e->expectation(),$e->result());
			}catch(\Exception $e){
				$res = array(-2,0,$e->getFile(),$e->getLine(),$e->getMessage());
			}
			$res[1] = (round(microtime(true) - self::$start_time,3));
			self::include_setup_teardown($test_path,'__teardown__.php');
			self::$resultset[$test_path] = $res;
			return $res[0];
		}
		static public function resultset(){
			return self::$resultset;
		}
	}
	class Coverage{
		static private $start = false;
		static private $db;
		static private $result = array();
		static private $linkvars = array();
		
		static public function has_link(&$vars){
			if(empty(self::$linkvars)) return false;
			$vars = self::$linkvars;
			return true;
		}
		static public function link(){
			return 'Link'.md5(__FILE__);
		}
		static public function start($savedb,$lib_dir){
			if(extension_loaded('xdebug')){
				self::$start = true;
				self::$db = $savedb;
				self::$linkvars['savedb'] = self::$db;
					
				$fp = fopen(self::$db.'.target','w');
				if(is_dir($lib_dir)){
					foreach(new \RecursiveIteratorIterator(
							new \RecursiveDirectoryIterator(
									$lib_dir,
									\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
							),\RecursiveIteratorIterator::SELF_FIRST
					) as $f){
						if($f->isFile() &&
							substr($f->getFilename(),-4) == '.php' &&
							strpos($f->getPathname(),'/test/') === false &&
							ctype_upper(substr($f->getFilename(),0,1))
						){
							fwrite($fp,$f->getPathname().PHP_EOL);
						}
					}
				}
				fclose($fp);
				xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
			}
		}
		static public function stop(){
			if(is_file(self::$db.'.target')){
				$target = explode(PHP_EOL,trim(file_get_contents(self::$db.'.target')));
				$fp = fopen(self::$db.'.coverage','a');

				foreach(xdebug_get_code_coverage() as $file_path => $lines){
					if(false !== ($i = array_search($file_path,$target))){
						fwrite($fp,json_encode(array($i,$lines)).PHP_EOL);
					}
				}
				fclose($fp);
					
				foreach(explode(PHP_EOL,trim(file_get_contents(self::$db.'.coverage'))) as $json){
					if(!empty($json)){
						$cov = json_decode($json,true);
						if($cov !== false){
							$filename = $target[$cov[0]];
			
							if(!isset(self::$result[$filename])){
								self::$result[$filename] = array('covered_line_status'=>array(),'uncovered_line_status'=>array(),'exec'=>1);
							}
							foreach($cov[1] as $line => $status){
								if($status == 1){
									self::$result[$filename]['covered_line_status'][] = $line;
								}else if($status != -2){
									self::$result[$filename]['uncovered_line_status'][] = $line;
								}
							}
						}
					}
				}
				foreach(self::$result as $filename => $cov){
					self::$result[$filename]['covered_line'] = array_unique(self::$result[$filename]['covered_line_status']);
					self::$result[$filename]['uncovered_line'] = array_diff(array_unique(self::$result[$filename]['uncovered_line_status']),self::$result[$filename]['covered_line']);
					unset(self::$result[$filename]['covered_line_status']);
					unset(self::$result[$filename]['uncovered_line_status']);
				}
				foreach($target as $filename){
					if(!empty($filename) && !isset(self::$result)){
						self::$result[$filename] = array('covered_line'=>array(),'uncovered_line'=>array(),'exec'=>0);
					}
				}
			}
			xdebug_stop_code_coverage();
	
			if(is_file(self::$db.'.target')) unlink(self::$db.'.target');
			if(is_file(self::$db.'.coverage')) unlink(self::$db.'.coverage');
		}
		static public function get(){
			return self::$result;
		}
	}
	class Browser{
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
	
		public function __construct($agent=null,$timeout=30,$redirect_max=20){
			$this->agent = $agent;
			$this->timeout = (int)$timeout;
			$this->redirect_max = (int)$redirect_max;
		}
		/**
		 * 最大リダイレクト回数
		 * @param integer $redirect_max
		 */
		public function redirect_max($redirect_max){
			$this->redirect_max = (integer)$redirect_max;
		}
		/**
		 * タイムアウトするまでの秒数
		 * @param integer $timeout
		 */
		public function timeout($timeout){
			$this->timeout = (int)$timeout;
		}
		/**
		 * リクエスト時のユーザエージェント
		 * @param string $agent
		 */
		public function agent($agent){
			$this->agent = $agent;
		}
		public function __toString(){
			return $this->body();
		}
		/**
		 * リクエスト時のヘッダ
		 * @param string $key
		 * @param string $value
		 */
		public function header($key,$value=null){
			$this->request_header[$key] = $value;
		}
		/**
		 * リクエスト時のクエリ
		 * @param string $key
		 * @param string $value
		 */
		public function vars($key,$value=null){
			if(is_bool($value)) $value = ($value) ? 'true' : 'false';
			$this->request_vars[$key] = $value;
			if(isset($this->request_file_vars[$key])) unset($this->request_file_vars[$key]);
		}
		/**
		 * リクエスト時の添付ファイル
		 * @param string $key
		 * @param string $filepath
		 */
		public function file_vars($key,$filepath){
			$this->request_file_vars[$key] = $filepath;
			if(isset($this->request_vars[$key])) unset($this->request_vars[$key]);
		}
		/**
		 * リクエストクエリがセットされているか
		 * @param string $key
		 * @return boolean
		 */
		public function has_vars($key){
			return (array_key_exists($key,$this->request_vars) || array_key_exists($key,$this->request_file_vars));
		}
		/**
		 * curlへのオプション
		 * @param string $key
		 * @param string $value
		 */
		public function setopt($key,$value){
			if(!isset($this->resource)) $this->resource = curl_init();
			curl_setopt($this->resource,$key,$value);
		}
		/**
		 * 結果ヘッダの取得
		 * @returnstring
		 */
		public function head(){
			return $this->head;
		}
		/**
		 * 結果ボディーの取得
		 * @return string
		 */
		public function body(){
			return ($this->body === null || is_bool($this->body)) ? '' : $this->body;
		}
		/**
		 * 最終実行URL
		 * @return string
		 */
		public function url(){
			return $this->url;
		}
		/**
		 * 最終HTTPステータス
		 * @return integer
		 */
		public function status(){
			return empty($this->status) ? null : (int)$this->status;
		}
		/**
		 * HEADでリクエスト
		 * @param string $url
		 * @return self
		 */
		public function do_head($url){
			return $this->request('HEAD',$url);
		}
		/**
		 * PUTでリクエスト
		 * @param string $url
		 * @return self
		 */
		public function do_put($url){
			return $this->request('PUT',$url);
		}
		/**
		 * DELETEでリクエスト
		 * @param string $url
		 * @return self
		 */
		public function do_delete(){
			return $this->request('DELETE',$url);
		}
		/**
		 * GETでリクエスト
		 * @param string $url
		 * @return self
		 */
		public function do_get($url){
			return $this->request('GET',$url);
		}
		/**
		 * POSTでリクエスト
		 * @param string $url
		 * @return self
		 */
		public function do_post($url){
			return $this->request('POST',$url);
		}
		/**
		 * GETでリクエストしてダウンロード
		 * @param string $url
		 * @param string $download_path 保存パス
		 * @return self
		 */
		public function do_download($url,$download_path){
			return $this->request('GET',$url,$download_path);
		}
		/**
		 * POSTでリクエストしてダウンロード
		 * @param string $url
		 * @param string $download_path 保存パス
		 * @return self
		 */
		public function do_post_download($url,$download_path){
			return $this->request('POST',$url,$download_path);
		}
		public function callback_head($resource,$data){
			$this->head .= $data;
			return strlen($data);
		}
		public function callback_body($resource,$data){
			$this->body .= $data;
			return strlen($data);
		}
		private function request($method,$url,$download_path=null){
			if(!isset($this->resource)) $this->resource = curl_init();
			$url_info = parse_url($url);
			$host = isset($url_info['host']) ? $url_info['host'] : '';
			$cookie_base_path = $host.(isset($url_info['path']) ? $url_info['path'] : '');
			if(\chaco\Coverage::has_link($vars)) $this->request_vars[\chaco\Coverage::link()] = $vars;
	
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
								$vars[urldecode($s[0])] = (class_exists('\\CURLFile',false)) ? new \CURLFile($f) : '@'.$f;
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
	
			if(!isset($this->request_header['Expect'])){
				$this->request_header['Expect'] = null;
			}
			if(!isset($this->request_header['Cookie'])){
				$cookies = '';
				foreach($this->cookie as $domain => $cookie_value){
					if(strpos($cookie_base_path,$domain) === 0 || strpos($cookie_base_path,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
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
			curl_setopt($this->resource,
			CURLOPT_HTTPHEADER,
			array_map(
			function($k,$v){
				return $k.': '.$v;
			},
			array_keys($this->request_header),
			$this->request_header
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
			if(($err_code = curl_errno($this->resource)) > 0){
				if($err_code == 47) return $this;
				throw new \RuntimeException($err_code.': '.curl_error($this->resource));
			}
	
			$this->status = curl_getinfo($this->resource,CURLINFO_HTTP_CODE);
			$this->url = curl_getinfo($this->resource,CURLINFO_EFFECTIVE_URL);
	
			if(strpos($this->url,'?') !== false){
				list($url,$query) = explode('?',$this->url,2);
				if(!empty($query)){
					parse_str($query,$vars);
					if(isset($vars[\chaco\Coverage::link()])) unset($vars[\chaco\Coverage::link()]);
					if(!empty($vars)){
						$url = $url.'?'.http_build_query($vars);
					}
				}
				$this->url = $url;
			}
			if(preg_match_all('/Set-Cookie:[\s]*(.+)/i',$this->head,$match)){
				$unsetcookie = $setcookie = array();
				
				foreach($match[1] as $cookies){
					$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
					$cookie_domain = $host;
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
									if(!isset($cookie_name)){
										$cookie_name = $k;
										$cookie_value = $v;
									}
							}
						}else if(strtolower($cookie) == 'secure'){
							$secure = true;
						}
					}
					$cookie_domain = $cookie_domain.$cookie_path;
					
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
							return $this->request('GET',trim($redirect_url[1]),$download_path);
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
}


namespace{
	if(count(debug_backtrace(false)) > 0){
		$key = \chaco\Coverage::link();
		$linkvars = isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : array());
		if(isset($_POST[$key])) unset($_POST[$key]);
		if(isset($_GET[$key])) unset($_GET[$key]);
		
		if(function_exists('xdebug_get_code_coverage') && isset($linkvars['savedb'])){
			register_shutdown_function(function() use($linkvars){
				register_shutdown_function(function() use($linkvars){
					$savedb = $linkvars['savedb'];
					
					if(is_file($savedb.'.target')){
						$target = explode(PHP_EOL,file_get_contents($savedb.'.target'));
						$fp = fopen($savedb.'.coverage','a');
	
						foreach(xdebug_get_code_coverage() as $file_path => $lines){
							if(false !== ($i = array_search($file_path,$target))){
								fwrite($fp,json_encode(array($i,$lines)).PHP_EOL);
							}
						}
						fclose($fp);
					}
					xdebug_stop_code_coverage();
				});
			});
			xdebug_start_code_coverage();
		}
		return;
	}
	ini_set('display_errors','On');
	ini_set('html_errors','Off');
	ini_set('error_reporting',E_ALL);
	ini_set('xdebug.var_display_max_children',-1);
	ini_set('xdebug.var_display_max_data',-1);
	ini_set('xdebug.var_display_max_depth',-1);
	ini_set('memory_limit',-1);
	
	if(ini_get('date.timezone') == ''){
		date_default_timezone_set('Asia/Tokyo');
	}
	if(extension_loaded('mbstring')){
		if('neutral' == mb_language()) mb_language('Japanese');
		mb_internal_encoding('UTF-8');
	}
	set_error_handler(function($n,$s,$f,$l){
		throw new \ErrorException($s,0,$n,$f,$l);
	});
	function expvar($var){
		if(is_numeric($var)) return strval($var);
		if(is_object($var)) $var = get_object_vars($var);
		if(is_array($var)){
			foreach($var as $key => $v){
				$var[$key] = expvar($v);
			}
		}
		return $var;
	}
	/**
	 *　等しい
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 */
	function eq($expectation,$result){
		if(expvar($expectation) !== expvar($result)){
			$failure = new \chaco\AssertFailure('failure equals');
			throw $failure->ab($expectation, $result);
		}
	}
	/**
	 * 等しくない
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 */
	function neq($expectation,$result){
		if(expvar($expectation) === expvar($result)){
			$failure = new \chaco\AssertFailure('failure not equals');
			throw $failure->ab($expectation, $result);
		}
	}
	/**
	 *　文字列中に指定の文字列が存在する
	 * @param string|array $keyword
	 * @param string $src
	 */
	function meq($keyword,$src){
		if(mb_strpos($src,$keyword) === false){
			$failure = new \chaco\AssertFailure('failure match');
			throw $failure->ab($keyword,$src);
		}
	}
	/**
	 * 文字列中に指定の文字列が存在しない
	 * @param string $keyword
	 * @param string $src
	 */
	function mneq($keyword,$src){
		if(mb_strpos($src,$keyword) !== false){
			$failure = new \chaco\AssertFailure('failure not match');
			throw $failure->ab($keyword,$src);
		}
	}
	/**
	 * mapに定義されたurlをフォーマットして返す
	 * @param string $map_name
	 * @throws \RuntimeException
	 * @return string
	 */
	function test_map_url($map_name){
		$urls = \chaco\Conf::get('urls',array());
		if(empty($urls) || !is_array($urls)) throw new \RuntimeException('urls empty');
		$args = func_get_args();
		array_shift($args);
	
		if(isset($urls[$map_name]) && substr_count($urls[$map_name],'%s') == sizeof($args)) return vsprintf($urls[$map_name],$args);
		throw new \RuntimeException($map_name.(isset($urls[$map_name]) ? '['.sizeof($args).']' : '').' not found');
	}
	/**
	 * Browser
	 * @param string $agent
	 * @param number $timeout
	 * @param number $redirect_max
	 * @return \chaco\Browser
	 */
	function b($agent=null,$timeout=30,$redirect_max=20){
		return new \chaco\Browser($agent,$timeout,$redirect_max);
	}
	
	$opt = $conf = array();
	$argv = array_slice($_SERVER['argv'],1);
	$value = is_dir(__DIR__.'/test') ? __DIR__.'/test' : __DIR__;
	
	for($i=0;$i<sizeof($argv);$i++){
		if(substr($argv[$i],0,2) == '--'){
			$opt[substr($argv[$i],2)] = (isset($argv[$i+1]) ? $argv[$i+1] : null);
			$i++;
		}else if(substr($argv[$i],0,1) == '-'){
			$opt = array_merge($opt,array_fill_keys(str_split(substr($argv[$i],1),1),true));
		}else{
			$value = $argv[$i];
		}
	}
	
	if(is_file($f=getcwd().'/bootstrap.php') || is_file($f=getcwd().'/vendor/autoload.php')){
		ob_start();
			include_once($f);
		ob_end_clean();
	}	
	$conf_file = substr(__FILE__,0,-4).'.conf.php';
	if(is_file($conf_file)){
		$conf = include($conf_file);
		if(!is_array($conf)) throw new \RuntimeException('invalid '.$conf_file);
	}
	\chaco\Conf::set(array_merge($conf,$opt));
	
	$path = realpath($value);
	if($path === false) die($value.' found'.PHP_EOL);
		
	$tab = '  ';
	$success = $fail = $exception = $exe_time = $use_memory = 0;
	$test_list = array();
	$output_dir = \chaco\Conf::get('outputdir',getcwd());
	if(substr($output_dir,-1) != '/') $output_dir = $output_dir.'/';
	
	$println = function($msg='',$color=30){
		print("\033[".$color."m");
		print($msg.PHP_EOL);
		print("\033[0m");
	};
	
	if(is_dir($path)){
		foreach(new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
				\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
		),\RecursiveIteratorIterator::SELF_FIRST),'/\.php$/') as $f){
			if(!preg_match('/\/[\.\_]/',$f->getPathname()) && strpos($f->getFilename(),basename(__FILE__,'.php').'.') === false){
				$test_list[$f->getPathname()] = 1;
			}
		}
	}else if(is_file($path)){
		$test_list[realpath($path)] = 1;
	}else{
		throw new \InvalidArgumentException($path.' not');
	}
	ksort($test_list);
	$test_list = array_keys($test_list);
	
	$println('Progress:','1;33');
	print(' ');
	print(str_repeat('+',sizeof($test_list)));
	print("\033[".(sizeof($test_list)+1)."D");
	
	if(is_file($fixture_file=substr(__FILE__,0,-4).'.fixture.php')){
		include_once($fixture_file);
	}
	
	$coverage_output = null;
	if(\chaco\Conf::has('coverage') || \chaco\Conf::has('c')){
		$coverage_output = \chaco\Conf::get('coverage',$output_dir.date('YmdHis').'.coverage.xml');
		if(!function_exists('xdebug_get_code_coverage')) die('xdebug extension not loaded'.PHP_EOL);
		if(!is_dir(dirname($coverage_output))) mkdir(dirname($coverage_output),0777,true);
		file_put_contents($coverage_output,'');
		$coverage_output = realpath($coverage_output);
		\chaco\Coverage::start($coverage_output,\chaco\Conf::get('libdir',dirname(__DIR__).'/lib'));
	}
	
	print(' ');
	$start_time = microtime(true);
	$start_mem = round(number_format((memory_get_usage() / 1024 / 1024),3),4);
	foreach($test_list as $test_path){
		print("/\033[1D");
		$status = \chaco\Runner::exec($test_path);
		print("\033[".(($status == 1) ? 32 : 31)."m*\033[0m");
	}
	print(PHP_EOL);
	$exe_time = round((microtime(true) - (float)$start_time),4);
	$use_memory = round(number_format((memory_get_usage() / 1024 / 1024),3),4);
	
	if(!empty($coverage_output) && is_file($coverage_output)){
		\chaco\Coverage::stop();

		// view
		$println();
		$println('Coverage: ','1;33');
		
		$total = 0;
		foreach(\chaco\Coverage::get() as $filename => $resultset){
			$covered = count($resultset['covered_line']);
			$uncovered = count($resultset['uncovered_line']);
				
			$coverage = ($resultset['exec'] == 1) ? ceil($covered / ($covered + $uncovered) * 100) : 1;
			$color = ($resultset['exec'] == 1) ? '1;31' : '1;34';
				
			if($coverage == 100){
				$color = '32';
			}else if($coverage > 50){
				$color = '33';
			}
			$println(sprintf(' %3d%% %s',$coverage,$filename),$color);
			$total += $coverage;
		}
		$println(str_repeat('-',70));
		$println(sprintf(' Covered %s%%',($total == 0) ? 0 : round($total/sizeof(\chaco\Coverage::get()),3)),'1;35');

		// output
		$xml = new \SimpleXMLElement('<coverage></coverage>');
		$total_covered = $total_lines = 0;
		
		foreach(\chaco\Coverage::get() as $filename => $resultset){
			$covered = count($resultset['covered_line']);
			$uncovered = count($resultset['uncovered_line']);
				
			$f = $xml->addChild('file');
			$f->addAttribute('name',$filename);
				
			$f->addAttribute('covered',(($resultset['exec'] == 1) ? ceil($covered / ($covered + $uncovered) * 100) : 0));
			$f->addAttribute('modify_date',date('Y/m/d H:i:s',filemtime($filename)));
			$f->addChild('covered_lines',implode(',',$resultset['covered_line']));
			$f->addChild('uncovered_lines',implode(',',$resultset['uncovered_line']));
				
			$total_covered += $covered;
			$total_lines += $covered + $uncovered;
		}
		$xml->addAttribute('create_date',date('Y/m/d H:i:s'));
		$xml->addAttribute('covered',($total_covered == 0) ? 0 : ceil($total_covered/$total_lines*100));
		$xml->addAttribute('lines',$total_lines);
		$xml->addAttribute('covered_lines',$total_covered);
		
		file_put_contents($coverage_output,$xml->asXML());
		$println('  Written XML: '.$coverage_output,'34');
	}
	
	$println();
	$println('Results:','1;33');
	foreach(\chaco\Runner::resultset() as $file => $info){
		switch($info[0]){
			case 1:
				$success++;
				break;
			case -1:
				$fail++;
				list(,$time,$file,$line,$msg,$r1,$r2) = $info;
	
				$println();
				$println($file,'1;34');
				$println('['.$line.']: '.$msg,'1;31');
				$println($tab.str_repeat('-',70));
				
				ob_start();
					var_dump($r1);
				$println($tab.str_replace(PHP_EOL,PHP_EOL.$tab,ob_get_clean()));
				
				$println($tab.str_repeat('-',70));
	
				ob_start();
					var_dump($r2);
				$println($tab.str_replace(PHP_EOL,PHP_EOL.$tab,ob_get_clean()));
				
				break;
			case -2:
				$exception++;
				list(,$time,$file,$line,$msg) = $info;
	
				$println();
				$println($file,'1;34');
				$println('['.$line.']: '.$msg,'1;31');
				break;
		}
	}
	$println(str_repeat('=',80));
	$println(sprintf('success %d, failures %d, errors %d (%.05f sec / %s MByte)',$success,$fail,$exception,$exe_time,$use_memory),'35');
	
	if(\chaco\Conf::has('output') || \chaco\Conf::has('o')){
		$output = \chaco\Conf::get('output',$output_dir.date('YmdHis').'.result.xml');
		if(!is_dir(dirname($output))) mkdir(dirname($output),0777,true);
		
		$xml = new \SimpleXMLElement('<testsuites></testsuites>');
		$get_testsuite = function($dir,&$testsuite) use($xml){
			if(empty($testsuite)){
				$testsuite = $xml->addChild('testsuite');
				$testsuite->addAttribute('name',$dir);
			}
			return $testsuite;
		};
			
		$list = array();
		foreach(\chaco\Runner::resultset() as $file => $info){
			$list[dirname($file)][basename($file)] = $info;
		}
			
		$errors = $failures = $times = 0;
		foreach($list as $dir => $files){
			$testsuite = null;
			$dir_time = $dir_failures = $dir_errors = 0;
	
			foreach($files as $file => $info){
				switch($info[0]){
					case 1:
						list(,$time) = $info;
						$x = $get_testsuite($dir,$testsuite)->addChild('testcase');
						$x->addAttribute('name',basename($file));
						$x->addAttribute('time',$time);
							
						$dir_time += $time;
						break;
					case -1:
						list(,$time,$file,$line,$msg,$r1,$r2) = $info;
						$dir_failures++;
							
						$x = $get_testsuite($dir,$testsuite)->addChild('testcase');
						$x->addAttribute('name',basename($file));
						$x->addAttribute('time',$time);
						$x->addAttribute('line',$line);
	
						ob_start();
						var_dump($r2);
						$failure_value = 'Line. '.$line.': '."\n".ob_get_clean();
						$failure = dom_import_simplexml($x->addChild('failure'));
						$failure->appendChild($failure->ownerDocument->createCDATASection($failure_value));
							
						$dir_time += $time;
						break;
					case -2:
						list(,$time,$file,$line,$msg) = $info;
						$dir_errors++;
							
						$x = $get_testsuite($dir,$testsuite)->addChild('testcase');
						$x->addAttribute('name',basename($file));
						$x->addAttribute('time',$time);
						$x->addAttribute('line',$line);
	
						$error_value = 'Line. '.$line.': '.$msg;
						$error = $x->addChild('error');
						$error->addAttribute('line',$line);
						$error_node = dom_import_simplexml($error);
						$error_node->appendChild($error_node->ownerDocument->createCDATASection($error_value));
							
						$dir_time += $time;
						break;
				}
			}
			if(!empty($testsuite)){
				$testsuite->addAttribute('tests',sizeof($files));
				$testsuite->addAttribute('failures',$dir_failures);
				$testsuite->addAttribute('errors',$dir_errors);
				$testsuite->addAttribute('time',$dir_time);
			}
			$failures += $dir_failures;
			$errors += $dir_errors;
			$times += $dir_time;
		}
		$xml->addAttribute('tests',sizeof(\chaco\Runner::resultset()));
		$xml->addAttribute('failures',$failures);
		$xml->addAttribute('errors',$errors);
		$xml->addAttribute('time',$times);
		$xml->addAttribute('create_date',date('Y/m/d H:i:s'));
		$xml->addChild('system-out');
		$xml->addChild('system-err');
		
		file_put_contents($output,$xml->asXML());
		$println('Written XML: '.realpath($output).' ','1;34');
	}
}
