<?php
namespace chaco{
	class Conf{
		static private $conf = array();
		
		static public function set($k,$v){
			self::$conf[$k] = $v;
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
	class NotFoundException extends \Exception{
	}
	class AssertFailure extends \Exception{
		private $expectation;
		private $result;
		private $has = false;
		
		public function ab($expectation,$result){
			$this->expectation = $expectation;
			$this->result = $result;
			$this->has = true;
			return $this;
		}
		public function has(){
			return $this->has;
		}
		public function expectation(){
			return $this->expectation;
		}
		public function result(){
			return $this->result;
		}
	}
	class Assert{
		static public function expvar($var){
			if(is_numeric($var)) return strval($var);
			if(is_object($var)) $var = get_object_vars($var);
			if(is_array($var)){
				foreach($var as $key => $v){
					$var[$key] = self::expvar($v);
				}
			}
			return $var;
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
				$rtn = ob_get_clean();
				
				if(preg_match('/(Parse|Fatal) error:.+/',$rtn,$m)){
					$err = (preg_match('/syntax error.+code on line\s*(\d+)/',$rtn,$line) ?
							'Parse error: syntax error '.$test_path.' code on line '.$line[1]
							: $m[0]);
				}
				$res = array(1,0);
			}catch(\chaco\AssertFailure $e){
				list($debug) = $e->getTrace();
				$res = array(-1,0,$debug['file'],$debug['line'],$e->getMessage(),$e->expectation(),$e->result(),$e->has());
			}catch(\Exception $e){
				$trace = $e->getTrace();
				for($i=sizeof($trace);$i>=0;$i--){
					if(isset($trace[$i]['file']) && $trace[$i]['file'] != __FILE__){
						$res = array(-2,0,$trace[$i]['file'],$trace[$i]['line'],(string)$e);
						break;
					}
				}
				if(!isset($res)){
					$res = array(-2,0,$e->getFile(),$e->getLine(),(string)$e);
				}
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
	/**
	 * XMLを処理する
	 */
	class Xml implements \IteratorAggregate{
		private $attr = array();
		private $plain_attr = array();
		private $name;
		private $value;
		private $close_empty = true;
	
		private $plain;
		private $pos;
		private $esc = true;
	
		public function __construct($name=null,$value=null){
			if($value === null && is_object($name)){
				$n = explode('\\',get_class($name));
				$this->name = array_pop($n);
				$this->value($name);
			}else{
				$this->name = trim($name);
				$this->value($value);
			}
		}
		/**
		 * (non-PHPdoc)
		 * @see IteratorAggregate::getIterator()
		 */
		public function getIterator(){
			return new \ArrayIterator($this->attr);
		}
		/**
		 * 値が無い場合は閉じを省略する
		 * @param boolean
		 * @return boolean
		 */
		public function close_empty(){
			if(func_num_args() > 0) $this->close_empty = (boolean)func_get_arg(0);
			return $this->close_empty;
		}
		/**
		 * エスケープするか
		 * @param boolean $bool
		 */
		public function escape($bool){
			$this->esc = (boolean)$bool;
			return $this;
		}
		/**
		 * setできた文字列
		 * @return string
		 */
		public function plain(){
			return $this->plain;
		}
		/**
		 * 子要素検索時のカーソル
		 * @return integer
		 */
		public function cur(){
			return $this->pos;
		}
		/**
		 * 要素名
		 * @return string
		 */
		public function name($name=null){
			if(isset($name)) $this->name = $name;
			return $this->name;
		}
		private function get_value($v){
			if($v instanceof self){
				$v = $v->get();
			}else if(is_bool($v)){
				$v = ($v) ? 'true' : 'false';
			}else if($v === ''){
				$v = null;
			}else if(is_array($v) || is_object($v)){
				$r = '';
				foreach($v as $k => $c){
					if(is_numeric($k) && is_object($c)){
						$e = explode('\\',get_class($c));
						$k = array_pop($e);
					}
					if(is_numeric($k)) $k = 'data';
					$x = new self($k,$c);
					$x->escape($this->esc);
					$r .= $x->get();
				}
				$v = $r;
			}else if($this->esc && strpos($v,'<![CDATA[') === false && preg_match("/&|<|>|\&[^#\da-zA-Z]/",$v)){
				$v = '<![CDATA['.$v.']]>';
			}
			return $v;
		}
		/**
		 * 値を設定、取得する
		 * @param mixed
		 * @param boolean
		 * @return string
		 */
		public function value(){
			if(func_num_args() > 0) $this->value = $this->get_value(func_get_arg(0));
			if(strpos($this->value,'<![CDATA[') === 0) return substr($this->value,9,-3);
			return $this->value;
		}
		/**
		 * 値を追加する
		 * ２つ目のパラメータがあるとアトリビュートの追加となる
		 * @param mixed $arg
		 */
		public function add($arg){
			if(func_num_args() == 2){
				$this->attr(func_get_arg(0),func_get_arg(1));
			}else{
				$this->value .= $this->get_value(func_get_arg(0));
			}
			return $this;
		}
		/**
		 * アトリビュートを取得する
		 * @param string $n 取得するアトリビュート名
		 * @param string $d アトリビュートが存在しない場合の代替値
		 * @return string
		 */
		public function in_attr($n,$d=null){
			return isset($this->attr[strtolower($n)]) ? ($this->esc ? htmlentities($this->attr[strtolower($n)],ENT_QUOTES,'UTF-8') : $this->attr[strtolower($n)]) : (isset($d) ? (string)$d : null);
		}
		/**
		 * アトリビュートから削除する
		 * パラメータが一つも無ければ全件削除
		 */
		public function rm_attr(){
			if(func_num_args() === 0){
				$this->attr = array();
			}else{
				foreach(func_get_args() as $n) unset($this->attr[$n]);
			}
		}
		/**
		 * アトリビュートがあるか
		 * @param string $name
		 * @return boolean
		 */
		public function is_attr($name){
			return array_key_exists($name,$this->attr);
		}
		/**
		 * アトリビュートを設定
		 * @return self $this
		 */
		public function attr($key,$value){
			$this->attr[strtolower($key)] = is_bool($value) ? (($value) ? 'true' : 'false') : $value;
			return $this;
		}
		/**
		 * 値の無いアトリビュートを設定
		 * @param string $v
		 */
		public function plain_attr($v){
			$this->plain_attr[] = $v;
		}
		/**
		 * XML文字列を返す
		 */
		public function get($encoding=null){
			if($this->name === null) throw new \LogicException('undef name');
			$attr = '';
			$value = ($this->value === null || $this->value === '') ? null : (string)$this->value;
			foreach($this->attr as $k => $v) $attr .= ' '.$k.'="'.$this->in_attr($k).'"';
			return ((empty($encoding)) ? '' : '<?xml version="1.0" encoding="'.$encoding.'" ?'.'>'.PHP_EOL)
			.('<'.$this->name.$attr.(implode(' ',$this->plain_attr)).(($this->close_empty && !isset($value)) ? ' /' : '').'>')
			.$this->value
			.((!$this->close_empty || isset($value)) ? sprintf('</%s>',$this->name) : '');
		}
		public function __toString(){
			return $this->get();
		}
		/**
		 * 検索する
		 * @param string $name
		 * @param integer $offset
		 * @param integer $length
		 * @return \chaco\XmlIterator
		 */
		public function find_all($name,$offset=0,$length=0){
			if(is_string($name) && strpos($name,'/') !== false){
				list($name,$path) = explode('/',$name,2);
				foreach(new \chaco\XmlIterator($name,$this->value(),0,0) as $t){
					try{
						$it = $t->find_all($path,$offset,$length);
						if($it->valid()){
							reset($it);
							return $it;
						}
					}catch(\chaco\NotFoundException $e){}
				}
				throw new \chaco\NotFoundException();
			}
			return new \chaco\XmlIterator($name,$this->value(),$offset,$length);
		}
		/**
		 * １件取得する
		 * @param string $name
		 * @param integer $offset
		 * @throws \chaco\NotFoundException
		 * @return \chaco\Xml
		 */
		public function find_get($name,$offset=0){
			foreach($this->find_all($name,$offset,1) as $x){
				return $x;
			}
			throw new \chaco\NotFoundException($name.' not found');
		}
		/**
		 * 匿名タグとしてインスタンス生成
		 * @param string $value
		 * @return \chaco\Xml
		 */
		static public function anonymous($value){
			$xml = new self('XML'.uniqid());
			$xml->escape(false);
			$xml->value($value);
			$xml->escape(true);
			return $xml;
		}
		/**
		 * タグの検出
		 * @param string $plain
		 * @param string $name
		 * @throws \chaco\NotFoundException
		 * @return \chaco\Xml
		 */
		static public function extract($plain,$name=null){
			if(!(!empty($name) && strpos($plain,$name) === false) && self::find_extract($x,$plain,$name)){
				return $x;
			}
			throw new \chaco\NotFoundException($name.' not found');
		}
		static private function find_extract(&$x,$plain,$name=null,$vtag=null){
			$plain = (string)$plain;
			$name = (string)$name;
			if(empty($name) && preg_match("/<([\w\:\-]+)[\s][^>]*?>|<([\w\:\-]+)>/is",$plain,$m)){
				$name = str_replace(array("\r\n","\r","\n"),'',(empty($m[1]) ? $m[2] : $m[1]));
			}
			$qname = preg_quote($name,'/');
			if(!preg_match("/<(".$qname.")([\s][^>]*?)>|<(".$qname.")>/is",$plain,$parse,PREG_OFFSET_CAPTURE)) return false;
			$x = new self();
			$x->pos = $parse[0][1];
			$balance = 0;
			$attrs = '';
	
			if(substr($parse[0][0],-2) == '/>'){
				$x->name = $parse[1][0];
				$x->plain = empty($vtag) ? $parse[0][0] : preg_replace('/'.preg_quote(substr($vtag,0,-1).' />','/').'/',$vtag,$parse[0][0],1);
				$attrs = $parse[2][0];
			}else if(preg_match_all("/<[\/]{0,1}".$qname."[\s][^>]*[^\/]>|<[\/]{0,1}".$qname."[\s]*>/is",$plain,$list,PREG_OFFSET_CAPTURE,$x->pos)){
				foreach($list[0] as $arg){
					if(($balance += (($arg[0][1] == '/') ? -1 : 1)) <= 0 &&
							preg_match("/^(<(".$qname.")([\s]*[^>]*)>)(.*)(<\/\\2[\s]*>)$/is",
									substr($plain,$x->pos,($arg[1] + strlen($arg[0]) - $x->pos)),
									$match
							)
					){
						$x->plain = $match[0];
						$x->name = $match[2];
						$x->value = ($match[4] === '' || $match[4] === null) ? null : $match[4];
						$attrs = $match[3];
						break;
					}
				}
				if(!isset($x->plain)){
					return self::find_extract($x,preg_replace('/'.preg_quote($list[0][0][0],'/').'/',substr($list[0][0][0],0,-1).' />',$plain,1),$name,$list[0][0][0]);
				}
			}
			if(!isset($x->plain)) return false;
			if(!empty($attrs)){
				if(preg_match_all("/[\s]+([\w\-\:]+)[\s]*=[\s]*([\"\'])([^\\2]*?)\\2/ms",$attrs,$attr)){
					foreach($attr[0] as $id => $value){
						$x->attr($attr[1][$id],$attr[3][$id]);
						$attrs = str_replace($value,'',$attrs);
					}
				}
				if(preg_match_all("/([\w\-]+)/",$attrs,$attr)){
					foreach($attr[1] as $v) $x->attr($v,$v);
				}
			}
			return true;
		}
	}
	class XmlIterator implements \Iterator{
		private $name = null;
		private $plain = null;
		private $tag = null;
		private $offset = 0;
		private $length = 0;
		private $count = 0;
	
		public function __construct($tag_name,$value,$offset,$length){
			$this->name = $tag_name;
			$this->plain = $value;
			$this->offset = $offset;
			$this->length = $length;
			$this->count = 0;
		}
		public function key(){
			$this->tag->name();
		}
		public function current(){
			$this->plain = substr($this->plain,0,$this->tag->cur()).substr($this->plain,$this->tag->cur() + strlen($this->tag->plain()));
			$this->count++;
			return $this->tag;
		}
		public function valid(){
			if($this->length > 0 && ($this->offset + $this->length) <= $this->count){
				return false;
			}
			if(is_string($this->name) && strpos($this->name,'|') !== false){
				$this->name = explode('|',$this->name);
			}
			if(is_array($this->name)){
				$tags = array();
				foreach($this->name as $name){
					try{
						$get_tag = \chaco\Xml::extract($this->plain,$name);
						$tags[$get_tag->cur()] = $get_tag;
					}catch(\chaco\NotFoundException $e){
					}
				}
				if(empty($tags)) return false;
				ksort($tags,SORT_NUMERIC);
				foreach($tags as $this->tag) return true;
			}
			try{
				$this->tag = \chaco\Xml::extract($this->plain,$this->name);
				return true;
			}catch(\chaco\NotFoundException $e){
			}
			return false;
		}
		public function next(){
		}
		public function rewind(){
			for($i=0;$i<$this->offset;$i++){
				if($this->valid()){
					$this->current();
				}
			}
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
				throw new \RuntimeException($err_code.': '.curl_error($this->resource).', ['.$method.'] '.$url);
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
		/**
		 * XMLオブジェクトとして返す
		 * @return \chaco\Xml
		 */
		public function xml(){
			return \chaco\Xml::extract($this->body());
		}
	}
	class Args{
		static private $opt = array();
		static private $value = array();
	
		static public function init(){
			$opt = $value = array();
			$argv = array_slice((isset($_SERVER['argv']) ? $_SERVER['argv'] : array()),1);
				
			for($i=0;$i<sizeof($argv);$i++){
				if(substr($argv[$i],0,2) == '--'){
					$opt[substr($argv[$i],2)][] = ((isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : true);
				}else if(substr($argv[$i],0,1) == '-'){
					$keys = str_split(substr($argv[$i],1),1);
					foreach($keys as $k){
						$opt[$k][] = true;
					}
				}else{
					$value[] = $argv[$i];
				}
			}
			self::$opt = $opt;
			self::$value = $value;
		}
		static public function opt($name,$default=false){
			return array_key_exists($name,self::$opt) ? self::$opt[$name][0] : $default;
		}
		static public function value($default=null){
			return isset(self::$value[0]) ? self::$value[0] : $default;
		}
		static public function opts($name){
			return array_key_exists($name,self::$opt) ? self::$opt[$name] : array();
		}
		static public function values(){
			return self::$value;
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
	/**
	 * 失敗とする
	 * @param string $msg 失敗時メッセージ
	 * @throws \chaco\AssertFailure
	 */
	function failure($msg='failure'){
		throw new \chaco\AssertFailure($msg);
	}
	/**
	 *　等しい
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 * @param string $msg 失敗時メッセージ
	 */
	function eq($expectation,$result,$msg='failure equals'){
		if(\chaco\Assert::expvar($expectation) !== \chaco\Assert::expvar($result)){
			$failure = new \chaco\AssertFailure($msg);
			throw $failure->ab($expectation, $result);
		}
	}
	/**
	 * 等しくない
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 * @param string $msg 失敗時メッセージ
	 */
	function neq($expectation,$result,$msg='failure not equals'){
		if(\chaco\Assert::expvar($expectation) === \chaco\Assert::expvar($result)){
			$failure = new \chaco\AssertFailure($msg);
			throw $failure->ab($expectation, $result);
		}
	}
	/**
	 *　文字列中に指定の文字列が存在する
	 * @param string|array $keyword
	 * @param string $src
	 * @param string $msg 失敗時メッセージ
	 */
	function meq($keyword,$src,$msg='failure match'){
		if(mb_strpos($src,$keyword) === false){
			throw new \chaco\AssertFailure($msg);
		}
	}
	/**
	 * 文字列中に指定の文字列が存在しない
	 * @param string $keyword
	 * @param string $src
	 */
	function mneq($keyword,$src,$msg='failure not match'){
		if(mb_strpos($src,$keyword) !== false){
			throw new \chaco\AssertFailure($msg);
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
	if(is_file($f=getcwd().'/bootstrap.php') || is_file($f=getcwd().'/vendor/autoload.php')){
		ob_start();
			include_once($f);
		ob_end_clean();
	}
	if(is_file($inc=substr(__FILE__,0,-4).'.lib.php')){
		include_once($inc);
	}
	if(is_file($inc=substr(__FILE__,0,-4).'.conf.php')){
		$conf = include($inc);
		if(!is_array($conf)) throw new \RuntimeException('invalid '.$inc);
		foreach($conf as $k => $v){
			\chaco\Conf::set($k,$v);
		}
	}
	\chaco\Args::init();
	foreach(array('coverage','c','output','o','libdir','outputdir') as $k){
		if(($v = \chaco\Args::opt($k,null)) !== null){
			\chaco\Conf::set($k,$v);
		}
	}
	
	$path = realpath(\chaco\Args::value(__DIR__));
	if($path === false) die(\chaco\Args::value().' found'.PHP_EOL);
	
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
	
	if(is_file($inc=substr(__FILE__,0,-4).'.fixture.php')){
		include_once($inc);
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
				list(,$time,$file,$line,$msg,$r1,$r2,$has) = $info;
	
				$println();
				$println($file,'1;34');
				$println('['.$line.']: '.$msg,'1;31');
				
				if($has){
					$println($tab.str_repeat('-',70));
					ob_start();
						var_dump($r1);
					$println($tab.str_replace(PHP_EOL,PHP_EOL.$tab,ob_get_clean()));
					
					$println($tab.str_repeat('-',70));
					ob_start();
						var_dump($r2);
					$println($tab.str_replace(PHP_EOL,PHP_EOL.$tab,ob_get_clean()));
				}				
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
						list(,$time,$file,$line,$msg,$r1,$r2,$has) = $info;
						$dir_failures++;
							
						$x = $get_testsuite($dir,$testsuite)->addChild('testcase');
						$x->addAttribute('name',basename($file));
						$x->addAttribute('time',$time);
						$x->addAttribute('line',$line);
	
						if($has){
							ob_start();
								var_dump($r2);
							$failure_value = 'Line. '.$line.': '."\n".ob_get_clean();
							$failure = dom_import_simplexml($x->addChild('failure'));
							$failure->appendChild($failure->ownerDocument->createCDATASection($failure_value));
						}							
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
