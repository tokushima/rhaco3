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
 * @conf string $notfound_log_level 対象のmapが見つからなかった際にLogに出力するログレベル (error,warn,info,debug)
 */
class Flow{
	private $module = array();
	private $branch_url;
	private $app_url;
	private $media_url;
	private $template_path;
	private $template;
	private $package_media_url = 'package/resources/media';
	static private $get_maps = false;
	static private $output_maps = array();
	
	private function entry_file(){
		foreach(debug_backtrace(false) as $d){
			if($d['file'] !== __FILE__) return $d['file'];
		}
		throw new \RuntimeException('no entry file');
	}
	public function __construct($app_url=null){
		$f = str_replace("\\",'/',$this->entry_file());
		$this->app_url = Conf::get('app_url',(isset($app_url) ? $app_url : (isset($_ENV['APP_URL']) ? $_ENV['APP_URL'] : null)));

		if(empty($this->app_url)) $this->app_url = dirname('http://localhost/'.preg_replace("/.+\/workspace\/(.+)/","\\1",$f));
		if(substr($this->app_url,-1) != '/') $this->app_url .= '/';
		$this->template_path = str_replace("\\",'/',Conf::get('template_path',\org\rhaco\io\File::resource_path('templates')));
		if(substr($this->template_path,-1) != '/') $this->template_path .= '/';
		$this->media_url = str_replace("\\",'/',Conf::get('media_url',$this->app_url.'resources/media/'));
		if(substr($this->media_url,-1) != '/') $this->media_url .= '/';		
		$this->branch_url = $this->app_url.((($branch = substr(basename($f),0,-4)) !== 'index') ? $branch.'/' : '');
		$this->template = new Template();
	}
	/**
	 * パッケージメディアのURLを設定する
	 * @param string $path
	 * @return string
	 */
	public function package_media_url($path=null){
		if(!empty($path)){
			if(substr($path,0,1) == '/') $path = substr($path,1);
			if(substr($path,-1) == '/') $path = substr($path,0,-1);
			$this->package_media_url = $path;
		}
		return $this->package_media_url;
	}
	/**
	 * テンプレートのパスを設定する
	 * @param string $path
	 * @return string
	 */
	public function template_path($path=null){
		if(!empty($path)){
			$this->template_path = str_replace("\\",'/',$path);
			if(substr($this->template_path,-1) != '/') $this->template_path .= '/';
		}
		return $this->template_path;
	}
	/**
	 * メディアのURLを設定する
	 * @param string $path
	 * @return string
	 */
	public function media_url($path=null){
		if(!empty($path)){
			$this->media_url = $path;
			if(substr($this->media_url,-1) != '/') $this->media_url .= '/';
		}
		return $this->media_url;
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
					include($file);
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
	public function output($map_array){
		$args = func_get_args();
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null));
		/**
		 * ハンドリングマップ配列を取得する
		 * @return array
		 */
		$map = ($this->has_object_module('flow_map_loader')) ? call_user_func_array(array($this,'object_module'),array_merge(array('flow_map_loader'),$args)) : $map_array;	
		if(is_string($map) && preg_match('/^[\w\.]+$/',$map)) $map = array('patterns'=>array(''=>array('action'=>$map)));
		$apps = $urls = array();
		$idx = $pkg_id =0;
		$put_block = null;

		if(isset($map['patterns']) && is_array($map['patterns'])){
			$exp_patterns = array();
			foreach($map['patterns'] as $k => $v){
				if(is_int($k) || isset($map['patterns'][$k]['patterns'])){
					if(!empty($map['patterns'][$k])){
						$maps = $map['patterns'][$k];
						unset($map['patterns'][$k]);
						if(!isset($maps['patterns']) || !is_array($maps['patterns'])) throw new \InvalidArgumentException('patterns not found');
						$maps_url = is_int($k) ? null : $k.'/';

						$maps_nam = isset($maps['name']) ? $maps['name'] : null;
						$maps_act = isset($maps['action']) ? $maps['action'] : null;
						$maps_med = isset($maps['media_path']) ? $maps['media_path'] : null;
						$maps_tem = isset($maps['template_path']) ? $maps['template_path'] : null;
						$maps_err = isset($maps['error_template']) ? $maps['error_template'] : null;
						$maps_sup = isset($maps['template_super']) ? $maps['template_super'] : null;
						$maps_mod = (isset($maps['modules']) && !empty($maps['modules'])) ? (is_array($maps['modules']) ? $maps['modules'] : array($maps['modules'])) : array();
						$maps_arg = (isset($maps['args']) && !empty($maps['args'])) ? (is_array($maps['args']) ? $maps['args'] : array($maps['args'])) : array();
						foreach($maps['patterns'] as $u => $m){
							if(!empty($maps_act) && isset($m['action'])) $m['action'] = $maps_act.'::'.$m['action'];
							if(!empty($maps_nam)) $m['name'] = $maps_nam.((isset($m['name']) && !empty($m['name'])) ? '/'.$m['name'] : '');
							if(!empty($maps_med)) $m['media_path'] = $maps_med;
							if(!empty($maps_tem)) $m['template_path'] = $maps_tem;
							if(!empty($maps_err)) $m['error_template'] = $maps_err;
							if(!empty($maps_sup)) $m['template_super'] = $maps_sup;
							if(!empty($maps_mod)) $m['modules'] = array_merge($maps_mod,(isset($m['modules']) ? (is_array($m['modules']) ? $m['modules'] : array($m['modules'])) : array()));
							if(!empty($maps_arg)) $m['args'] = array_merge($maps_arg,(isset($m['args']) ? (is_array($m['args']) ? $m['args'] : array($m['args'])) : array()));
							$exp_patterns[$maps_url.$u] = $m;
						}
					}
				}else{
					$exp_patterns[$k] = $v;
				}
			}
			$map['patterns'] = $exp_patterns;
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
						$pkg_id++;
						$n = isset($v['name']) ? $v['name'] : $v['class'];
						$r = new \ReflectionClass(str_replace('.',"\\",$v['class']));
						$suffix = isset($v['suffix']) ? $v['suffix'] : '';
						$automaps = $methodmaps = array();
						foreach($r->getMethods() as $m){
							if($m->isPublic() && !$m->isStatic() && substr($m->getName(),0,1) != '_'){
								$automap = (boolean)preg_match('/@automap[\s]*/',$m->getDocComment());
								if(empty($automaps) || $automap){
									$url = $k.(($m->getName() == 'index') ? '' : (($k == '') ? '' : '/').$m->getName()).str_repeat('/(.+)',$m->getNumberOfRequiredParameters());
									$auto_anon = array();
									if($automap){
										if(preg_match('/@automap\s.*@(\[.*\])/',$m->getDocComment(),$a)){
											if(preg_match_all('/([\"\']).+?\\1/',$a[1],$dm)){
												foreach($dm[0] as $dv) $a[1] = str_replace($dv,str_replace(array('[',']'),array('#{#','#}#'),$dv),$a[1]);
											}
											$auto_anon = @eval('return '.str_replace(array('[',']','#{#','#}#'),array('array(',')','[',']'),$a[1]).';');
											if(!is_array($auto_anon)) throw new \InvalidArgumentException($r->getName().'::'.$m->getName().' automap annotation error');
										}
									}
									for($i=0;$i<=$m->getNumberOfParameters()-$m->getNumberOfRequiredParameters();$i++){
										$p = is_dir(substr($r->getFilename(),0,-4)) ? substr($r->getFilename(),0,-4) : dirname($r->getFilename());
										$mapvar = array_merge($v,array('name'=>$n.'/'.$m->getName(),'class'=>$v['class'],'method'=>$m->getName(),'num'=>$i,'@'=>$p,'pkg_id'=>$pkg_id));										
										if($automap){
											if(!empty($auto_anon)){
												$mapvar = array_merge($mapvar,$auto_anon);
												if(empty($suffix) && isset($mapvar['suffix'])) $suffix = $mapvar['suffix'];
											}
											$automaps[$url.$suffix] = $mapvar;
										}else{
											$methodmaps[$url.$suffix] = $mapvar;
										}
										$url .= '/(.+)';
									}
								}
							}
						}
						$apps = array_merge($apps,(empty($automaps) ? $methodmaps : $automaps));
						unset($automaps,$methodmaps);
					}catch(\ReflectionException $e){
						throw new \InvalidArgumentException($v['class'].' not found');
					}
				}else{
					$apps[(string)$k] = $v;
				}
			}
			list($url,$surl) = array($this->branch_url,str_replace('http://','https://',$this->branch_url));
			$map_secure = (isset($map['secure']) && $map['secure'] === true);
			$conf_secure = (\org\rhaco\Conf::get('secure',true) === true);

			foreach($apps as $u => $m){
				$m['secure'] = ($conf_secure && (((isset($m['secure']) && $m['secure'] === true)) || (!isset($m['secure']) && $map_secure)));
				$cnt = 0;
				$fu = \org\rhaco\net\Path::absolute(
						($m['secure'] ? $surl : $url)
						,(empty($u)) ? '' : substr(preg_replace_callback("/([^\\\\])(\(.*?[^\\\\]\))/",function($n){return $n[1].'%s';},' '.$u,-1,$cnt),1)
						);
				foreach(array('template_path','media_path') as $k){
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
				self::$output_maps[basename($this->entry_file())] = $apps;
				self::$get_maps = false;
				return;
			}
			foreach(array_keys($apps) as $k => $u) $urls[$u] = strlen(preg_replace("/[\W]/",'',$u));
			arsort($urls);
			krsort($urls);
			if(preg_match("/^\/".str_replace("/","\\/",$this->package_media_url)."\/(\d+)\/(.+)$/",$pathinfo,$m) && sizeof($urls) >= $m[1]){
				for(reset($urls),$i=0;$i<$m[1];$i++) next($urls);
				$v = $apps[key($urls)];
				if(isset($v['@'])) \org\rhaco\net\http\File::attach($v['@'].'/resources/media/'.$m[2]);
				\org\rhaco\net\http\Header::send_status(404);
				exit;
			}
			foreach($urls as $k => $null){
				if(preg_match("/^".(empty($k) ? '' : "\/").str_replace(array("\/",'/','@#S'),array('@#S',"\/","\/"),$k).'[\/]{0,1}$/',$pathinfo,$p)){
					if(isset($apps[$k]['mode']) && !empty($apps[$k]['mode'])){
						$mode = \org\rhaco\Conf::appmode();
						$mode_alias = \org\rhaco\Conf::get('mode');
						$bool = false;
						foreach(explode(',',$apps[$k]['mode']) as $m){
							foreach((
								(substr(trim($m),0,1) == '@' && isset($mode_alias[substr(trim($m),1)])) ? 
									explode(',',$mode_alias[substr(trim($m),1)]) : 
									array($m)
							) as $me){
								if($mode == trim($me)){
									$bool = true;
									break;
								}
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
							(
								!isset($_SERVER['HTTP_X_FORWARDED_HOST']) || 
								(isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] != 443) || 
								(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https')
							)
						){
							header('Location: '.preg_replace("/^.+(:\/\/.+)$/","https\\1",$current_url));
							exit;
						}
					}
					if(isset($apps[$k]['redirect'])){
						header('Location: '.\org\rhaco\net\Path::absolute($this->branch_url,$apps[$k]['redirect']));
						exit;
					}
					if(isset($map['modules'])){
						foreach((is_array($map['modules']) ? $map['modules'] : array($map['modules'])) as $m) $modules[] = $this->str_reflection($m);
					}
					if(isset($apps[$k]['modules'])){
						foreach((is_array($apps[$k]['modules']) ? $apps[$k]['modules'] : array($apps[$k]['modules'])) as $m) $modules[] = $this->str_reflection($m);
					}
					try{
						foreach($modules as $m) $this->set_object_module($m);
						if(isset($apps[$k]['class'])){
							if(!class_exists(str_replace('.',"\\",$apps[$k]['class']))) throw new \InvalidArgumentException($apps[$k]['class'].' not found');
							$obj = $this->str_reflection($apps[$k]['class']);
							$func_exception = null;
							
							if($obj instanceof \org\rhaco\Object){
								foreach($modules as $m) $obj->set_object_module($m);
							}
							if($obj instanceof \org\rhaco\flow\FlowInterface){
								$obj->set_select_map_name($apps[$k]['name']);
								$obj->set_maps($apps);
								$obj->set_args((isset($apps[$k]['args']) && is_array($apps[$k]['args'])) ? $apps[$k]['args'] : array());
								$ext_modules = $obj->get_template_modules();
								if(!empty($ext_modules)){
									if(!is_array($ext_modules)) $ext_modules = array($ext_modules);
									foreach($ext_modules as $o) $this->template->set_object_module($o);
								}
								$obj->before();
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
								$put_block = $obj->get_block();
							}
							if($func_exception instanceof \Exception) throw $func_exception;
						}
						if(isset($apps[$k]['post_after']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && !\org\rhaco\Exceptions::has()){
							$this->after_redirect($apps[$k]['post_after'],$apps[$k],$apps,$obj);
						}
						if(isset($apps[$k]['after']) && !\org\rhaco\Exceptions::has()){
							$this->after_redirect($apps[$k]['after'],$apps[$k],$apps,$obj);
						}
						if(isset($apps[$k]['template'])){
							$this->print_template($this->template_path,$apps[$k]['template'],$this->media_url,$put_block,$obj,$apps,$k);
						}else if(isset($apps[$k]['@']) && is_file($t = $apps[$k]['@'].'/resources/templates/'.$apps[$k]['method'].'.html')){
							$this->print_template(dirname($t).'/',basename($t),$this->branch_url.$this->package_media_url.'/'.$idx,$put_block,$obj,$apps,$k,false);
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
						if(($level = \org\rhaco\Conf::get('exception_log_level')) !== null && in_array($level,array('error','warn','info','debug'))){
							$es = ($e instanceof \org\rhaco\Exceptions) ? \org\rhaco\Exceptions::gets() : array($e);
							$ignore = \org\rhaco\Conf::get('exception_log_ignore');
							foreach($es as $ev){
								$in = true;
								if(!empty($ignore)){
									foreach((is_array($ignore) ? $ignore : array($ignore)) as $p){
										if(($in = !(preg_match('/'.str_replace('/','\\/',$p).'/',(string)$ev))) === false) break;
									}
								}
								if($in) \org\rhaco\Log::$level($ev);
							}
						}
						if($this->has_object_module('flow_handle_exception')){
							$this->object_module('flow_handle_exception',$e);
						}
						if(!($e instanceof \org\rhaco\Exceptions)){
							\org\rhaco\Exceptions::add($e);
						}
						if(isset($apps[$k]['error_status'])){
							\org\rhaco\net\http\Header::send_status($apps[$k]['error_status']);
						}else if(isset($map['error_status'])){
							\org\rhaco\net\http\Header::send_status($map['error_status']);
						}
						if($this->has_object_module('flow_exception_output')){
							/**
							 * 例外発生時の出力
							 * @param mixed $obj actionで返却された値
							 * @param Exception $e 発生した例外
							 */
							$this->object_module('flow_exception_output',$obj,$e);
							exit;
						}else if(isset($apps[$k]['error_redirect'])){
							$this->redirect($apps,$apps[$k]['error_redirect']);
						}else if(isset($map['error_redirect'])){
							$this->redirect($apps,$map['error_redirect']);
						}else if(isset($apps[$k]['error_template'])){
							$this->print_template($this->template_path,$apps[$k]['error_template'],$this->media_url,$put_block,$obj,$apps,$k);
						}else if(isset($map['error_template'])){
							$this->print_template($this->template_path,$map['error_template'],$this->media_url,$put_block,$obj,$apps,$k);
						}else if(isset($apps[$k]['@']) && is_file($t = $apps[$k]['@'].'/resources/templates/error.html')){
							$this->print_template(dirname($t).'/',basename($t),$this->branch_url.$this->package_media_url.'/'.$idx,$put_block,$obj,$apps,$k,false);
						}else if(isset($apps[$k]['template']) || (isset($apps[$k]['@']) && is_file($apps[$k]['@'].'/resources/templates/'.$apps[$k]['method'].'.html'))){
							if(!isset($map['error_status'])) \org\rhaco\net\http\Header::send_status(500);
							exit;
						}
						$xml = new \org\rhaco\Xml('error');
						foreach(\org\rhaco\Exceptions::gets() as $g => $e){
							$class_name = get_class($e);
							$message = new \org\rhaco\Xml('message',$e->getMessage());
							$message->add('group',$g);
							$message->add('type',basename(str_replace("\\",'/',$class_name)));
							$xml->add($message);
						}
						$xml->output();
					}
				}
				$idx++;
			}
		}
		if(isset($map['nomatch_redirect'])){
			$this->redirect($apps,$map['nomatch_redirect']);
		}
		if(($level = \org\rhaco\Conf::get('notfound_log_level')) !== null && in_array($level,array('error','warn','info','debug'))){
			\org\rhaco\Log::$level(\org\rhaco\Request::current_url().' (`'.$pathinfo.'`) bad request');
		}
		\org\rhaco\net\http\Header::send_status(404);
		exit;
	}
	private function redirect($apps,$url,$args=array()){
		if(strpos($url,'://') !== false) \org\rhaco\net\http\Header::redirect($url);
		foreach($apps as $m){
			if(isset($m['name']) && $m['name'] == $url){
				\org\rhaco\net\http\Header::redirect(empty($args) ? $m['format'] : vsprintf($m['format'],$args));
			}
		}
		throw new \InvalidArgumentException('map `'.$url.'` not found');
	}
	private function after_redirect($after,$pattern,$apps,$obj){
		$vars = array();
		foreach($obj as $k => $v) $vars[$k] = $v;
		if(isset($pattern['vars'])){
			foreach($pattern['vars'] as $k => $v) $vars[$k] = $v;
		}		
		if(is_array($after) && !isset($after[0])){
			$bool = false;
			foreach($after as $k => $a){
				if(array_key_exists($k,$vars)){
					$after = $a;
					$bool = true;
					break;
				}
			}
			if(!$bool){
				return;
			}
		}
		$name = is_string($after) ? $after : (is_array($after) ? array_shift($after) : null);
		$var_names = (!empty($after) && is_array($after)) ? $after : array();
		$args = array();
		if(!empty($var_names)){
			foreach($var_names as $n){
				if(!isset($vars[$n])) throw new \InvalidArgumentException('variable '.$n.' not found');
				$args[$n] = $vars[$n];
			}
		}
		if(isset($pattern['@'])){
			foreach($apps as $u => $m){
				if(isset($m['@']) && $m['pkg_id'] == $pattern['pkg_id'] && $name == $m['method'] && sizeof($args) == $m['num']){
					$name = $m['name'];
					break;
				}
			}
		}		
		if(empty($name)) \org\rhaco\net\http\Header::redirect_referer();
		$this->redirect($apps,$name,$args);
	}
	private function print_template($template_path,$template,$media_url,$put_block,$obj,$apps,$index,$path_replace=true){
		if($path_replace){
			if(isset($apps[$index]['media_path'])) $media_url = $media_url.\org\rhaco\net\Path::slash($apps[$index]['media_path'],true,false);
			if(isset($apps[$index]['template_path'])) $template_path = $template_path.\org\rhaco\net\Path::slash($apps[$index]['template_path'],false,true);
		}
		if(!empty($put_block)) $this->template->put_block(\org\rhaco\net\Path::absolute($this->template_path,$put_block));
		if(isset($apps[$index]['template_super'])) $this->template->template_super($this->template_path.$apps[$index]['template_super']);
		if(is_array($obj) && isset($obj[0]) && isset($obj[1])){
			foreach(((is_array($obj[1])) ? $obj[1] : array($obj[1])) as $o) $this->template->set_object_module($o);
			$obj = $obj[0];
		}
		$this->template->media_url($media_url);
		$this->template->cp($obj);
		if(isset($apps[$index]['vars'])) $this->template->cp($apps[$index]['vars']);
		$this->template->vars('t',new \org\rhaco\flow\module\Helper($this->app_url,$media_url,$apps[$index]['name'],$apps[$index]['num'],$this->entry_file(),$apps,$obj));
		$src = $this->template->read(\org\rhaco\net\Path::absolute($template_path,$template));
		/**
		 * テンプレートの出力
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('before_flow_print_template',\org\rhaco\lang\String::ref($obj,$src));
		$src = (string)$obj;
		header('Content-Length: '.strlen($src));
		print($src);
		exit;
	}
	private function str_reflection($package){
		if(is_object($package)) return $package;
		$class_name = substr($package,strrpos($package,'.')+1);
		try{
			$r = new \ReflectionClass("\\".str_replace('.',"\\",$package));
			return $r->newInstance();
		}catch(\ReflectionException $e){
			if(!empty($class_name)){
				try{
					$r = new \ReflectionClass($class_name);
					return $r->newInstance();
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
	private function object_module($n){
		$r = null;
		$a = func_get_args();
		array_shift($a);
		foreach($this->module as $o){
			if(method_exists($o,$n)) $r = call_user_func_array(array($o,$n),$a);
		}
		return $r;		
	}
	/**
	 * 出力する
	 * @param array $maps
	 */
	static public function out($maps){
		$self = new self();
		$self->output($maps);
	}
}