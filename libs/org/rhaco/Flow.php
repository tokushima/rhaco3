<?php
namespace org\rhaco;
use \org\rhaco\Conf;
/**
 * リクエストから出力までの流れを制御する
 * @conf string $app_url アプリケーションのベースURL
 * @conf string $template_path テンプレートファイルのベースパス
 * @conf string $media_url メディアファイルのベースURL
 * @conf boolean $secure patternsでsecure指定された場合にhttpsとするか
 * @conf string $exception_log_level Exceptionが発生した際にLogに出力するログレベル (error,warn,info,debug)
 */
class Flow{
	private $module = array();
	private $app_url;
	private $media_url;
	private $template_path;
	private $template;
	private $package_media_url = 'package/resources/media';
	static private $get_maps = false;
	static private $output_maps = array();
	
	public function __construct($app_url=null){
		list($d) = debug_backtrace(false);
		$f = str_replace("\\",'/',$d['file']);
		$this->app_url = Conf::get('app_url',$app_url);
		if(empty($this->app_url)) $this->app_url = dirname('http://localhost/'.preg_replace("/.+\/workspace\/(.+)/","\\1",$f));
		if(substr($this->app_url,-1) != '/') $this->app_url .= '/';
		$this->template_path = str_replace("\\",'/',Conf::get('template_path',\org\rhaco\io\File::resource_path('templates')));
		if(substr($this->template_path,-1) != '/') $this->template_path .= '/';
		$this->media_url = str_replace("\\",'/',Conf::get('media_url',$this->app_url.'resources/media/'));
		if(substr($this->media_url,-1) != '/') $this->media_url .= '/';
		if(($branch = substr(basename($f),0,-4)) !== 'index') $this->app_url = $this->app_url.$branch.'/';
		$this->template = new Template();
	}
	public function package_media_url($path){
		if(substr($path,0,1) == '/') $path = substr($path,1);
		if(substr($path,-1) == '/') $path = substr($path,0,-1);
		$this->package_media_url = $path;
	}
	/**
	 * テンプレートのパスを設定する
	 * @param string $path
	 */
	public function template_path($path){
		$this->template_path = str_replace("\\",'/',$path);
		if(substr($this->template_path,-1) != '/') $this->template_path .= '/';
	}
	/**
	 * メディアのURLを設定する
	 * @param string $path
	 */
	public function media_url($path){
		$this->media_url = $path;
		if(substr($this->media_url,-1) != '/') $this->media_url .= '/';
		return $this;
	}
	/**
	 * outputで定義されたmapsを取得する
	 * @param string $file
	 * @return array
	 */
	static public function get_maps($file){
		$key = basename($file);
		if(!isset(self::$output_maps[$key])){
			self::$get_maps = true;
			self::$output_maps[$key] = array();
			
			try{
				ob_start();
					include_once($file);
				ob_end_clean();
			}catch(\Exception $e){
				\org\rhaco\Log::error($e);
			}
		}
		return self::$output_maps[$key];
	}
	/**
	 * 出力する
	 * @param array $map
	 */
	public function output($p0=null,$p1=null,$p2=null,$p3=null,$p4=null,$p5=null,$p6=null,$p7=null,$p8=null,$p9=null){
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null));			
		$map = ($this->has_object_module('flow_map_loader')) ? $this->object_module('flow_map_loader',$p0,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9) : $p0;
		$apps = $urls = array();
		$idx = 0;
		$theme = $put_block = null;

		if(isset($map['patterns']) && is_array($map['patterns'])){
			foreach($map['patterns'] as $k => $v){
				if(is_int($k) || isset($map['patterns'][$k]['patterns'])){
					if(empty($map['patterns'][$k])){
						unset($map['patterns'][$k]);
					}else{
						$maps = $map['patterns'][$k];
						unset($map['patterns'][$k]);
						if(!isset($maps['patterns']) || !is_array($maps['patterns'])) throw new \InvalidArgumentException('patterns not found');
						$maps_url = is_int($k) ? null : $k.'/';

						$maps_nam = isset($maps['name']) ? $maps['name'] : null;
						$maps_act = isset($maps['action']) ? $maps['action'] : null;
						$maps_med = isset($maps['media_path']) ? $maps['media_path'] : null;
						$maps_the = isset($maps['theme_path']) ? $maps['theme_path'] : null;
						$maps_tem = isset($maps['template_path']) ? $maps['template_path'] : null;
						$maps_err = isset($maps['error_template']) ? $maps['error_template'] : null;
						$maps_sup = isset($maps['template_super']) ? $maps['template_super'] : null;
						$maps_mod = isset($maps['modules']) ? (is_array($maps['modules']) ? $maps['modules'] : array($maps['modules'])) : array();
						$maps_arg = isset($maps['args']) ? (is_array($maps['args']) ? $maps['args'] : array($maps['args'])) : array();
						foreach($maps['patterns'] as $u => $m){
							if(!empty($maps_act) && isset($m['action'])) $m['action'] = $maps_act.'::'.$m['action'];
							if(!empty($maps_nam)) $m['name'] = $maps_nam.((isset($m['name']) && !empty($m['name'])) ? '/'.$m['name'] : '');
							if(!empty($maps_med)) $m['media_path'] = $maps_med;
							if(!empty($maps_the)) $m['theme_path'] = $maps_the;
							if(!empty($maps_tem)) $m['template_path'] = $maps_tem;
							if(!empty($maps_err)) $m['error_template'] = $maps_err;
							if(!empty($maps_sup)) $m['template_super'] = $maps_sup;
							if(!empty($maps_mod)) $m['modules'] = array_merge($maps_mod,(isset($m['modules']) ? (is_array($m['modules']) ? $m['modules'] : array($m['modules'])) : array()));
							if(!empty($maps_arg)) $m['args'] = array_merge($maps_arg,(isset($m['args']) ? (is_array($m['args']) ? $m['args'] : array($m['args'])) : array()));
							$map['patterns'][$maps_url.$u] = $m;
						}
					}
				}
			}
			foreach($map['patterns'] as $k => $v){
				if(preg_match('/^(.*)\$(.+)$/',$k,$m)) list($k,$v['name']) = array(trim($m[1]),trim($m[2]));
				if(substr($k,0,1) == '/') $k = substr($k,1);
				if(substr($k,-1) == '/') $k = substr($k,0,-1);
				if(is_string($v)) $v = array('class'=>$v);
				if(!isset($v['name'])) $v['name'] = $k;
				if(isset($v['action'])){
					if(strpos($v['action'],'::') !== false){
						list($v['class'],$v['method']) = explode('::',$v['action']);
					}else{
						$v['class'] = $v['action'];
					}
					unset($v['action']);
				}
				if(isset($v['class']) && !isset($v['method'])){
					try{
						$n = isset($v['name']) ? $v['name'] : $v['class'];
						$r = new \ReflectionClass(str_replace('.',"\\",$v['class']));						
						
						// TODO 
						$automaps = false;
						if(class_exists('\org\rhaco\Object') && $r->isSubclassOf('\org\rhaco\Object')){
							$automaps = call_user_func_array(array($r->getName(),'anon'),array('automaps',false));
						}else{
							$d = '';
							while($r->getParentClass() !== false){
								$d = $r->getDocComment().$d;
								$r = $r->getParentClass();
							}
							$anon = \org\rhaco\Object::anon_decode($d,'class');
							$automaps = isset($anon['automaps']) ? (boolean)$anon['automaps'] : false;
						}
						$suffix = isset($v['suffix']) ? $v['suffix'] : '';
						foreach($r->getMethods() as $m){
							if($m->isPublic() && !$m->isStatic() && substr($m->getName(),0,1) != '_'
								&& (!$automaps || preg_match('/@automap[\s]*/',$m->getDocComment()))
							 ){
								$url = $k.(($m->getName() == 'index') ? '' : (($k == '') ? '' : '/').$m->getName()).str_repeat('/(.+)',$m->getNumberOfRequiredParameters());
								for($i=0;$i<=$m->getNumberOfParameters()-$m->getNumberOfRequiredParameters();$i++){
									$apps[$url.$suffix] = array_merge($v,array('name'=>$n.'/'.$m->getName().'/'.$i,'class'=>$v['class'],'method'=>$m->getName(),'num'=>$i,'='=>dirname($r->getFilename())));
									$url .= '/(.+)';
								}
							}
						}
					}catch(\ReflectionException $e){
						throw new \InvalidArgumentException($v['class'].' not found');
					}
				}else{
					$apps[(string)$k] = $v;
				}
			}
			list($url,$surl) = array($this->app_url,str_replace('http://','https://',$this->app_url));
			$map_secure = (isset($map['secure']) && $map['secure'] === true);
			$conf_secure = (\org\rhaco\Conf::get('secure',true) === true);
			foreach($apps as $u => $m){
				$m['secure'] = ($conf_secure && (((isset($m['secure']) && $m['secure'] === true)) || (!isset($m['secure']) && $map_secure)));
				$cnt = 0;
				$fu = \org\rhaco\net\Path::absolute(
						($m['secure'] ? $surl : $url)
						,(empty($u)) ? '' : substr(preg_replace_callback("/([^\\\\])(\(.*?[^\\\\]\))/",function($n){return $n[1].'%s';},' '.$u,-1,$cnt),1)
						);
				foreach(array('template_path','media_path','theme_path') as $k){
					if(isset($map[$k]) || isset($m[$k])) $m[$k] = \org\rhaco\net\Path::slash((isset($map[$k])?$map[$k]:''),null,true).(isset($m[$k])?$m[$k]:'');
				}
				$apps[$u] = array_merge($m,array(
									'url'=>$u
									,'format'=>$fu
									,'num'=>$cnt
									,'pattern'=>str_replace(array("\\\\","\\.",'_ESC_'),array('_ESC_','.',"\\"),$fu)
								));
			}
			if(self::$get_maps){
				list($d) = debug_backtrace(false);
				self::$output_maps[basename($d['file'])] = $apps;
				self::$get_maps = false;
				return;
			}
			foreach(array_keys($apps) as $k => $u) $urls[$u] = strlen(preg_replace("/[\W]/",'',$u));
			arsort($urls);
			krsort($urls);
			if(preg_match("/^\/".str_replace("/","\\/",$this->package_media_url)."\/(\d+)\/(.+)$/",$pathinfo,$m) && sizeof($urls) >= $m[1]){
				for(reset($urls),$i=0;$i<$m[1];$i++) next($urls);
				$v = $apps[key($urls)];
				if(isset($v['='])) \org\rhaco\net\http\File::attach($v['='].'/resources/media/'.$m[2]);
				\org\rhaco\net\http\Header::send_status(404);
				exit;
			}
			foreach($urls as $k => $null){
				if(preg_match("/^".(empty($k) ? '' : "\/").str_replace(array("\/",'/','@#S'),array('@#S',"\/","\/"),$k).'[\/]{0,1}$/',$pathinfo,$p)){
					if(isset($apps[$k]['mode']) && !empty($apps[$k]['mode'])){
						$mode = \Rhaco3::mode();
						$bool = false;
						foreach(explode(',',$apps[$k]['mode']) as $m){
							if($mode == trim($m)){
								$bool = true;
								break;
							}
						}
						if(!$bool) break;
					}
					array_shift($p);
					$obj = $modules = array();
					$current_url = \org\rhaco\Request::current_url();
					if(isset($apps[$k]['secure']) && $apps[$k]['secure'] === true && \org\rhaco\Conf::get('secure',true) !== false){
						$this->template->secure(true);
						if(substr($current_url,0,5) === 'http:' &&
							(!isset($_SERVER['HTTP_X_FORWARDED_HOST']) || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])))
						){
							header('Location: '.preg_replace("/^.+(:\/\/.+)$/","https\\1",$current_url));
							exit;
						}
					}
					if(isset($apps[$k]['redirect'])){
						header('Location: '.\org\rhaco\net\Path::absolute($this->app_url,$apps[$k]['redirect']));
						exit;
					}
					if(isset($map['modules'])){
						foreach((is_array($map['modules']) ? $map['modules'] : array($map['modules'])) as $m) $modules[] = $this->str_reflection($m)->newInstance();
					}
					if(isset($apps[$k]['modules'])){
						foreach((is_array($apps[$k]['modules']) ? $apps[$k]['modules'] : array($apps[$k]['modules'])) as $m) $modules[] = $this->str_reflection($m)->newInstance();
					}
					try{
						foreach($modules as $m) $this->set_object_module($m);
						if(isset($apps[$k]['class'])){
							if(!class_exists(str_replace('.',"\\",$apps[$k]['class']))) throw new \InvalidArgumentException($apps[$k]['class'].' not found');
							if(isset($map['session'])) \org\rhaco\Conf::set('module',$map['session']);
							$r = $this->str_reflection($apps[$k]['class']);
							$obj = $r->newInstance();
							$func_exception = null;
							
							if($obj instanceof \org\rhaco\Object){
								foreach($modules as $m) $obj->set_object_module($m);
							}
							if($obj instanceof \org\rhaco\flow\FlowInterface){
								$obj->set_select_map_name($apps[$k]['name']);
								$obj->set_maps($apps);
								$obj->set_args((isset($apps[$k]['args']) && is_array($apps[$k]['args'])) ? $apps[$k]['args'] : array());
								$obj->before();
								$theme = $obj->get_theme();
								$put_block = $obj->get_block();
							}
							try{
								$result = call_user_func_array(array($obj,$apps[$k]['method']),$p);
								if($result !== null) $obj = $result;
							}catch(\Exception $e){
								$func_exception = $e;
							}						
							if($obj instanceof \org\rhaco\flow\FlowInterface){
								$obj->after();
								if(\org\rhaco\Exceptions::has()) $obj->exception();
								$theme = $obj->get_theme();
								$put_block = $obj->get_block();
								$ext_modules = $obj->get_template_modules();
								if(!empty($ext_modules)){
									if(!is_array($ext_modules)) $ext_modules = array($ext_modules);
									foreach($ext_modules as $o) $this->template->set_object_module($o);
								}
							}
							if($func_exception instanceof \Exception) throw $func_exception;
						}
						if(isset($apps[$k]['template'])){
							$this->print_template($this->template_path,$apps[$k]['template'],$this->media_url,$theme,$put_block,$obj,$apps,$k);
						}else if(isset($apps[$k]['=']) && is_file($t = $apps[$k]['='].'/resources/templates/'.$apps[$k]['method'].'.html')){
							$this->print_template(dirname($t).'/',basename($t),$this->app_url.$this->package_media_url.'/'.$idx,$theme,$put_block,$obj,$apps,$k,false);
						}else if($this->has_object_module('flow_output')){
							/**
							 * 結果出力
							 * @param mixed $obj
							 */
							$this->object_module('flow_output',$obj);
						}else{
							\org\rhaco\Exceptions::throw_over();
							$xml = new \org\rhaco\Xml('result',$obj);
							$xml->output();
						}
						exit;
					}catch(\Exception $e){
						if(($level = \org\rhaco\Conf::get('exception_log_level')) !== null && ($level == 'error' || $level == 'warn' || $level == 'info' || $level == 'debug')){
							\org\rhaco\Log::$level($e);
						}
						if($this->has_object_module('flow_handle_exception')){
							/**
							 * 例外発生
							 * @param Exception $e
							 */
							$this->object_module('flow_handle_exception',$e);
						}
						if(isset($map['error_status'])) \org\rhaco\net\http\Header::send_status($map['error_status']);
						if($this->has_object_module('flow_exception_output')){
							/**
							 * 例外発生時の出力
							 * @param mixed $obj actionで返却された値
							 * @param Exception $e 発生した例外
							 */
							$this->object_module('flow_exception_output',$obj,$e);
							exit;
						}else if(isset($map['error_redirect'])){
							if(strpos($map['error_redirect'],'://') === false){
								$map['error_redirect'] = $this->app_url.$map['error_redirect'];
							}
							\org\rhaco\net\http\Header::send_status(302);
							header('Location: '.$map['error_redirect']);
							exit;
						}else{
							if(isset($apps[$k]['error_template'])){
								if(!($e instanceof \org\rhaco\Exceptions)) \org\rhaco\Exceptions::add($e);
								$this->print_template($this->template_path,$apps[$k]['error_template'],$this->media_url,$theme,$put_block,$obj,$apps,$k);
								exit;
							}else if(isset($map['error_template'])){
								if(!($e instanceof \org\rhaco\Exceptions)) \org\rhaco\Exceptions::add($e);
								$this->print_template($this->template_path,$map['error_template'],$this->media_url,$theme,$put_block,$obj,$apps,$k);
								exit;
							}else if(isset($apps[$k]['=']) && is_file($t = $apps[$k]['='].'/resources/templates/error.html')){
								if(!($e instanceof \org\rhaco\Exceptions)) \org\rhaco\Exceptions::add($e);
								$this->print_template(dirname($t).'/',basename($t),$this->app_url.$this->package_media_url.'/'.$idx,$theme,$put_block,$obj,$apps,$k,false);
								exit;
							}else if(isset($apps[$k]['template']) || (isset($apps[$k]['=']) && is_file($apps[$k]['='].'/resources/templates/'.$apps[$k]['method'].'.html'))){
								if(!isset($map['error_status'])) \org\rhaco\net\http\Header::send_status(500);
								exit;
							}else{
								if(!($e instanceof \org\rhaco\Exceptions)) \org\rhaco\Exceptions::add($e);
								$this->handle_exception_xml();
							}
						}
						throw $e;
					}
				}
				$idx++;
			}
		}
		if(isset($map['nomatch_redirect'])){
			if(strpos($map['nomatch_redirect'],'://') === false){
				if(substr($map['nomatch_redirect'],0,1) == '/') $map['nomatch_redirect'] = substr($map['nomatch_redirect'],1);
				$map['nomatch_redirect'] = $this->app_url.$map['nomatch_redirect'];
			}
			\org\rhaco\net\http\Header::send_status(302);
			header('Location: '.$map['nomatch_redirect']);
			exit;
		}
		if(($level = \org\rhaco\Conf::get('notfound_log_level')) !== null && ($level == 'error' || $level == 'warn' || $level == 'info' || $level == 'debug')){
			\org\rhaco\Log::$level(\org\rhaco\Request::current_url().' (`'.$pathinfo.'`) bad request');
		}
		\org\rhaco\net\http\Header::send_status(404);
		exit;
	}
	private function print_template($template_path,$template,$media_url,$theme,$put_block,$obj,$apps,$index,$path_replace=true){
		if($path_replace){
			if(isset($apps[$index]['media_path'])) $media_url = $media_url.\org\rhaco\net\Path::slash($apps[$index]['media_path'],true,false);
			if(isset($apps[$index]['template_path'])) $template_path = $template_path.\org\rhaco\net\Path::slash($apps[$index]['template_path'],false,true);
		}
		if(isset($apps[$index]['theme_path']) || !empty($theme)){
			if(empty($theme)) $theme = 'default';
			$theme = \org\rhaco\net\Path::slash($theme,true,true);
			$theme_path =\org\rhaco\net\Path::slash((isset($apps[$index]['theme_path']) ? $apps[$index]['theme_path'] : 'theme'),false,false);
			$template = $template_path.$theme_path.$theme.$template;
			$media_url = $media_url.$theme_path.$theme;
		}else{
			$template = $template_path.$template;
		}
		if(!empty($put_block)) $this->template->put_block($this->template_path.$put_block);
		if(isset($apps[$index]['template_super'])) $this->template->template_super($this->template_path.$apps[$index]['template_super']);

		$this->template->media_url($media_url);
		$this->template->cp($obj);
		if(isset($apps[$index]['vars'])) $this->template->cp($apps[$index]['vars']);
		$this->template->vars('t',new \org\rhaco\flow\module\Helper($media_url,(isset($apps[$index]['name']) ? $apps[$index]['name'] : null),$apps,$obj));
		$src = $this->template->read($template);
		/**
		 * テンプレートの出力
		 * @param string $src
		 */
		$this->object_module('before_flow_print_template',$src);
		print($src);
	}
	private function handle_exception_xml(){
		$xml = new \org\rhaco\Xml('error');
			foreach(\org\rhaco\Exceptions::groups() as $g){
				foreach(\org\rhaco\Exceptions::gets($g) as $e){
					$message = new \org\rhaco\Xml('message',$e->getMessage());
					$message->add('group',$g);
					$message->add('type',basename(str_replace("\\",'/',get_class($e))));
					$xml->add($message);
				}
			}
		$xml->output();
	}
	private function str_reflection($package){
		$class_name = substr($package,strrpos($package,'.')+1);
		try{
			return new \ReflectionClass("\\".str_replace('.',"\\",$package));
		}catch(\ReflectionException $e){
			if(!empty($class_name)){
				try{
					return new \ReflectionClass($class_name);
				}catch(\ReflectionException $f){}
			}
			throw $e;
		}
	}
	/**
	 * モジュールを追加する
	 * @param object $o
	 */
	public function set_object_module($o){
		$this->module[] = $o;
		$this->template->set_object_module($o);
		return $this;
	}
	private function has_object_module($n){
		foreach($this->module as $o){
			if(method_exists($o,$n)) return true;
		}
		return false;
	}
	private function object_module($n,&$p0=null,&$p1=null,&$p2=null,&$p3=null,&$p4=null,&$p5=null,&$p6=null,&$p7=null,&$p8=null,&$p9=null){
		$r = null;
		foreach($this->module as $o){
			if(method_exists($o,$n)) $r = call_user_func_array(array($o,$n),array(&$p0,&$p1,&$p2,&$p3,&$p4,&$p5,&$p6,&$p7,&$p8,&$p9));
		}
		return $r;
	}
	/**
	 * ローダーを指定してインスタンスを作成する
	 * @param string $loader_module
	 * @return $this
	 */
	static public function loader($loader_module){
		list($d) = debug_backtrace(false);
		$self = new self(dirname($d['file']));
		if(!is_object($loader_module)) $loader_module = $self->str_reflection($loader_module)->newInstance();
		$self->set_object_module($loader_module);
		return $self;
	}
}