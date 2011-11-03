<?php
/**
 * HTTP関連処理
 * @author tokushima
 * @see http://jp2.php.net/manual/ja/context.ssl.php
 */
class Http{
	private $user;
	private $password;

	private $agent;
	private $timeout = 30;
	private $status_redirect = true;

	private $body;
	private $head;
	private $url;
	private $status;
	private $cmd;

	private $raw;
	protected $vars = array();
	protected $header = array();

	private $cookie = array();
	private $form = array();

	private $api_url;
	private $api_key;
	private $api_key_name = 'api_key';

	public function __construct($agent=null,$timeout=30,$status_redirect=true){
		$this->agent = $agent;
		$this->timeout = (int)$timeout;
		$this->status_redirect = (boolean)$status_redirect;
	}
	public function __toString(){
		return $this->body;
	}
	public function api_url($url){
		$this->api_url = $url;
		return $this;
	}
	public function api_key($key,$keyname='api_key'){
		$this->api_key = $key;
		$this->api_key_name = $keyname;
		return $this;
	}
	public function raw($raw){
		$this->raw = $raw;
		return $this;
	}
	public function status(){
		return $this->status;
	}
	public function body(){
		return $this->body;
	}
	public function head(){
		return $this->head;
	}
	public function url(){
		return $this->url;
	}
	public function cmd(){
		return $this->cmd;
	}
	public function get_vars(){
		return $this->vars;
	}
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	public function header($key,$value){
		$this->header[$key] = $value;
	}
	/**
	 * URLが有効かを調べる
	 *
	 * @param string $url 確認するURL
	 * @return boolean
	 */
	static public function is_url($url){
		try{
			$self = new self();
			$result = $self->request($url,'HEAD',array(),array(),null,false);
			return ($result->status === 200);
		}catch(Exception $e){}
		return false;
	}
	/**
	 * URLのステータスを確認する
	 * @param string $url 確認するURL
	 * @return integer
	 */
	static public function request_status($url){
		try{
			$self = new self();
			$result = $self->request($url,'HEAD',array(),array(),null,false);
			return $result->status;
		}catch(Exception $e){}
		return 404;
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
	public function cp($array){
		if(is_array($array)){
			foreach($array as $k => $v) $this->vars[$k] = $v;			
		}else if(is_object($array)){
			if(in_array('Traversable',class_implements($array))){
				foreach($array as $k => $v) $this->vars[$k] = $v;
			}else{
				foreach(get_object_vars($array) as $k => $v) $this->vars[$k] = $v;
			}
		}else{
			throw new InvalidArgumentException('must be an of array');
		}
		return $this;
	}
	private function build_url($url){
		if($this->api_key !== null) $this->vars($this->api_key_name,$this->api_key);
		if($this->api_url !== null) return self::absolute($this->api_url,(substr($url,0,1) == '/') ? substr($url,1) : $url);
		return $url;
	}
	/**
	 * getでアクセスする
	 * @param string $url アクセスするURL
	 * @param boolean $form formタグの解析を行うか
	 * @return $this
	 */
	public function do_get($url=null,$form=true){
		return $this->browse($this->build_url($url),'GET',$form);
	}
	/**
	 * postでアクセスする
	 * @param string $url アクセスするURL
	 * @param boolean $form formタグの解析を行うか
	 * @return $this
	 */
	public function do_post($url=null,$form=true){
		return $this->browse($this->build_url($url),'POST',$form);
	}
	/**
	 * putでアクセスする
	 * @param string $url アクセスするURL
	 * @return $this
	 */
	public function do_put($url=null){
		return $this->browse($this->build_url($url),'PUT',false);
	}
	/**
	 * deleteでアクセスする
	 * @param string $url アクセスするURL
	 * @return $this
	 */
	public function do_delete($url=null){
		return $this->browse($this->build_url($url),'DELETE',false);
	}
	/**
	 * ダウンロードする
	 *
	 * @param string $url アクセスするURL
	 * @param string $download_path ダウンロード先のファイルパス
	 * @return $this
	 */
	public function do_download($url=null,$download_path){
		return $this->browse($this->build_url($url),'GET',false,$download_path);
	}
	/**
	 * HEADでアクセスする formの取得はしない
	 * @param string $url アクセスするURL
	 * @return $this
	 */
	public function do_head($url=null){
		return $this->browse($this->build_url($url),'HEAD',false);
	}
	/**
	 * 指定の時間から更新されているか
	 * @param string $url アクセスするURL
	 * @param integer $time 基点となる時間
	 * @return string
	 */
	public function do_modified($url,$time){
		$this->header('If-Modified-Since',date('r',$time));
		return $this->browse($this->build_url($url),'GET',false)->body();
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
	private function browse($url,$method,$form=true,$download_path=null){
		$cookies = '';
		$variables = '';
		$headers = $this->header;
		$cookie_base_domain = preg_replace("/^[\w]+:\/\/(.+)$/","\\1",$url);

		foreach($this->cookie as $domain => $cookie_value){
			if(strpos($cookie_base_domain,$domain) === 0 || strpos($cookie_base_domain,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
				foreach($cookie_value as $name => $value){
					if(!$value['secure'] || ($value['secure'] && substr($url,0,8) == 'https://')) $cookies .= sprintf("%s=%s; ",$name,$value['value']);
				}
			}
		}
		if(!empty($cookies)) $headers["Cookie"] = $cookies;
		if(!empty($this->user)){
			if(preg_match("/^([\w]+:\/\/)(.+)$/",$url,$match)){
				$url = $match[1].$this->user.':'.$this->password.'@'.$match[2];
			}else{
				$url = 'http://'.$this->user.':'.$this->password.'@'.$url;
			}
		}
		if($this->raw !== null) $headers['rawdata'] = $this->raw;
		$result = $this->request($url,$method,$headers,$this->vars,$download_path,false);
		$this->cmd = $result->cmd;
		$this->head = $result->head;
		$this->url = $result->url;
		$this->status = $result->status;
		$this->body = $result->body;
		$this->form = array();

		if(preg_match_all("/Set-Cookie:[\s]*(.+)/i",$this->head,$match)){
			$unsetcookie = $setcookie = array();
			foreach($match[1] as $cookies){
				$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
				$cookie_domain = $cookie_base_domain;
				$cookie_path = '/';
				$secure = false;

				foreach(explode(';',$cookies) as $cookie){
					$cookie = trim($cookie);
					if(strpos($cookie,'=') !== false){
						list($name,$value) = explode('=',$cookie,2);
						$name = trim($name);
						$value = trim($value);
						switch(strtolower($name)){
							case 'expires': $cookie_expires = ctype_digit($value) ? (int)$value : strtotime($value); break;
							case 'domain': $cookie_domain = preg_replace("/^[\w]+:\/\/(.+)$/","\\1",$value); break;
							case 'path': $cookie_path = $value; break;
							default:
								$cookie_name = $name;
								$cookie_value = $value;
						}
					}else if(strtolower($cookie) == 'secure'){
						$secure = true;
					}
				}
				$cookie_domain = substr(self::absolute('http://'.$cookie_domain,$cookie_path),7);
				if($cookie_expires !== null && $cookie_expires < time()){
					if(isset($this->cookie[$cookie_domain][$cookie_name])) unset($this->cookie[$cookie_domain][$cookie_name]);
				}else{
					$this->cookie[$cookie_domain][$cookie_name] = array('value'=>$cookie_value,'expires'=>$cookie_expires,'secure'=>$secure);
				}
			}
		}
		$this->vars = array();
		if($this->status_redirect){
			if(isset($result->redirect)) return $this->browse($result->redirect,'GET',$form,$download_path);
			if(class_exists('XmlObject') && XmlObject::set($tag,$result->body,'head')){
				foreach($tag->in('meta') as $meta){
					if(strtolower($meta->in_attr('http-equiv')) == 'refresh'){
						if(preg_match("/^[\d]+;url=(.+)$/i",$meta->in_attr('content'),$refresh)){
							$this->vars = array();
							return $this->browse(self::absolute(dirname($url),$refresh[1]),'GET',$form,$download_path);
						}
					}
				}
			}
		}
		if($form && class_exists('XmlObject')) $this->parse_form();
		return $this;
	}
	private function parse_form(){
		$tag = new XmlObject('<:>'.$this->body.'</:>',':');
		foreach($tag->in('form') as $key => $formtag){
			$form = new stdClass();
			$form->name = $formtag->in_attr('name',$formtag->in_attr('id',$key));
			$form->action = self::absolute($this->url,$formtag->in_attr('action',$this->url));
			$form->method = strtolower($formtag->in_attr('method','get'));
			$form->multiple = false;
			$form->element = array();

			foreach($formtag->in('input') as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->in_attr('name',$input->in_attr('id','input_'.$count));
				$obj->type = strtolower($input->in_attr('type','text'));
				$obj->value = self::htmldecode($input->in_attr('value'));
				$obj->selected = ('selected' === strtolower($input->in_attr('checked',$input->in_attr('checked'))));
				$obj->multiple = false;
				$form->element[] = $obj;
			}
			foreach($formtag->in('textarea') as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->in_attr('name',$input->in_attr('id','textarea_'.$count));
				$obj->type = 'textarea';
				$obj->value = self::htmldecode($input->value());
				$obj->selected = true;
				$obj->multiple = false;
				$form->element[] = $obj;
			}
			foreach($formtag->in('select') as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->in_attr('name',$input->in_attr('id','select_'.$count));
				$obj->type = 'select';
				$obj->value = array();
				$obj->selected = true;
				$obj->multiple = ('multiple' == strtolower($input->param('multiple',$input->attr('multiple'))));

				foreach($input->in('option') as $count => $option){
					$op = new stdClass();
					$op->value = self::htmldecode($option->in_attr('value',$option->value()));
					$op->selected = ('selected' == strtolower($option->in_attr('selected',$option->in_attr('selected'))));
					$obj->value[] = $op;
				}
				$form->element[] = $obj;
			}
			$this->form[] = $form;
		}
	}
	/**
	 * formをsubmitする
	 * @param string $form FORMタグの名前、または順番
	 * @param string $submit 実行するINPUTタグ(type=submit)の名前
	 * @return $this
	 */
	public function submit($form=0,$submit=null){
		foreach($this->form as $key => $f){
			if($f->name === $form || $key === $form){
				$form = $key;
				break;
			}
		}
		if(isset($this->form[$form])){
			$inputcount = 0;
			$onsubmit = ($submit === null);

			foreach($this->form[$form]->element as $element){
				switch($element->type){
					case 'hidden':
					case 'textarea':
						if(!array_key_exists($element->name,$this->vars)){
							$this->vars($element->name,$element->value);
						}
						break;
					case 'text':
					case 'password':
						$inputcount++;
						if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value); break;
						break;
					case 'checkbox':
					case 'radio':
						if($element->selected !== false){
							if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value);
						}
						break;
					case 'submit':
					case 'image':
						if(($submit === null && $onsubmit === false) || $submit == $element->name){
							$onsubmit = true;
							if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value);
							break;
						}
						break;
					case 'select':
						if(!array_key_exists($element->name,$this->vars)){
							if($element->multiple){
								$list = array();
								foreach($element->value as $option){
									if($option->selected) $list[] = $option->value;
								}
								$this->vars($element->name,$list);
							}else{
								foreach($element->value as $option){
									if($option->selected){
										$this->vars($element->name,$option->value);
									}
								}
							}
						}
						break;
					case "button":
						break;
				}
			}
			if($onsubmit || $inputcount == 1){
				return ($this->form[$form]->method == 'post') ?
							$this->browse($this->form[$form]->action,'POST') :
							$this->browse($this->form[$form]->action,'GET');
			}
		}
		return $this;
	}
	private function request($url,$method,array $header=array(),array $vars=array(),$download_path=null,$status_redirect=true){
		$url = (string)$url;
		$result = (object)array('url'=>$url,'status'=>200,'head'=>null,'redirect'=>null,'body'=>null,'encode'=>null,'cmd'=>null);
		$raw = isset($header['rawdata']) ? $header['rawdata'] : null;
		if(isset($header['rawdata'])) unset($header['rawdata']);
		$header['Content-Type'] = 'application/x-www-form-urlencoded';

		if(!isset($raw) && !empty($vars)){
			if($method == 'GET'){
				$url = (strpos($url,'?') === false) ? $url.'?' : $url.'&';
				$url .= self::query_get($vars,null,true);
			}else{
				$query_vars = array(array(),array());
				foreach(self::expand_vars($tmp,$vars,null,false) as $v){
					$query_vars[is_string($v[1]) ? 0 : 1][] = $v;
				}
				if(empty($query_vars[1])){
					$raw = self::query_get($vars,null,true);
				}else{
					$boundary = '-----------------'.md5(microtime());
					$header['Content-Type'] = 'multipart/form-data;  boundary='.$boundary;
					$raws = array();
	
					foreach($query_vars[0] as $v){
						$raws[] = sprintf('Content-Disposition: form-data; name="%s"',$v[0])
									."\r\n\r\n"
									.$v[1]
									."\r\n";
					}
					foreach($query_vars[1] as $v){
						$raws[] = sprintf('Content-Disposition: form-data; name="%s"; filename="%s"',$v[0],$v[1]->name())
									."\r\n".sprintf('Content-Type: %s',$v[1]->mime())
									."\r\n".sprintf('Content-Transfer-Encoding: %s',"binary")
									."\r\n\r\n"
									.$v[1]->get()
									."\r\n";
					}
					$raw = "--".$boundary."\r\n".implode("--".$boundary."\r\n",$raws)."\r\n--".$boundary."--\r\n"."\r\n";
				}
			}
		}
		$ulist = parse_url(preg_match("/^([\w]+:\/\/)(.+?):(.+)(@.+)$/",$url,$m) ? ($m[1].urlencode($m[2]).":".urlencode($m[3]).$m[4]) : $url);
		$ssl = (isset($ulist['scheme']) && ($ulist['scheme'] == 'ssl' || $ulist['scheme'] == 'https'));
		$port = isset($ulist['port']) ? $ulist['port'] : null;
		$errorno = $errormsg = null;

		if(!isset($ulist['host']) || substr($ulist['host'],-1) === '.') throw new InvalidArgumentException('Connection fail `'.$url.'`');
		$fp	= fsockopen((($ssl) ? 'ssl://' : '').$ulist['host'],(isset($port) ? $port : ($ssl ? 443 : 80)),$errorno,$errormsg,$this->timeout);
		if($fp == false || false == stream_set_blocking($fp,true) || false == stream_set_timeout($fp,$this->timeout)) throw new InvalidArgumentException('Connection fail `'.$url.'` '.$errormsg.' '.$errorno);
		$cmd = sprintf("%s %s%s HTTP/1.1\r\n",$method,((!isset($ulist["path"])) ? "/" : $ulist["path"]),(isset($ulist["query"])) ? sprintf("?%s",$ulist["query"]) : "")
				.sprintf("Host: %s\r\n",$ulist['host'].(empty($port) ? '' : ':'.$port));

		if(!isset($header['User-Agent'])) $header['User-Agent'] = empty($this->agent) ? (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null) : $this->agent;
		if(!isset($header['Accept'])) $header['Accept'] = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
		if(!isset($header['Accept-Language'])) $header['Accept-Language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
		if(!isset($header['Accept-Charset'])) $header['Accept-Charset'] = isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : null;
		$header['Connection'] = 'Close';

		foreach($header as $k => $v){
			if(isset($v)) $cmd .= sprintf("%s: %s\r\n",$k,$v);
		}
		if(!isset($header['Authorization']) && isset($ulist["user"]) && isset($ulist["pass"])){
			$cmd .= sprintf("Authorization: Basic %s\r\n",base64_encode(sprintf("%s:%s",urldecode($ulist["user"]),urldecode($ulist["pass"]))));
		}
		$result->cmd = $cmd.((!empty($raw)) ? ('Content-length: '.strlen($raw)."\r\n\r\n".$raw) : "\r\n");
		fwrite($fp,$result->cmd);

		while(!feof($fp) && substr($result->head,-4) != "\r\n\r\n"){
			$result->head .= fgets($fp,4096);
			self::check_timeout($fp,$url);
		}
		$result->status = (preg_match("/HTTP\/.+[\040](\d\d\d)/i",$result->head,$httpCode)) ? intval($httpCode[1]) : 0;
		$result->encode = (preg_match("/Content-Type.+charset[\s]*=[\s]*([\-\w]+)/",$result->head,$match)) ? trim($match[1]) : null;

		switch($result->status){
			case 300:
			case 301:
			case 302:
			case 303:
			case 307:
				if(preg_match("/Location:[\040](.*)/i",$result->head,$redirect_url)){
					$result->redirect = preg_replace("/[\r\n]/","",self::absolute($url,$redirect_url[1]));
					if($method == 'GET' && $result->redirect === $result->url){
						$result->redirect = null;
					}else if($status_redirect){
						fclose($fp);
						return $this->request($result->redirect,"GET",$h,array(),$download_path,$status_redirect);
					}
				}
		}		
		$download_handle = null;
		if($download_path !== null){
			if(is_dir($download_path)) throw new LogicException('Is a directory in `'.$download_path.'`');
			if(is_dir(dirname($download_path)) || mkdir(dirname($download_path),0777,true) === null) $download_handle = fopen($download_path,'wb');			
			if($download_handle === false) throw new LogicException('Permission denied `'.$download_path.'`');
		}
		if(preg_match("/^Content\-Length:[\s]+([0-9]+)\r\n/i",$result->head,$m)){
			if(0 < ($length = $m[1])){
				$rest = $length % 4096;
				$count = ($length - $rest) / 4096;

				while(!feof($fp)){
					if($count-- > 0){
						self::write_body($result,$download_handle,fread($fp,4096));
					}else{
						self::write_body($result,$download_handle,fread($fp,$rest));
						break;
					}
					self::check_timeout($fp,$url);
				}
			}
		}else if(preg_match("/Transfer\-Encoding:[\s]+chunked/i",$result->head)){
			while(!feof($fp)){
				$size = hexdec(trim(fgets($fp,4096)));
				$buffer = "";

				while($size > 0 && strlen($buffer) < $size){
					$value = fgets($fp,$size);
					if($value === feof($fp)) break;
					$buffer .= $value;
				}
				self::write_body($result,$download_handle,substr($buffer,0,$size));
				self::check_timeout($fp,$url);
			}
		}else{
			while(!feof($fp)){
				self::write_body($result,$download_handle,fread($fp,4096));
				self::check_timeout($fp,$url);
			}
		}
		fclose($fp);
		if($download_handle !== null) fclose($download_handle);
		return $result;
	}
	static private function check_timeout($fp,$url){
		$info = stream_get_meta_data($fp);
		if($info['timed_out']){
			fclose($fp);
			throw new LogicException('Connection time out. `'.$url.'`');
		}
	}
	static private function write_body(&$result,&$download_handle,$value){
		if($download_handle !== null) return fwrite($download_handle,$value);
		return $result->body .= $value;
	}
	static private function query_get($var,$name=null,$null=true,$array=true){
		$result = '';
		foreach(self::expand_vars($vars,$var,$name,$array) as $v){
			if(($null || ($v[1] !== null && $v[1] !== '')) && is_string($v[1])) $result .= $v[0].'='.urlencode($v[1]).'&';
		}
		return (empty($result)) ? $result : substr($result,0,-1);
	}
	static private function expand_vars(&$vars,$value,$name=null,$array=true){
		if(!is_array($vars)) $vars = array();
		if(class_exists('File') && ($value instanceof File)){
			$vars[] = array($name,$value);
		}else{
			if(is_array($value)){
				foreach($value as $k => $v){
					self::expand_vars($vars,$v,(isset($name) ? $name.(($array) ? '['.$k.']' : '') : $k),$array);
				}
			}else if(!is_numeric($name)){
				if(is_bool($value)) $value = ($value) ? 'true' : 'false';
				$vars[] = array($name,(string)$value);
			}
		}
		return $vars;
	}
	static public function htmldecode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			$value = preg_replace("/&#[xX]([0-9a-fA-F]+);/eu","'&#'.hexdec('\\1').';'",$value);
			$value = mb_decode_numericentity($value,array(0x0,0x10000,0,0xfffff),"UTF-8");
			$value = html_entity_decode($value,ENT_QUOTES,"UTF-8");
			$value = str_replace(array("\\\"","\\'","\\\\"),array("\"","\'","\\"),$value);
		}
		return $value;
	}
	static private function absolute($a,$b){
		$a = str_replace("\\",'/',$a);
		if($b === '' || $b === null) return $a;
		$b = str_replace("\\",'/',$b);
		if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)) return $b;
		if(preg_match("/^[\w]+\:\/\/[^\/]+/",$a,$h)){
			$a = preg_replace("/^(.+?)[".(($b[0] === '#') ? '#' : "#\?")."].*$/","\\1",$a);
			if($b[0] == '#' || $b[0] == '?') return $a.$b;
			if(substr($a,-1) != '/') $b = (substr($b,0,2) == './') ? '.'.$b : (($b[0] != '.' && $b[0] != '/') ? '../'.$b : $b);
			if($b[0] == '/' && isset($h[0])) return $h[0].$b;
		}else if($b[0] == '/'){
			return $b;
		}
		$p = array(array('://','/./','//'),array('#R#','/','/'),array("/^\/(.+)$/","/^(\w):\/(.+)$/"),array("#T#\\1","\\1#W#\\2",''),array('#R#','#T#','#W#'),array('://','/',':/'));
		$a = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$a));
		$b = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$b));
		$d = $t = $r = '';
		if(strpos($a,'#R#')){
			list($r) = explode('/',$a,2);
			$a = substr($a,strlen($r));
			$b = str_replace('#T#','',$b);
		}
		$al = preg_split("/\//",$a,-1,PREG_SPLIT_NO_EMPTY);
		$bl = preg_split("/\//",$b,-1,PREG_SPLIT_NO_EMPTY);
	
		for($i=0;$i<sizeof($al)-substr_count($b,'../');$i++){
			if($al[$i] != '.' && $al[$i] != '..') $d .= $al[$i].'/';
		}
		for($i=0;$i<sizeof($bl);$i++){
			if($bl[$i] != '.' && $bl[$i] != '..') $t .= '/'.$bl[$i];
		}
		$t = (!empty($d)) ? substr($t,1) : $t;
		$d = (!empty($d) && $d[0] != '/' && substr($d,0,3) != '#T#' && !strpos($d,'#W#')) ? '/'.$d : $d;
		return str_replace($p[4],$p[5],$r.$d.$t);
	}
}