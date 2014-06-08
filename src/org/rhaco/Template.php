<?php
namespace org\rhaco;
/**
 * テンプレートを処理する
 * @author tokushima
 * @var mixed{} $vars バインドされる変数
 * @var boolean $secure https://をhttp://に置換するか
 * @var string $put_block ブロックファイル
 * @var string $template_super 継承元テンプレート
 * @var string $media_url メディアファイルへのURLの基点
 * @conf boolean $display_exception 例外が発生した場合にメッセージを表示するか
 */
class Template extends \org\rhaco\TemplateVariable{
	private $file;
	private $selected_template;
	private $selected_src;

	protected $secure = false;
	protected $vars = array();
	protected $put_block;
	protected $template_super;
	protected $media_url;

	protected function __new__($media_url=null){
		if($media_url !== null) $this->media_url($media_url);
	}
	/**
	 * 配列からテンプレート変数に値をセットする
	 * @param array $array
	 */
	final public function cp($array){
		if(is_array($array) || is_object($array)){
			foreach($array as $k => $v) $this->vars[$k] = $v;
		}else{
			throw new \InvalidArgumentException('must be an of array');
		}
		return $this;
	}
	/**
	 * メディアファイルへのURLの基点を設定
	 * @param string $url
	 * @return $this
	 */
	protected function __set_media_url__($url){
		$this->media_url = str_replace("\\",'/',$url);
		if(!empty($this->media_url) && substr($this->media_url,-1) !== '/') $this->media_url = $this->media_url.'/';
	}
	/**
	 * 出力する
	 * @param string $file
	 * @param string $template_name
	 */
	final public function output($file,$template_name=null){
		print($this->read($file,$template_name));
		exit;
	}
	/**
	 * ファイルを読み込んで結果を返す
	 * @param string $file
	 * @param string $template_name
	 * @return string
	 */
	final public function read($file,$template_name=null){
		if(!is_file($file) && strpos($file,'://') === false) throw new \InvalidArgumentException($file.' not found');
		$this->file = $file;
		$cname = md5($this->template_super.$this->put_block.$this->file.$this->selected_template);
		/**
		 * キャッシュのチェック
		 * @param string $cname キャッシュ名
		 * @return boolean
		 */
		if(!static::has_module('has_template_cache') || static::module('has_template_cache',$cname) !== true){
			if(!empty($this->put_block)){
				$src = $this->read_src($this->put_block);
				if(strpos($src,'rt:extends') !== false){
					Xml::set($x,'<:>'.$src.'</:>');
					foreach($x->in('rt:extends') as $ext) $src = str_replace($ext->plain(),'',$src);
				}
				$src = sprintf('<rt:extends href="%s" />\n',$file).$src;
				$this->file = $this->put_block;
			}else{
				$src = $this->read_src($this->file);
			}
			$src = $this->replace($src,$template_name);
			/**
			 * キャッシュにセットする
			 * @param string $cname キャッシュ名
			 * @param string $src 作成されたテンプレート
			 */
			static::module('set_template_cache',$cname,$src);
		}else{
			/**
			 * キャッシュから取得する
			 * @param string $cname キャッシュ名
			 * @return string
			 */
			$src = static::module('get_template_cache',$cname);
		}
		return $this->execute($src);
	}
	private function cname(){
		return md5($this->put_block.$this->file.$this->selected_template);
	}
	/**
	 * 文字列から結果を返す
	 * @param string $src
	 * @param string $template_name
	 * @return string
	 */
	final public function get($src,$template_name=null){
		return $this->execute($this->replace($src,$template_name));
	}
	private function execute($src){
		$src = $this->exec($src);
		$src = str_replace(array('#PS#','#PE#'),array('<?','?>'),$this->html_reform($src));
		return $src;
	}
	private function replace($src,$template_name){
		$this->selected_template = $template_name;
		$src = preg_replace("/([\w])\->/","\\1__PHP_ARROW__",$src);
		$src = str_replace(array("\\\\","\\\"","\\'"),array('__ESC_DESC__','__ESC_DQ__','__ESC_SQ__'),$src);
		$src = $this->replace_xtag($src);
		/**
		 * テンプレート作成の初期化
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('init_template',\org\rhaco\lang\String::ref($obj,$src));
		$src = $this->rtcomment($this->rtblock($this->rttemplate((string)$obj),$this->file));
		$this->selected_src = $src;
		/**
		 * テンプレート作成の前処理
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('before_template',\org\rhaco\lang\String::ref($obj,$src));
		$src = $this->rtif($this->rtloop($this->html_form($this->html_list((string)$obj))));
		/**
		 * テンプレート作成の後処理
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('after_template',\org\rhaco\lang\String::ref($obj,$src));
		$src = str_replace('__PHP_ARROW__','->',(string)$obj);
		$src = $this->parse_print_variable($src);
		$php = array(' ?>','<?php ','->');
		$str = array('__PHP_TAG_END__','__PHP_TAG_START__','__PHP_ARROW__');
		$src = str_replace($php,$str,$src);
		if($bool = $this->html_script_search($src,$keys,$tags)) $src = str_replace($tags,$keys,$src);
		$src = $this->parse_url($src,$this->media_url);
		if($bool) $src = str_replace($keys,$tags,$src);
		$src = str_replace($str,$php,$src);
		$src = str_replace(array('__ESC_DQ__','__ESC_SQ__','__ESC_DESC__'),array("\\\"","\\'","\\\\"),$src);
		return $src;		
	}
	private function exec($_src_){
		/**
		 * 実行前処理
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('before_exec_template',\org\rhaco\lang\String::ref($_obj_,$_src_));
		foreach($this->default_vars() as $k => $v){
			$this->vars($k,$v);
		}
		ob_start();
			if(is_array($this->vars) && !empty($this->vars)) extract($this->vars);
			eval('?><?php $_display_exception_='.((\org\rhaco\Conf::get('display_exception') === true) ? 'true' : 'false').'; ?>'.((string)$_obj_));
		$_eval_src_ = ob_get_clean();

		if(strpos($_eval_src_,'Parse error: ') !== false){
			if(preg_match("/Parse error\:(.+?) in .+eval\(\)\'d code on line (\d+)/",$_eval_src_,$match)){
				list($msg,$line) = array(trim($match[1]),((int)$match[2]));
				$lines = explode("\n",$_src_);
				$plrp = substr_count(implode("\n",array_slice($lines,0,$line)),"<?php 'PLRP'; ?>\n");
				\org\rhaco\Log::error($msg.' on line '.($line-$plrp).' [compile]: '.trim($lines[$line-1]));

				$lines = explode("\n",$this->selected_src);
				\org\rhaco\Log::error($msg.' on line '.($line-$plrp).' [plain]: '.trim($lines[$line-1-$plrp]));
				if(\org\rhaco\Conf::get('display_exception') === true) $_eval_src_ = $msg.' on line '.($line-$plrp).': '.trim($lines[$line-1-$plrp]);
			}
		}
		$_src_ = $this->selected_src = null;
		/**
		 * 実行後処理
		 * @param org.rhaco.lang.String $obj
		 */
		$this->object_module('after_exec_template',\org\rhaco\lang\String::ref($_obj_,$_eval_src_));
		return (string)$_obj_;
	}
	private function error_handler($errno,$errstr,$errfile,$errline){
		throw new \ErrorException($errstr,0,$errno,$errfile,$errline);
	}
	private function replace_xtag($src){
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
			foreach($null[0] as $value) $src = str_replace($value,'#PS#'.substr($value,2,-2).'#PE#',$src);
		}
		return $src;
	}
	private function parse_url($src,$media){
		if(!empty($media) && substr($media,-1) !== '/') $media = $media.'/';
		$secure_base = ($this->secure) ? str_replace('http://','https://',$media) : null;
		if(preg_match_all("/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",$src,$match)){
			foreach($match[2] as $k => $p){
				$t = null;
				if(strtolower($p) === 'href') list($t) = (preg_split("/[\s]/",strtolower($match[1][$k])));
				$src = $this->replace_parse_url($src,(($this->secure && $t !== 'a') ? $secure_base : $media),$match[0][$k],$match[4][$k]);
			}
		}
		if(preg_match_all("/[^:]:[\040]*url\(([^\n]+?)\)/",$src,$match)){
			if($this->secure) $media = $secure_base;
			foreach($match[1] as $key => $param) $src = $this->replace_parse_url($src,$media,$match[0][$key],$match[1][$key]);
		}
		return $src;
	}
	private function replace_parse_url($src,$base,$dep,$rep){
		if(!preg_match("/(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\{\\$)|(^\w+:)|(^[#\?])/",$rep)){
			$src = str_replace($dep,str_replace($rep,$this->ab_path($base,$rep),$dep),$src);
		}
		return $src;
	}
	private function ab_path($a,$b){
		if($b === '' || $b === null) return $a;
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
	private function read_src($filename){
		$src = file_get_contents($filename);
		return (preg_match('/^http[s]*\:\/\//',$filename)) ? $this->parse_url($src,dirname($filename)) : $src;
	}
	private function rttemplate($src){
		$values = array();
		$bool = false;
		while(Xml::set($tag,$src,'rt:template')){
			$src = str_replace($tag->plain(),'',$src);
			$values[$tag->in_attr('name')] = $tag->value();
			$src = str_replace($tag->plain(),'',$src);
			$bool = true;
		}
		if(!empty($this->selected_template)){
			if(!array_key_exists($this->selected_template,$values)) throw new \LogicException('undef rt:template '.$this->selected_template);
			return $values[$this->selected_template];
		}
		return ($bool) ? implode($values) : $src;
	}
	private function rtblock($src,$filename){
		if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
			$base_filename = $filename;
			$blocks = $paths = array();
			while(Xml::set($e,'<:>'.$this->rtcomment($src).'</:>','rt:extends') !== false){
				$href = $this->ab_path(str_replace("\\",'/',dirname($filename)),$e->in_attr('href'));
				if(!$e->is_attr('href') || !is_file($href)) throw new \LogicException('href not found '.$filename);
				if($filename === $href) throw new \LogicException('Infinite Recursion Error'.$filename);
				Xml::set($bx,'<:>'.$this->rtcomment($src).'</:>',':');
				foreach($bx->in('rt:block') as $b){
					$n = $b->in_attr('name');
					if(!empty($n) && !array_key_exists($n,$blocks)){
						$blocks[$n] = $b->value();
						$paths[$n] = $filename;
					}
				}
				$src = $this->rttemplate($this->replace_xtag($this->read_src($filename = $href)));
				$this->selected_template = $e->in_attr('name');
			}
			/**
			 * ブロック展開の前処理
			 * @param org.rhaco.lang.String $obj
			 */
			$this->object_module('before_block_template',\org\rhaco\lang\String::ref($obj,$src));
			$src = (string)$obj;
			if(empty($blocks)){
				if(Xml::set($bx,'<:>'.$src.'</:>')){
					foreach($bx->in('rt:block') as $b) $src = str_replace($b->plain(),$b->value(),$src);
				}
			}else{
				if(!empty($this->template_super)) $src = $this->read_src($this->ab_path(str_replace("\\",'/',dirname($base_filename)),$this->template_super));
				while(Xml::set($b,$src,'rt:block')){
					$n = $b->in_attr('name');
					$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
				}
			}
			$this->file = $filename;
		}
		return $src;
	}
	private function rtcomment($src){
		while(Xml::set($tag,$src,'rt:comment')) $src = str_replace($tag->plain(),'',$src);
		return $src;
	}
	private function rtloop($src){
		if(strpos($src,'rt:loop') !== false){
			while(Xml::set($tag,$src,'rt:loop')){
				$tag->escape(false);
				$value = $tag->value();		

				while(Xml::set($subtag,$value,'rt:loop')){
					$value = $this->rtloop($value);
				}
				$uniq = uniqid('');
				$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
				$varname = '$_'.$uniq;
				$var = '$'.$tag->in_attr('var','_v_'.$uniq);
				$key = '$'.$tag->in_attr('key','_k_'.$uniq);
				$counter = '$'.$tag->in_attr('counter','_c_'.$uniq);
				$evenodd = '$'.$tag->in_attr('evenodd','loop_evenodd');
				$even = $tag->in_attr('even_value','even');
				$odd = $tag->in_attr('odd_value','odd');
				
				$src = $this->php_exception_catch(str_replace(
							$tag->plain(),
							sprintf('<?php '
										.' %s=%s; '
										.' %s = 0; '
										.' foreach(%s as %s => %s){'
											.' %s++; '
											.' %s=((%s %% 2) === 0) ? \'%s\' : \'%s\';'
									.' ?>'
											.'%s'
									.'<?php '
										.' } '
									.' ?>'
										,$varname,$param
										,$counter
										,$varname,$key,$var
											,$counter
											,$evenodd,$counter,$even,$odd
										,$value
							)
							,$src
						));
			}
		}
		return $src;
	}
	private function rtif($src){
		foreach(array('rt:if','rt:notif') as $k => $rttag){
			if(strpos($src,$rttag) !== false){
				$not = ($k === 1) ? '!' : '';
				
				while(\org\rhaco\Xml::set($tag,$src,$rttag)){
					$tag->escape(false);
					if(!$tag->is_attr('param')) throw new \LogicException('if');
					$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));
	
					if($tag->is_attr('value')){
						$arg2 = $this->parse_plain_variable($tag->in_attr('value'));
						if($arg2 == 'true' || $arg2 == 'false' || preg_match('/^-?[0-9]+$/',(string)$arg2)){
							$cond = sprintf('<?php if(%s(%s === %s || %s === "%s")){ ?>',$not,$arg1,$arg2,$arg1,$arg2);
						}else{
							if($arg2 === '' || $arg2[0] != '$') $arg2 = '"'.$arg2.'"';
							$cond = sprintf('<?php if(%s(%s === %s)){ ?>',$not,$arg1,$arg2);
						}
					}else{
						$uniq = uniqid('$I');
						$cond = sprintf(
									'<?php try{ '
										.' %s=%s; '
									.'}catch(\Exception $e){ %s=null; } ?>'
									.'<?php if(%s(%s !== null && %s !== false && ( (!is_string(%s) && !is_array(%s)) || (is_string(%s) && %s !== "") || (is_array(%s) && !empty(%s)) ) )){ ?>'
											,$uniq,$arg1
											,$uniq
											,$not,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq
								);
					}
					$src = str_replace(
								$tag->plain(),
								$this->php_exception_catch($cond.preg_replace('/<rt\:else[\s]*.*?>/i','<?php }else{ ?>',$tag->value()).'<?php } ?>')
								,$src
							);
				}
			}
		}
		return $src;
	}
	private function html_script_search($src,&$keys,&$tags){
		$keys = $tags = array();
		$uniq = uniqid('uniq');		
		$i = 0;
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in('script') as $obj){
			if(!$obj->is_attr('src')){
				$keys[] = '__'.$uniq.($i++).'__';
				$tags[] = $obj->plain();
			}
		}
		return ($i > 0);
	}
	private function html_reform($src){
		if(strpos($src,'rt:aref') !== false){
			Xml::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in('form') as $obj){
				if($obj->is_attr('rt:aref')){
					$bool = ($obj->in_attr('rt:aref') === 'true');
					$obj->rm_attr('rt:aref');
					$obj->escape(false);
					$value = $obj->get();

					if($bool){
						foreach($obj->in(array('input','select','textarea')) as $tag){
							if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
								switch(strtolower($tag->in_attr('type','text'))){
									case 'button':
									case 'submit':
									case 'file':
										break;
									default:
										$tag->attr('rt:ref','true');
										$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
								}
							}
						}
						$value = $this->exec($this->parse_print_variable($this->html_input($obj->get())));
					}
					$src = str_replace($obj->plain(),$value,$src);
				}
			}
		}
		return $src;
	}
	private function html_form($src){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in('form') as $obj){
			if($this->is_reference($obj)){
				if($obj->is_attr('rt:param')){
					$param = $this->variable_string($this->parse_plain_variable($obj->in_attr('rt:param')));
					$uniq = uniqid('');
					$var = '$__form_var__'.$uniq;
					$k = '$__form_k__'.$uniq;
					$v = '$__form_v__'.$uniq;
					$tag = sprintf('<?php try{ ?>'
							.'<?php '
								.'%s=%s; '
								.'if( isset(%s) && ( is_array(%s) || (is_object(%s) && %s instanceof \Traversable) ) ){ '
									.'foreach(%s as %s => %s){ '
										.'if(!isset($%s) && preg_match(\'/^[a-zA-Z0-9_]+$/\',%s)){ $%s = %s; }'
									.'}'
								.'} '
							.' ?>'
							.'<?php }catch(\Exception $e){ if(!isset($_nes_) && $_display_exception_){ $_t_->print_variable($e->getMessage());} } ?>'.PHP_EOL
							,$var,$param
							,$var,$var,$var,$var
							,$var,$k,$v
							,$k,$k,$k,$v
					);
					$obj->rm_attr('rt:param');
					$obj->value($tag.$obj->value());
				}
				$obj->escape(false);
				foreach($obj->in(array('input','select','textarea')) as $tag){
					if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
						switch(strtolower($tag->in_attr('type','text'))){
							case 'button':
							case 'submit':
								break;
							case 'file':
								$obj->attr('enctype','multipart/form-data');
								$obj->attr('method','post');
								break;
							default:
								$tag->attr('rt:ref','true');
								$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
						}
					}
				}
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}
		}
		return $this->html_input($src);
	}
	private function no_exception_str($value){
		return '<?php $_nes_=1; ?>'.$value.'<?php $_nes_=null; ?>';
	}
	private function html_input($src){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in(array('input','textarea','select')) as $obj){
			if('' != ($originalName = $obj->in_attr('name',$obj->in_attr('id','')))){
				$obj->escape(false);
				$type = strtolower($obj->in_attr('type','text'));
				$name = $this->parse_plain_variable($this->form_variable_name($originalName));
				$lname = strtolower($obj->name());
				$change = false;
				$uid = uniqid();

				if(substr($originalName,-2) !== '[]'){
					if($type == 'checkbox'){
						if($obj->in_attr('rt:multiple','true') === 'true') $obj->attr('name',$originalName.'[]');
						$obj->rm_attr('rt:multiple');
						$change = true;
					}else if($obj->is_attr('multiple') || $obj->in_attr('multiple') === 'multiple'){
						$obj->attr('name',$originalName.'[]');
						$obj->rm_attr('multiple');
						$obj->attr('multiple','multiple');
						$change = true;
					}
				}else if($obj->in_attr('name') !== $originalName){
					$obj->attr('name',$originalName);
					$change = true;
				}
				if($obj->is_attr('rt:param')){
					switch($lname){
						case 'select':
							$value = sprintf('<rt:loop param="%s" var="%s" key="%s">'
											.'<option value="{$%s}">{$%s}</option>'
											.'</rt:loop>'
											,$obj->in_attr('rt:param'),$obj->in_attr('rt:var','loop_var'.$uid),$obj->in_attr('rt:key','loop_key'.$uid)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:var','loop_var'.$uid)
							);
							$obj->value($this->rtloop($value));
							if($obj->is_attr('rt:null')) $obj->value('<option value="">'.$obj->in_attr('rt:null').'</option>'.$obj->value());
					}
					$obj->rm_attr('rt:param','rt:key','rt:var');
					$change = true;
				}
				if($obj->is_attr('rt:ref')){
					if($this->is_reference($obj)){
						switch($lname){
							case 'textarea':
								$obj->value($this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',((preg_match("/^{\$(.+)}$/",$originalName,$match)) ? '{$$'.$match[1].'}' : '{$'.$originalName.'}'))));
								break;
							case 'select':
								$select = $obj->value();
								foreach($obj->in('option') as $option){
									$option->escape(false);
									$value = $this->parse_plain_variable($option->in_attr('value'));
									if(empty($value) || $value[0] != '$') $value = sprintf("'%s'",$value);
									$option->rm_attr('selected');
									$option->plain_attr($this->check_selected($name,$value,'selected'));
									$select = str_replace($option->plain(),$option->get(),$select);
								}
								$obj->value($select);
								break;
							case 'input':
								switch($type){
									case 'checkbox':
									case 'radio':
										$value = $this->parse_plain_variable($obj->in_attr('value','true'));
										$value = (substr($value,0,1) != '$') ? sprintf("'%s'",$value) : $value;
										$obj->rm_attr('checked');
										$obj->plain_attr($this->check_selected($name,$value,'checked'));
										break;
									case 'text':
									case 'hidden':
									case 'password':
									case 'search':
									case 'url':
									case 'email':
									case 'tel':
									case 'datetime':
									case 'date':
									case 'month':
									case 'week':
									case 'time':
									case 'datetime-local':
									case 'number':
									case 'range':
									case 'color':
										$obj->attr('value',$this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',
																	((preg_match("/^\{\$(.+)\}$/",$originalName,$match)) ?
																		'{$$'.$match[1].'}' :
																		'{$'.$originalName.'}'))));
										break;
								}
								break;
						}
					}
					$change = true;
				}
				if($change){
					switch($lname){
						case 'textarea':
						case 'select':
							$obj->close_empty(false);
					}
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
		}
		return $src;

	}
	private function check_selected($name,$value,$selected){
		return sprintf('<?php if('
					.'isset(%s) && (%s === %s '
										.' || (!is_array(%s) && ctype_digit((string)%s) && (string)%s === (string)%s)'
										.' || ((%s === "true" || %s === "false") ? (%s === (%s == "true")) : false)'
										.' || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? array() : array(%s))),true) '
									.') '
					.'){ print(" %s=\"%s\""); } ?>' // !escape
					,$name,$name,$value
					,$name,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
					,$selected,$selected
				);
	}
	private function html_list($src){
		if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
			$tags = array();
			foreach($m[1] as $k => $v){
				if(Xml::set($tag,substr($src,$v[1]-1),$v[0])) $tags[] = $tag;
			}
			foreach($tags as $obj){
				$obj->escape(false);
				$name = strtolower($obj->name());
				$param = $obj->in_attr('rt:param');
				$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" '
									.'key="%s" '
									.'evenodd="%s" even_value="%s" odd_value="%s" '
									.'>'
								,$param,$obj->in_attr('rt:var','loop_var'),$obj->in_attr('rt:counter','loop_counter')
								,$obj->in_attr('rt:key','loop_key')
								,$obj->in_attr('rt:evenodd','loop_evenodd'),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
							);
				$rawvalue = $obj->value();
				if($name == 'table' && Xml::set($t,$rawvalue,'tbody')){
					$t->escape(false);
					$t->value($value.$this->table_tr_even_odd($t->value(),(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>');
					$value = str_replace($t->plain(),$t->get(),$rawvalue);
				}else{
					$value = $value.$this->table_tr_even_odd($rawvalue,(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>';
				}
				$obj->value($this->html_list($value));
				$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:evenodd','rt:even_value','rt:odd_value');
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}
		}
		return $src;
	}
	private function table_tr_even_odd($src,$name,$even_odd){
		Xml::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in($name) as $tr){
			$tr->escape(false);
			$class = ' '.$tr->in_attr('class').' ';
			if(preg_match('/[\s](even|odd)[\s]/',$class,$match)){
				$tr->attr('class',trim(str_replace($match[0],' {$'.$even_odd.'} ',$class)));
				$src = str_replace($tr->plain(),$tr->get(),$src);
			}
		}
		return $src;
	}
	private function form_variable_name($name){
		return (strpos($name,'[') && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$match)) ?
			'{$'.$match[1].'["'.$match[2].'"]'.'}' : '{$'.$name.'}';
	}
	private function is_reference($tag){
		$bool = ($tag->in_attr('rt:ref') === 'true');
		$tag->rm_attr('rt:ref');
		return $bool;
	}
}
