<?php
/**
 * テンプレートを処理する
 * @author tokushima
 */
class Template{
	private $module = array();
	private $exception_str;
	private $file;
	private $media_url;
	private $put_block;
	private $secure = false;
	private $selected_template;
	private $vars = array();

	public function __construct($media_url=null){
		if(!class_exists('XmlObject')) throw new RuntimeException('XmlObject not found');
		if($media_url !== null) $this->media_url($media_url);
	}
	/**
	 * 配列からテンプレート変数に値をセットする
	 * @param array $array
	 */
	final public function cp($array){
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
	/**
	 * テンプレート変数に値をセットする
	 * @param string $k 変数名
	 * @param mixed $v 値
	 */
	final public function vars($k,$v){
		$this->vars[$k] = $v;
	}
	/**
	 * セットしたテンプレート変数を取り除く
	 * @param string 変数名
	 */
	final public function rm_vars(){
		if(func_num_args() === 0){
			$this->vars = array();
		}else{
			foreach(func_get_args() as $n) unset($this->vars[$n]);
		}
	}
	/**
	 * Exceptionが発生した場所に表示される文字列を設定
	 * @param string $str
	 */
	final public function exception_str($str){
		$this->exception_str = $str;
	}
	/**
	 * https://をhttp://に置換するか
	 * @param boolean $bool
	 */
	final public function secure($bool){
		$this->secure = (boolean)$bool;
	}
	/**
	 * ブロックファイルを指定する
	 * @param string $file
	 */
	final public function put_block($file){
		if(!is_file($file)) throw new InvalidArgumentException($file.' not found');
		$this->put_block = $file;
	}
	/**
	 * メディアファイルへのURLの基点を設定／取得
	 * @param string $url
	 * @return string
	 */
	final public function media_url(){
		if(func_num_args() > 0){
			$this->media_url = str_replace("\\",'/',(string)func_get_arg(0));
			if(!empty($this->media_url) && substr($this->media_url,-1) !== '/') $this->media_url = $this->media_url.'/';
		}
		return $this->media_url;
	}
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
		if(!is_file($file)) throw new InvalidArgumentException($file.' not found');
		$this->file = $file;
		$cname = md5($this->put_block.$this->file.$this->selected_template);
		
		if(!$this->has_object_module('has_template_cahce') || $this->object_module('has_template_cahce',$cname) !== true){
			if(!empty($this->put_block)){
				$src = file_get_contents($this->put_block);
				if(strpos($src,'rt:extends') !== false){
					XmlObject::set($x,'<:>'.$src.'</:>');
					foreach($x->in('rt:extends') as $ext) $src = str_replace($ext->plain(),'',$src);
				}
				$src = sprintf('<rt:extends href="%s" />\n',$file).$src;
			}else{
				$src = file_get_contents($this->file);
			}
			$src = $this->replace($src,$template_name);
			$this->object_module('set_template_cahce',$cname,$src);
		}else{
			$src = $this->object_module('get_template_cahce',$cname);
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
		$this->object_module('init_template',$src);
		$src = $this->rtcomment($this->rtblock($this->rttemplate($src),$this->file));
		$this->object_module('before_template',$src);
		$src = $this->rtif($this->rtloop($this->rtunit($this->html_form($this->html_list($src)))));
		$this->object_module('after_template',$src);
		$src = str_replace('__PHP_ARROW__','->',$src);
		$src = $this->parse_print_variable($src);

		$php = array(' ?>','<?php ','->');
		$str = array('PHP_TAG_END','PHP_TAG_START','PHP_ARROW');
		$src = str_replace($php,$str,$src);		
		$media = $this->media_url;
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
		$src = str_replace($str,$php,$src);
		$src = str_replace(array('__ESC_DQ__','__ESC_SQ__','__ESC_DESC__'),array("\\\"","\\'","\\\\"),$src);
		return $src;
	}
	private function exec($src){
		$this->object_module('before_exec_template',$src);
		$this->vars('_t_',new TemplateHelper());
		$__template_eval_src__ = $src;
		ob_start();
			set_error_handler(array($this,'error_handler'));
			if(is_array($this->vars) && !empty($this->vars)) extract($this->vars);
			eval('?>'.$__template_eval_src__);
			unset($__template_eval_src__);
			restore_error_handler();
		$src = ob_get_clean();
		$this->object_module('after_exec_template',$src);
		return $src;
	}
	private function error_handler($errno,$errstr,$errfile,$errline){
		throw new ErrorException($errstr,0,$errno,$errfile,$errline);
	}	
	private function replace_xtag($src){
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
			foreach($null[0] as $value) $src = str_replace($value,'#PS#'.substr($value,2,-2).'#PE#',$src);
		}
		return $src;
	}
	private function replace_parse_url($src,$base,$dep,$rep){
		if(!preg_match("/(^[\w]+:\/\/)|(^PHP_TAG_START)|(^\{\\$)|(^\w+:)|(^[#\?])/",$rep)){
			$src = str_replace($dep,str_replace($rep,$this->ab_path($base,$rep),$dep),$src);
		}
		return $src;
	}
	private function ab_path($a,$b){
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
	private function rttemplate($src){
		$values = array();
		$bool = false;
		while(XmlObject::set($tag,$src,'rt:template')){
			$src = str_replace($tag->plain(),'',$src);
			$values[$tag->in_attr('name')] = $tag->value();
			$src = str_replace($tag->plain(),'',$src);
			$bool = true;
		}
		if(!empty($this->selected_template)){
			if(!array_key_exists($this->selected_template,$values)) throw new LogicException('undef rt:template '.$this->selected_template);
			return $values[$this->selected_template];
		}
		return ($bool) ? implode($values) : $src;
	}
	private function rtblock($src,$filename){
		if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
			$blocks = $paths = array();
			while(XmlObject::set($e,'<:>'.$this->rtcomment($src).'</:>','rt:extends') !== false){
				$href = $this->ab_path(dirname($filename),$e->in_attr('href'));
				if(!$e->is_attr('href') || !is_file($href)) throw new LogicException('href not found '.$filename);				
				if($filename === $href) throw new LogicException('Infinite Recursion Error'.$filename);
				XmlObject::set($bx,'<:>'.$this->rtcomment($src).'</:>',':');
				foreach($bx->in('rt:block') as $b){
					$n = $b->in_attr('name');
					if(!empty($n) && !array_key_exists($n,$blocks)){
						$blocks[$n] = $b->value();
						$paths[$n] = $filename;
					}
				}
				$src = $this->rttemplate($this->replace_xtag(file_get_contents($filename = $href)));
				$this->selected_template = $e->in_attr('name');
			}
			if(empty($blocks)){
				if(XmlObject::set($bx,'<:>'.$src.'</:>')){
					foreach($bx->in('rt:block') as $b) $src = str_replace($b->plain(),$b->value(),$src);
				}
			}else{
				while(XmlObject::set($b,$src,'rt:block')){
					$n = $b->in_attr('name');
					$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
				}
			}
			$this->file = $filename;
		}
		return $src;
	}
	private function rtcomment($src){
		while(XmlObject::set($tag,$src,'rt:comment')) $src = str_replace($tag->plain(),'',$src);
		return $src;
	}
	private function rtunit($src){
		if(strpos($src,'rt:unit') !== false){
			while(XmlObject::set($tag,$src,'rt:unit')){
				$uniq = uniqid('');
				$param = $tag->in_attr('param');
				$var = '$'.$tag->in_attr('var','_var_'.$uniq);
				$offset = $tag->in_attr('offset',1);
				$total = $tag->in_attr('total','_total_'.$uniq);
				$cols = ($tag->is_attr('cols')) ? (ctype_digit($tag->in_attr('cols')) ? $tag->in_attr('cols') : $this->variable_string($this->parse_plain_variable($tag->in_attr('cols')))) : 1;
				$rows = ($tag->is_attr('rows')) ? (ctype_digit($tag->in_attr('rows')) ? $tag->in_attr('rows') : $this->variable_string($this->parse_plain_variable($tag->in_attr('rows')))) : 0;
				$value = $tag->value();

				$cols_count = '$_ucount_'.$uniq;
				$cols_total = '$'.$tag->in_attr('cols_total','_cols_total_'.$uniq);
				$rows_count = '$'.$tag->in_attr('counter','_counter_'.$uniq);
				$rows_total = '$'.$tag->in_attr('rows_total','_rows_total_'.$uniq);
				$ucols = '$_ucols_'.$uniq;
				$urows = '$_urows_'.$uniq;
				$ulimit = '$_ulimit_'.$uniq;
				$ufirst = '$_ufirst_'.$uniq;				
				$ufirstnm = '_ufirstnm_'.$uniq;

				$ukey = '_ukey_'.$uniq;
				$uvar = '_uvar_'.$uniq;

				$src = str_replace(
							$tag->plain(),
							sprintf('<?php %s=%s; %s=%s; %s=%s=1; %s=null; %s=%s*%s; %s=array(); ?>'
									.'<rt:loop param="%s" var="%s" key="%s" total="%s" offset="%s" first="%s">'
										.'<?php if(%s <= %s){ %s[$%s]=$%s; } ?>'
										.'<rt:first><?php %s=$%s; ?></rt:first>'
										.'<rt:last><?php %s=%s; ?></rt:last>'
										.'<?php if(%s===%s){ ?>'
											.'<?php if(isset(%s)){ $%s=""; } ?>'
											.'<?php %s=sizeof(%s); ?>'
											.'<?php %s=ceil($%s/%s); ?>'
											.'%s'
											.'<?php %s=array(); %s=null; %s=1; %s++; ?>'
										.'<?php }else{ %s++; } ?>'
									.'</rt:loop>'
									,$ucols,$cols,$urows,$rows,$cols_count,$rows_count,$ufirst,$ulimit,$ucols,$urows,$var
									,$param,$uvar,$ukey,$total,$offset,$ufirstnm
										,$cols_count,$ucols,$var,$ukey,$uvar
										,$ufirst,$ufirstnm
										,$cols_count,$ucols
										,$cols_count,$ucols
											,$ufirst,$ufirstnm
											,$cols_total,$var
											,$rows_total,$total,$ucols
											,$value
											,$var,$ufirst,$cols_count,$rows_count
										,$cols_count
							)
							.($tag->is_attr('rows') ? 
								sprintf('<?php for(;%s<=%s;%s++){ %s=array(); ?>%s<?php } ?>',$rows_count,$rows,$rows_count,$var,$value) : ''
							)
							,$src
						);
			}
		}
		return $src;
		/***
			# unit
			$src = pre('
						<rt:unit param="abc" var="unit_list" cols="3" offset="2" counter="counter">
						<rt:first>FIRST</rt:first>{$counter}{
						<rt:loop param="unit_list" var="a"><rt:first>first</rt:first>{$a}<rt:last>last</rt:last></rt:loop>
						}
						<rt:last>LAST</rt:last>
						</rt:unit>
					');
			$result = pre('
							FIRST1{
							first234last}
							2{
							first567last}
							3{
							first8910last}
							LAST
						');
			$t = new self();
			$t->vars("abc",array(1,2,3,4,5,6,7,8,9,10));
			eq($result,$t->get($src));
		*/
		/***
			# rows_fill
			$src = pre('<rt:unit param="abc" var="abc_var" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
			$result = '[123][400][000]';
			$t = new self();			
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));
			
			$src = pre('<rt:unit param="abc" var="abc_var" offset="3" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
			$result = '[340][000][000]';
			$t = new self();
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));
		 */
	}
	private function rtloop($src){
		if(strpos($src,'rt:loop') !== false){
			while(XmlObject::set($tag,$src,'rt:loop')){
				$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
				$offset = ($tag->is_attr('offset')) ? (ctype_digit($tag->in_attr('offset')) ? $tag->in_attr('offset') : $this->variable_string($this->parse_plain_variable($tag->in_attr('offset')))) : 1;
				$limit = ($tag->is_attr('limit')) ? (ctype_digit($tag->in_attr('limit')) ? $tag->in_attr('limit') : $this->variable_string($this->parse_plain_variable($tag->in_attr('limit')))) : 0;
				if(empty($param) && $tag->is_attr('range')){
					list($range_start,$range_end) = explode(',',$tag->in_attr('range'),2);
					$range = ($tag->is_attr('range_step')) ? sprintf('range(%d,%d,%d)',$range_start,$range_end,$tag->in_attr('range_step')) :
																sprintf('range("%s","%s")',$range_start,$range_end);
					$param = sprintf('array_combine(%s,%s)',$range,$range);
				}
				$is_fill = false;
				$uniq = uniqid('');
				$even = $tag->in_attr('even_value','even');
				$odd = $tag->in_attr('odd_value','odd');
				$evenodd = '$'.$tag->in_attr('evenodd','loop_evenodd');

				$first_value = $tag->in_attr('first_value','first');
				$first = '$'.$tag->in_attr('first','_first_'.$uniq);
				$first_flg = '$__isfirst__'.$uniq;
				$last_value = $tag->in_attr('last_value','last');
				$last = '$'.$tag->in_attr('last','_last_'.$uniq);
				$last_flg = '$__islast__'.$uniq;
				$shortfall = '$'.$tag->in_attr('shortfall','_DEFI_'.$uniq);

				$var = '$'.$tag->in_attr('var','_var_'.$uniq);
				$key = '$'.$tag->in_attr('key','_key_'.$uniq);
				$total = '$'.$tag->in_attr('total','_total_'.$uniq);
				$vtotal = '$__vtotal__'.$uniq;				
				$counter = '$'.$tag->in_attr('counter','_counter_'.$uniq);
				$loop_counter = '$'.$tag->in_attr('loop_counter','_loop_counter_'.$uniq);
				$reverse = (strtolower($tag->in_attr('reverse') === 'true'));

				$varname = '$_'.$uniq;
				$countname = '$__count__'.$uniq;
				$lcountname = '$__vcount__'.$uniq;
				$offsetname	= '$__offset__'.$uniq;
				$limitname = '$__limit__'.$uniq;

				$value = $tag->value();
				$empty_value = null;
				while(XmlObject::set($subtag,$value,'rt:loop')){
					$value = $this->rtloop($value);
				}
				while(XmlObject::set($subtag,$value,'rt:first')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$first
					,(($subtag->in_attr('last') === 'false') ? sprintf(' && (%s !== 1) ',$total) : '')
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(XmlObject::set($subtag,$value,'rt:middle')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(!isset(%s) && !isset(%s)){ ?>%s<?php } ?>',$first,$last
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(XmlObject::set($subtag,$value,'rt:last')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$last
					,(($subtag->in_attr('first') === 'false') ? sprintf(' && (%s !== 1) ',$vtotal) : '')
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(XmlObject::set($subtag,$value,'rt:fill')){
					$is_fill = true;
					$value = str_replace($subtag->plain(),sprintf('<?php if(%s > %s){ ?>%s<?php } ?>',$lcountname,$total
					,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}				
				$value = $this->rtif($value);
				if(preg_match("/^(.+)<rt\:else[\s]*.*?>(.+)$/ims",$value,$match)){
					list(,$value,$empty_value) = $match;
				}
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php try{ ?>"
									."<?php "
										." %s=%s;"
										." if(is_array(%s)){"
											." if(%s){ krsort(%s); }"
											." %s=%s=sizeof(%s); %s=%s=1; %s=%s; %s=((%s>0) ? (%s + %s) : 0); "
											." %s=%s=false; %s=0; %s=%s=null;"
											." if(%s){ for(\$i=0;\$i<(%s+%s-%s);\$i++){ %s[] = null; } %s=sizeof(%s); }"
											." foreach(%s as %s => %s){"
												." if(%s <= %s){"
													." if(!%s){ %s=true; %s='%s'; }"
													." if((%s > 0 && (%s+1) == %s) || %s===%s){ %s=true; %s='%s'; %s=(%s-%s+1) * -1;}"													
													." %s=((%s %% 2) === 0) ? '%s' : '%s';"
													." %s=%s; %s=%s;"
													." ?>%s<?php "
													." %s=%s=null;"
													." %s++;"
												." }"
												." %s++;"
												." if(%s > 0 && %s >= %s){ break; }"
											." }"
											." if(!%s){ ?>%s<?php } "
											." unset(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s);"
										." }"
									." ?>"
									."<?php }catch(Exception \$e){ if(!isset(\$_nes_)){print('".$this->exception_str."');} } ?>"
									,$varname,$param
									,$varname
										,(($reverse) ? 'true' : 'false'),$varname
										,$vtotal,$total,$varname,$countname,$lcountname,$offsetname,$offset,$limitname,$limit,$offset,$limit
										,$first_flg,$last_flg,$shortfall,$first,$last
										,($is_fill ? 'true' : 'false'),$offsetname,$limitname,$total,$varname,$vtotal,$varname
										,$varname,$key,$var
											,$offsetname,$lcountname

											
												,$first_flg,$first_flg,$first,str_replace("'","\\'",$first_value)
												,$limitname,$lcountname,$limitname,$lcountname,$vtotal,$last_flg,$last,str_replace("'","\\'",$last_value),$shortfall,$lcountname,$limitname


												,$evenodd,$countname,$even,$odd
												,$counter,$countname,$loop_counter,$lcountname
												,$value
												,$first,$last
												,$countname
											,$lcountname
											,$limitname,$lcountname,$limitname
									,$first_flg,$empty_value
									,$var,$counter,$key,$countname,$lcountname,$offsetname,$limitname,$varname,$first,$first_flg,$last,$last_flg
							)
							,$src
						);
			}
		}
		return $src;
		/***
			$src = pre('
						<rt:loop param="abc" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							1: A => 456
							2: B => 789
							3: C => 010
							hoge
						');
			$t = new self();
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010"));
			eq($result,$t->get($src));
		*/
		/***
			$t = new self();
			$src = pre('
						<rt:loop param="abc" offset="2" limit="2" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							2: B => 789
							3: C => 010
							hoge
						');
			$t = new self();
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
			eq($result,$t->get($src));
		*/
		/***
			# limit
			$t = new self();
			$src = pre('
						<rt:loop param="abc" offset="{$offset}" limit="{$limit}" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							2: B => 789
							3: C => 010
							4: D => 999
							hoge
						');
			$t = new self();
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999","E"=>"111"));
			$t->vars("offset",2);
			$t->vars("limit",3);
			eq($result,$t->get($src));
		*/
		/***
			# range
			$t = new self();
			$src = pre('<rt:loop range="0,5" var="var">{$var}</rt:loop>');
			$result = pre('012345');
			eq($result,$t->get($src));

			$src = pre('<rt:loop range="0,6" range_step="2" var="var">{$var}</rt:loop>');
			$result = pre('0246');
			eq($result,$t->get($src));

			$src = pre('<rt:loop range="A,F" var="var">{$var}</rt:loop>');
			$result = pre('ABCDEF');
			eq($result,$t->get($src));
		 */
		/***
			# multi
			$t = new self();
			$src = pre('<rt:loop range="1,2" var="a"><rt:loop range="1,2" var="b">{$a}{$b}</rt:loop>-</rt:loop>');
			$result = pre('1112-2122-');
			eq($result,$t->get($src));
		 */
		/***
			# empty
			$t = new self();
			$src = pre('<rt:loop param="abc">aaa</rt:loop>');
			$result = pre('');
			$t->vars("abc",array());
			eq($result,$t->get($src));
		 */
		/***
			# total
			$t = new self();
			$src = pre('<rt:loop param="abc" total="total">{$total}</rt:loop>');
			$result = pre('4444');
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));

			$t = new self();
			$src = pre('<rt:loop param="abc" total="total" offset="2" limit="2">{$total}</rt:loop>');
			$result = pre('44');
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));
		 */
		/***
			# evenodd
			$t = new self();
			$src = pre('<rt:loop range="0,5" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>');
			$result = pre('1[odd]2[even]3[odd]4[even]5[odd]6[even]');
			eq($result,$t->get($src));
		 */
		/***
			# first_last
			$t = new self();
			$src = pre('<rt:loop param="abc" var="var" first="first" last="last">{$first}{$var}{$last}</rt:loop>');
			$result = pre('first12345last');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));

			$t = new self();
			$src = pre('<rt:loop param="abc" var="var" first="first" last="last" offset="2" limit="2">{$first}{$var}{$last}</rt:loop>');
			$result = pre('first23last');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));

			$t = new self();
			$src = pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F</rt:first>[<rt:middle>{$var}</rt:middle>]<rt:last>L</rt:last></rt:loop>');
			$result = pre('F[][3][]L');
			$t->vars("abc",array(1,2,3,4,5,6));
			eq($result,$t->get($src));
		*/
		/***
			# first_last_block
			$t = new self();
			$src = pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F<rt:if param="var" value="1">I<rt:else />E</rt:if><rt:else />nf</rt:first>[<rt:middle>{$var}<rt:else />nm</rt:middle>]<rt:last>L<rt:else />nl</rt:last></rt:loop>');

			$result = pre('FE[nm]nlnf[3]nlnf[nm]L');
			$t->vars("abc",array(1,2,3,4,5,6));
			eq($result,$t->get($src));
		 */
		/***
			# first_in_last
			$t = new self();
			$src = pre('<rt:loop param="abc" var="var"><rt:last>L</rt:last></rt:loop>');
			$t->vars("abc",array(1));
			eq("L",$t->get($src));

			$t = new self();
			$src = pre('<rt:loop param="abc" var="var"><rt:last first="false">L</rt:last></rt:loop>');
			$t->vars("abc",array(1));
			eq("",$t->get($src));
		 */
		/***
			# last_in_first
			$t = new self();
			$src = pre('<rt:loop param="abc" var="var"><rt:first>F</rt:first></rt:loop>');
			$t->vars("abc",array(1));
			eq("F",$t->get($src));

			$t = new self();
			$src = pre('<rt:loop param="abc" var="var"><rt:first last="false">F</rt:first></rt:loop>');
			$t->vars("abc",array(1));
			eq("",$t->get($src));
		 */
		/***
			# difi
			$t = new self();
			$src = pre('<rt:loop param="abc" limit="10" shortfall="difi" var="var">{$var}{$difi}</rt:loop>');
			$result = pre('102030405064');
			$t->vars("abc",array(1,2,3,4,5,6));
			eq($result,$t->get($src));
		*/
		/***
			# empty
			$t = new self();
			$src = pre('<rt:loop param="abc">aaaaaa<rt:else />EMPTY</rt:loop>');
			$result = pre('EMPTY');
			$t->vars("abc",array());
			eq($result,$t->get($src));
			
			$t = new self();
			$src = pre('<rt:loop param="abc">aaaaaa<rt:else>EMPTY</rt:loop>');
			$result = pre('EMPTY');
			$t->vars("abc",array());
			eq($result,$t->get($src));
		*/
		/***
			# fill
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
			$result = pre('F45hogehogeL');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
			
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill><rt:first>f</rt:first>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill><rt:else />empty</rt:loop>');
			$result = pre('fhogehogehogehogeL');
			$t->vars("abc",array());
			eq($result,$t->get($src));			
		*/
		/***
			# fill_no_limit
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
			$result = pre('F12345');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
		*/
		/***
			# fill_last
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
			$result = pre('45hogeLast');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
			
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
			$result = pre('123Last');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
			
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" offset="6" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
			$result = pre('hogehogehogeLast');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));			
		*/
		/***
			# fill_first
			$t = new self();
			$src = pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:first>First</rt:first></rt:loop>');
			$result = pre('4First5hoge');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
		*/
		/***
			# fill_middle
			$template = new self();
			$src = pre('<rt:loop param="abc" var="a" limit="4" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:middle>M</rt:middle></rt:loop>');
			$result = pre('45MhogeMhoge');
			$t->vars("abc",array(1,2,3,4,5));
			eq($result,$t->get($src));
		*/
	}
	private function rtif($src){
		if(strpos($src,'rt:if') !== false){
			while(XmlObject::set($tag,$src,'rt:if')){
				if(!$tag->is_attr('param')) throw new LogicException('if');
				$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));

				if($tag->is_attr('value')){
					$arg2 = $this->parse_plain_variable($tag->in_attr('value'));
					if($arg2 == 'true' || $arg2 == 'false' || ctype_digit((string)$arg2)){
						$cond = sprintf('<?php if(%s === %s || %s === "%s"){ ?>',$arg1,$arg2,$arg1,$arg2);
					}else{
						if($arg2 === '' || $arg2[0] != '$') $arg2 = '"'.$arg2.'"';
						$cond = sprintf('<?php if(%s === %s){ ?>',$arg1,$arg2);
					}
				}else{
					$uniq = uniqid('$I');
					$cond = sprintf('<?php %s=%s; ?>',$uniq,$arg1)
								.sprintf('<?php if(%s !== null && %s !== false && ( (!is_string(%s) && !is_array(%s)) || (is_string(%s) && %s !== "") || (is_array(%s) && !empty(%s)) ) ){ ?>',$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq);
				}
				$src = str_replace(
							$tag->plain()
							,'<?php try{ ?>'.$cond
								.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$tag->value())
							."<?php } ?>"."<?php }catch(Exception \$e){ if(!isset(\$_nes_)){print('".$this->exception_str."');} } ?>"
							,$src
						);
			}
		}
		return $src;
		/***
			$src = pre('<rt:if param="abc">hoge</rt:if>');
			$result = pre('hoge');
			$t = new self();
			$t->vars("abc",true);
			eq($result,$t->get($src));

			$src = pre('<rt:if param="abc" value="xyz">hoge</rt:if>');
			$result = pre('hoge');
			$t = new self();
			$t->vars("abc","xyz");
			eq($result,$t->get($src));

			$src = pre('<rt:if param="abc" value="1">hoge</rt:if>');
			$result = pre('hoge');
			$t = new self();
			$t->vars("abc",1);
			eq($result,$t->get($src));

			$src = pre('<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>');
			$result = pre('bb');
			$t = new self();
			$t->vars("abc",1);
			eq($result,$t->get($src));

			$src = pre('<rt:if param="abc" value="1">bb<rt:else />aa</rt:if>');
			$result = pre('aa');
			$t = new self();
			$t->vars("abc",2);
			eq($result,$t->get($src));

			$src = pre('<rt:if param="abc" value="{$a}">bb<rt:else />aa</rt:if>');
			$result = pre('bb');
			$t = new self();
			$t->vars("abc",2);
			$t->vars("a",2);
			eq($result,$t->get($src));
			
			$src = pre('<rt:loop range="1,5" var="c"><rt:if param="{$c}" value="{$a}">A<rt:else />{$c}</rt:if></rt:loop>');
			$result = pre('1A345');
			$t = new self();
			$t->vars("abc",2);
			$t->vars("a",2);
			eq($result,$t->get($src));			
		*/
	}
	private function parse_print_variable($src){
		foreach($this->match_variable($src) as $variable){
			$name = $this->parse_plain_variable($variable);
			$value = "<?php try{ ?>"."<?php @print(".$name."); ?>"."<?php }catch(Exception \$e){ if(!isset(\$_nes_)){print('".$this->exception_str."');} } ?>";
			$src = str_replace(array($variable."\n",$variable),array($value."\n\n",$value),$src);
		}
		return $src;
	}
	private function match_variable($src){
		$hash = array();
		while(preg_match("/({(\\$[\$\w][^\t]*)})/s",$src,$vars,PREG_OFFSET_CAPTURE)){
			list($value,$pos) = $vars[1];
			if($value == "") break;
			if(substr_count($value,'}') > 1){
				for($i=0,$start=0,$end=0;$i<strlen($value);$i++){
					if($value[$i] == '{'){
						$start++;
					}else if($value[$i] == '}'){
						if($start == ++$end){
							$value = substr($value,0,$i+1);
							break;
						}
					}
				}
			}
			$length	= strlen($value);
			$src = substr($src,$pos + $length);
			$hash[sprintf('%03d_%s',$length,$value)] = $value;
		}
		krsort($hash,SORT_STRING);
		return $hash;
	}
	private function parse_plain_variable($src){
		while(true){
			$array = $this->match_variable($src);
			if(sizeof($array) <= 0)	break;
			foreach($array as $v){
				$tmp = $v;
				if(preg_match_all("/([\"\'])([^\\1]+?)\\1/",$v,$match)){
					foreach($match[2] as $value) $tmp = str_replace($value,str_replace('.','__PERIOD__',$value),$tmp);
				}
				$src = str_replace($v,str_replace('.','->',substr($tmp,1,-1)),$src);
			}
		}
		return str_replace('[]','',str_replace('__PERIOD__','.',$src));
	}
	private function variable_string($src){
		return (empty($src) || isset($src[0]) && $src[0] == '$') ? $src : '$'.$src;
	}
	private function html_reform($src){
		if(strpos($src,'rt:aref') !== false){
			$bool = false;
			XmlObject::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in('form') as $obj){
				if(($obj->in_attr('rt:aref') === 'true')){
					$form = $obj->value();
					foreach($obj->in(array('input','select')) as $tag){
						if($tag->is_attr('name') || $tag->is_attr('id')){
							$name = $this->parse_plain_variable($this->form_variable_name($tag->in_attr('name',$tag->in_attr('id'))));
							switch(strtolower($tag->name())){
								case 'input':
									switch(strtolower($tag->in_attr('type'))){
										case 'radio':
										case 'checkbox':
											$tag->plain_attr($this->check_selected($name,sprintf("'%s'",$this->parse_plain_variable($tag->in_attr('value','true'))),'checked'));
											$form = str_replace($tag->plain(),$tag->get(),$form);
											$bool = true;
									}
									break;
								case 'select':
									$select = $tag->value();
									foreach($tag->in('option') as $option){
										$option->plain_attr($this->check_selected($name,sprintf("'%s'",$this->parse_plain_variable($option->in_attr('value'))),'selected'));
										$select = str_replace($option->plain(),$option->get(),$select);
									}
									$tag->value($select);
									$form = str_replace($tag->plain(),$tag->get(),$form);
									$bool = true;
							}
						}
					}
					$obj->rm_attr('rt:aref');
					$obj->value($form);
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
			return ($bool) ? $this->exec($src) : $src;
		}
		return $src;
	}
	private function html_form($src){
		XmlObject::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in('form') as $obj){
			if($this->is_reference($obj)){
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
			}
			$src = str_replace($obj->plain(),$obj->get(),$src);
		}
		return $this->html_input($src);
	}
	private function no_exception_str($value){
		return '<?php $_nes_=1; ?>'.$value.'<?php $_nes_=null; ?>';
	}
	private function html_input($src){
		XmlObject::set($tag,'<:>'.$src.'</:>');
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
				if($obj->is_attr('rt:param') || $obj->is_attr('rt:range')){
					switch($lname){
						case 'select':
							$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" key="%s" offset="%s" limit="%s" reverse="%s" evenodd="%s" even_value="%s" odd_value="%s" range="%s" range_step="%s">'
											.'<option value="{$%s}">{$%s}</option>'
											.'</rt:loop>'
											,$obj->in_attr('rt:param'),$obj->in_attr('rt:var','loop_var'.$uid),$obj->in_attr('rt:counter','loop_counter'.$uid)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
											,$obj->in_attr('rt:reverse','false')
											,$obj->in_attr('rt:evenodd','loop_evenodd'.$uid),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
											,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:var','loop_var'.$uid)
							);
							$obj->value($this->rtloop($value));
							if($obj->is_attr('rt:null')) $obj->value('<option value="">'.$obj->in_attr('rt:null').'</option>'.$obj->value());
					}
					$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd'
									,'rt:range','rt:range_step','rt:even_value','rt:odd_value');
					$change = true;
				}
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
					$change = true;
				}else if($obj->is_attr('rt:ref')){
					$obj->rm_attr('rt:ref');
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
		/***
			#input
			$src = pre('
						<form rt:ref="true">
							<input type="text" name="aaa" />
							<input type="checkbox" name="bbb" value="hoge" />hoge
							<input type="checkbox" name="bbb" value="fuga" checked="checked" />fuga
							<input type="checkbox" name="eee" value="true" checked />foo
							<input type="checkbox" name="fff" value="false" />foo
							<input type="submit" />
							<textarea name="aaa"></textarea>

							<select name="ddd" size="5" multiple>
								<option value="123" selected="selected">123</option>
								<option value="456">456</option>
								<option value="789" selected>789</option>
							</select>
							<select name="XYZ" rt:param="xyz"></select>
						</form>
					');
			$result = pre('
						<form>
							<input type="text" name="aaa" value="hogehoge" />
							<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
							<input type="checkbox" name="bbb[]" value="fuga" />fuga
							<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
							<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
							<input type="submit" />
							<textarea name="aaa">hogehoge</textarea>

							<select name="ddd[]" size="5" multiple="multiple">
								<option value="123">123</option>
								<option value="456" selected="selected">456</option>
								<option value="789" selected="selected">789</option>
							</select>
							<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
						</form>
						');
			$t = new self();
			$t->vars("aaa","hogehoge");
			$t->vars("bbb","hoge");
			$t->vars("XYZ","B");
			$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
			$t->vars("ddd",array("456","789"));
			$t->vars("eee",true);
			$t->vars("fff",false);
			eq($result,$t->get($src));

			$src = pre('
						<form rt:ref="true">
							<select name="ddd" rt:param="abc">
							</select>
						</form>
					');
			$result = pre('
						<form>
							<select name="ddd"><option value="123">123</option><option value="456" selected="selected">456</option><option value="789">789</option></select>
						</form>
						');
			$t = new self();
			$t->vars("abc",array(123=>123,456=>456,789=>789));
			$t->vars("ddd","456");
			eq($result,$t->get($src));

			$src = pre('
						<form rt:ref="true">
						<rt:loop param="abc" var="v">
						<input type="checkbox" name="ddd" value="{$v}" />
						</rt:loop>
						</form>
					');
			$result = pre('
							<form>
							<input type="checkbox" name="ddd[]" value="123" />
							<input type="checkbox" name="ddd[]" value="456" checked="checked" />
							<input type="checkbox" name="ddd[]" value="789" />
							</form>
						');
			$t = new self();
			$t->vars("abc",array(123=>123,456=>456,789=>789));
			$t->vars("ddd","456");
			eq($result,$t->get($src));

		*/
		/***
			# textarea
			$src = pre('
							<form>
								<textarea name="hoge"></textarea>
							</form>
						');
			$t = new self();
			eq($src,$t->get($src));
		 */
		/***
			#select
			$src = '<form><select name="abc" rt:param="abc"></select></form>';
			$t = new self();
			$t->vars("abc",array(123=>123,456=>456));
			eq('<form><select name="abc"><option value="123">123</option><option value="456">456</option></select></form>',$t->get($src));
		 */
		/***
			#multiple
			$src = '<form><input name="abc" type="checkbox" /></form>';
			$t = new self();
			eq('<form><input name="abc[]" type="checkbox" /></form>',$t->get($src));

			$src = '<form><input name="abc" type="checkbox" rt:multiple="false" /></form>';
			$t = new self();
			eq('<form><input name="abc" type="checkbox" /></form>',$t->get($src));
		 */
		/***
			# input_exception
			$src = pre('{$hoge}');
			$t = new self();
			$t->exception_str('EXCEPTION');
			eq('EXCEPTION',$t->get($src));

			$src = pre('<form rt:ref="true"><input type="text" name="hoge" /></form>');
			$t = new self();
			eq('<form><input type="text" name="hoge" value="" /></form>',$t->get($src));

			$src = pre('<form rt:ref="true"><input type="password" name="hoge" /></form>');
			$t = new self();
			eq('<form><input type="password" name="hoge" value="" /></form>',$t->get($src));
			
			$src = pre('<form rt:ref="true"><input type="hidden" name="hoge" /></form>');
			$t = new self();
			eq('<form><input type="hidden" name="hoge" value="" /></form>',$t->get($src));

			$src = pre('<form rt:ref="true"><input type="checkbox" name="hoge" /></form>');
			$t = new self();
			eq('<form><input type="checkbox" name="hoge[]" /></form>',$t->get($src));
			
			$src = pre('<form rt:ref="true"><input type="radio" name="hoge" /></form>');
			$t = new self();
			eq('<form><input type="radio" name="hoge" /></form>',$t->get($src));

			$src = pre('<form rt:ref="true"><textarea name="hoge"></textarea></form>');
			$t = new self();
			eq('<form><textarea name="hoge"></textarea></form>',$t->get($src));

			$src = pre('<form rt:ref="true"><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>');
			$t = new self();
			eq('<form><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>',$t->get($src));
		 */
		/***
			#html5
			$src = pre('
							<form rt:ref="true">
								<input type="search" name="search" />
								<input type="tel" name="tel" />
								<input type="url" name="url" />
								<input type="email" name="email" />
								<input type="datetime" name="datetime" />
								<input type="datetime-local" name="datetime_local" />
								<input type="date" name="date" />
								<input type="month" name="month" />
								<input type="week" name="week" />
								<input type="time" name="time" />
								<input type="number" name="number" />
								<input type="range" name="range" />
								<input type="color" name="color" />
							</form>
						');
			$rslt = pre('
							<form>
								<input type="search" name="search" value="hoge" />
								<input type="tel" name="tel" value="000-000-0000" />
								<input type="url" name="url" value="http://rhaco.org" />
								<input type="email" name="email" value="hoge@hoge.hoge" />
								<input type="datetime" name="datetime" value="1970-01-01T00:00:00.0Z" />
								<input type="datetime-local" name="datetime_local" value="1970-01-01T00:00:00.0Z" />
								<input type="date" name="date" value="1970-01-01" />
								<input type="month" name="month" value="1970-01" />
								<input type="week" name="week" value="1970-W15" />
								<input type="time" name="time" value="12:30" />
								<input type="number" name="number" value="1234" />
								<input type="range" name="range" value="7" />
								<input type="color" name="color" value="#ff0000" />
							</form>
						');
			$t = new self();
			$t->vars("search","hoge");
			$t->vars("tel","000-000-0000");
			$t->vars("url","http://rhaco.org");
			$t->vars("email","hoge@hoge.hoge");
			$t->vars("datetime","1970-01-01T00:00:00.0Z");
			$t->vars("datetime_local","1970-01-01T00:00:00.0Z");
			$t->vars("date","1970-01-01");
			$t->vars("month","1970-01");
			$t->vars("week","1970-W15");
			$t->vars("time","12:30");
			$t->vars("number","1234");
			$t->vars("range","7");
			$t->vars("color","#ff0000");

			eq($rslt,$t->get($src));
		 */
	}
	private function check_selected($name,$value,$selected){
		return sprintf('<?php if('
					.'isset(%s) && (%s === %s '
										.' || (ctype_digit((string)%s) && %s == %s)'
										.' || ((%s == "true" || %s == "false") ? (%s === (%s == "true")) : false)'
										.' || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? array() : array(%s))),true) '
									.') '
					.'){print(" %s=\"%s\"");} ?>'
					,$name,$name,$value
					,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
					,$selected,$selected
				);
	}
	private function html_list($src){
		if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
			$tags = array();
			foreach($m[1] as $k => $v){
				if(XmlObject::set($tag,substr($src,$v[1]-1),$v[0])) $tags[] = $tag;
			}
			foreach($tags as $obj){
				$obj->escape(false);
				$name = strtolower($obj->name());
				$param = $obj->in_attr('rt:param');
				$null = strtolower($obj->in_attr('rt:null'));
				$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" '
									.'key="%s" offset="%s" limit="%s" '
									.'reverse="%s" '
									.'evenodd="%s" even_value="%s" odd_value="%s" '
									.'range="%s" range_step="%s" '
									.'shortfall="%s">'
								,$param,$obj->in_attr('rt:var','loop_var'),$obj->in_attr('rt:counter','loop_counter')
								,$obj->in_attr('rt:key','loop_key'),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
								,$obj->in_attr('rt:reverse','false')
								,$obj->in_attr('rt:evenodd','loop_evenodd'),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
								,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
								,$tag->in_attr('rt:shortfall','_DEFI_'.uniqid())
							);
				$rawvalue = $obj->value();
				if($name == 'table' && XmlObject::set($t,$rawvalue,'tbody')){
					$t->escape(false);
					$t->value($value.$this->table_tr_even_odd($t->value(),(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>');
					$value = str_replace($t->plain(),$t->get(),$rawvalue);
				}else{
					$value = $value.$this->table_tr_even_odd($rawvalue,(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>';
				}
				$obj->value($this->html_list($value));
				$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd','rt:range'
								,'rt:range_step','rt:even_value','rt:odd_value','rt:shortfall');
				$src = str_replace($obj->plain(),
						($null === 'true') ? $this->rtif(sprintf('<rt:if param="%s">',$param).$obj->get().'</rt:if>') : $obj->get(),
						$src);
			}
		}
		return $src;
		/***
		 	$src = pre('
						<table><tr><td><table rt:param="xyz" rt:var="o">
						<tr class="odd"><td>{$o["B"]}</td></tr>
						</table></td></tr></table>
					');
			$result = pre('
							<table><tr><td><table><tr class="odd"><td>222</td></tr>
							<tr class="even"><td>444</td></tr>
							<tr class="odd"><td>666</td></tr>
							</table></td></tr></table>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		 */
		/***
		 	$src = pre('
						<table rt:param="abc" rt:var="a"><tr><td><table rt:param="a" rt:var="x"><tr><td>{$x}</td></tr></table></td></td></table>
					');
			$result = pre('
						<table><tr><td><table><tr><td>A</td></tr><tr><td>B</td></tr></table></td></td><tr><td><table><tr><td>C</td></tr><tr><td>D</td></tr></table></td></td></table>
						');
			$t = new self();
			$t->vars("abc",array(array("A","B"),array("C","D")));
			eq($result,$t->get($src));
		 */
		/***
		 	$src = pre('
						<ul rt:param="abc" rt:var="a"><li><ul rt:param="a" rt:var="x"><li>{$x}</li></ul></li></ul>
					');
			$result = pre('
						<ul><li><ul><li>A</li><li>B</li></ul></li><li><ul><li>C</li><li>D</li></ul></li></ul>
						');
			$t = new self();
			$t->vars("abc",array(array("A","B"),array("C","D")));
			eq($result,$t->get($src));
		 */
		/***
		 	$src = pre('
						<table rt:param="xyz" rt:var="o">
						<tr class="odd"><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = pre('
							<table><tr class="odd"><td>222</td></tr>
							<tr class="even"><td>444</td></tr>
							<tr class="odd"><td>666</td></tr>
							</table>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		*/
		/***
		 	$src = pre('
						<table rt:param="xyz" rt:var="o">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = pre('
							<table><tr><td>222</td></tr>
							<tr><td>444</td></tr>
							<tr><td>666</td></tr>
							</table>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		*/
		/***
		 	$src = pre('
						<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = pre('
							<table><tr><td>222</td></tr>
							</table>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		*/
		/***
		 	$src = pre('
						<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
						<thead>
							<tr><th>hoge</th></tr>
						</thead>
						<tbody>
							<tr><td>{$o["B"]}</td></tr>
						</tbody>
						</table>
					');
			$result = pre('
							<table>
							<thead>
								<tr><th>hoge</th></tr>
							</thead>
							<tbody>	<tr><td>222</td></tr>
							</tbody>
							</table>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		*/
		/***
		 	$src = pre('
						<table rt:param="xyz" rt:null="true">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$t = new self();
			$t->vars("xyz",array());
			eq("",$t->get($src));
		*/
		/***
		 	$src = pre('
						<ul rt:param="xyz" rt:var="o">
							<li class="odd">{$o["B"]}</li>
						</ul>
					');
			$result = pre('
							<ul>	<li class="odd">222</li>
								<li class="even">444</li>
								<li class="odd">666</li>
							</ul>
						');
			$t = new self();
			$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$t->get($src));
		*/
		/***
			# abc
		 	$src = pre('
						<rt:loop param="abc" var="a">
						<ul rt:param="{$a}" rt:var="b">
						<li>
						<ul rt:param="{$b}" rt:var="c">
						<li>{$c}<rt:loop param="xyz" var="z">{$z}</rt:loop></li>
						</ul>
						</li>
						</ul>
						</rt:loop>
					');
			$result = pre('
							<ul><li>
							<ul><li>A12</li>
							<li>B12</li>
							</ul>
							</li>
							</ul>
							<ul><li>
							<ul><li>C12</li>
							<li>D12</li>
							</ul>
							</li>
							</ul>

						');
			$t = new self();
			$t->vars("abc",array(array(array("A","B")),array(array("C","D"))));
			$t->vars("xyz",array(1,2));
			eq($result,$t->get($src));
		*/
		/***
			# range
		 	$src = pre('<ul rt:range="1,3" rt:var="o"><li>{$o}</li></ul>');
			$result = pre('<ul><li>1</li><li>2</li><li>3</li></ul>');
			$t = new self();
			eq($result,$t->get($src));
		*/
		/***
			# nest_table
			$src = pre('<table rt:param="object_list" rt:var="obj"><tr><td><table rt:param="obj" rt:var="o"><tr><td>{$o}</td></tr></table></td></tr></table>');
			$t = new self();
			$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
			eq('<table><tr><td><table><tr><td>A1</td></tr><tr><td>A2</td></tr><tr><td>A3</td></tr></table></td></tr><tr><td><table><tr><td>B1</td></tr><tr><td>B2</td></tr><tr><td>B3</td></tr></table></td></tr></table>',$t->get($src));
		*/
		/***
			# nest_ul
			$src = pre('<ul rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ul>');
			$t = new self();
			$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
			eq('<ul><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ul>',$t->get($src));
		*/
		/***
			# nest_ol
			$src = pre('<ol rt:param="object_list" rt:var="obj"><li><ol rt:param="obj" rt:var="o"><li>{$o}</li></ol></li></ol>');
			$t = new self();
			$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
			eq('<ol><li><ol><li>A1</li><li>A2</li><li>A3</li></ol></li><li><ol><li>B1</li><li>B2</li><li>B3</li></ol></li></ol>',$t->get($src));
		*/
		/***
			# nest_olul
			$src = pre('<ol rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ol>');
			$t = new self();
			$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
			eq('<ol><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ol>',$t->get($src));
		*/
		/***
			# nest_tableul
			$src = pre('<table rt:param="object_list" rt:var="obj"><tr><td><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></td></tr></table>');
			$t = new self();
			$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
			eq('<table><tr><td><ul><li>A1</li><li>A2</li><li>A3</li></ul></td></tr><tr><td><ul><li>B1</li><li>B2</li><li>B3</li></ul></td></tr></table>',$t->get($src));
		*/
	}
	private function table_tr_even_odd($src,$name,$even_odd){
		XmlObject::set($tag,'<:>'.$src.'</:>');
		foreach($tag->in($name) as $tr){
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
	private function is_reference(&$tag){
		$bool = ($tag->in_attr('rt:ref') === 'true');
		$tag->rm_attr('rt:ref');
		return $bool;
	}
	/**
	 * モジュールを追加する
	 * @param object $o
	 */
	public function set_object_module($o){
		$this->module[] = $o;
	}
	private function has_object_module($n){
		foreach($this->module as $o){
			if(method_exists($o,$n)) return true;
		}
		return false;
	}
	private function object_module($n,&$p0=null,&$p1=null){
		$r = null;
		foreach($this->module as $o){
			if(method_exists($o,$n)) $r = call_user_func_array(array($o,$n),array(&$p0,&$p1));
		}
		return $r;
	}
}
class TemplateHelper{
	final public function htmlencode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			return htmlentities($value,ENT_QUOTES,"UTF-8");
		}
		return $value;
	}
}
