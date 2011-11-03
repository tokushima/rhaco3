<?php
/**
 * XMLを処理する
 * @author tokushima
 */
class XmlObject implements IteratorAggregate{
	private $attr = array();
	private $plain_attr = array();
	private $name;
	private $value;
	private $close_empty = true;
	
	private $plain;
	private $pos;
	private $esc = true;
	
	public function __construct($name=null,$value=null){
		$this->name = $name;
		$this->value($value);
	}
	public function getIterator(){
		return new ArrayIterator($this->attr);
	}
	/**
	 * 値が無い場合は閉じを省略する
	 * @param boolean
	 * @return boolean
	 */
	final public function close_empty(){
		if(func_num_args() > 0) $this->close_empty = (boolean)func_get_arg(0);
		return $this->close_empty;
	}
	/**
	 * エスケープするか
	 * @param boolean $bool
	 */
	final public function escape($bool){
		$this->esc = (boolean)$bool;
	}
	/**
	 * setできた文字列
	 * @return string
	 */
	final public function plain(){
		return $this->plain;
	}
	/**
	 * 子要素検索時のカーソル
	 * @return integer
	 */
	final public function cur(){
		return $this->pos;
	}
	/**
	 * 要素名
	 * @return string
	 */	
	final public function name(){
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
				if(!is_numeric($k)) $r .= new self($k,$c);
			}
			$v = $r;
		}else if($this->esc && strpos($v,'<![CDATA[') === false && preg_match("/<|>|\&[^#\da-zA-Z]/",$v)){
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
	final public function value(){
		if(func_num_args() > 0) $this->value = $this->get_value(func_get_arg(0));
		return $this->value;
		/***
			$xml = new self("test");
			eq("hoge",$xml->value("hoge"));
			eq("true",$xml->value(true));
			eq("false",$xml->value(false));
			eq("<abc>1</abc><def>2</def><ghi>3</ghi>",$xml->value(array("abc"=>1,"def"=>2,"ghi"=>3)));
			eq(null,$xml->value(''));
			eq(1,$xml->value('1'));
			eq(null,$xml->value(null));
			eq("<![CDATA[<abc>123</abc>]]>",$xml->value("<abc>123</abc>"));
			eq("<b>123</b>",$xml->value(new self("b","123")));
			
			$xml = new self("test");
			$xml->escape(false);
			eq("<abc>123</abc>",$xml->value("<abc>123</abc>",false));
		 */
	}
	/**
	 * 値を追加する
	 * ２つ目のパラメータがあるとアトリビュートの追加となる
	 * @param mixed $arg
	 */
	final public function add($arg){
		if(func_num_args() == 2){
			$this->attr(func_get_arg(0),func_get_arg(1));
		}else{
			$this->value .= $this->get_value(func_get_arg(0));
		}
		return $this;
		/***
			$x = new self("test");
			$x->value("abc");
			eq("abc",$x->value());
			$x->add("def");
			eq("abcdef",$x->value());
			$x->add(new self("b","123"));
			eq("abcdef<b>123</b>",$x->value());
		 */
	}
	/**
	 * アトリビュートを取得する
	 * @param string $n 取得するアトリビュート名
	 * @param string $d アトリビュートが存在しない場合の代替値
	 * @return string
	 */
	final public function in_attr($n,$d=null){
		return isset($this->attr[strtolower($n)]) ? $this->attr[strtolower($n)] : (isset($d) ? (string)$d : null);
		/***
			$x = new self("test");
			$x->attr("abc",123);
			eq(123,$x->in_attr("abc"));
			eq(null,$x->in_attr("def"));
			eq(456,$x->in_attr("ghi",456));
		 */
	}
	/**
	 * アトリビュートから削除する
	 * パラメータが一つも無ければ全件削除
	 */
	final public function rm_attr(){
		if(func_num_args() === 0){
			$this->attr = array();
		}else{
			foreach(func_get_args() as $n) unset($this->attr[$n]);
		}
		/***
			$x = new self("test");
			$x->attr("abc",123);
			$x->attr("def",456);
			$x->attr("ghi",789);
			
			eq(array("abc"=>123,"def"=>456,"ghi"=>789),iterator_to_array($x));
			$x->rm_attr("def");
			eq(array("abc"=>123,"ghi"=>789),iterator_to_array($x));
			$x->attr("def",456);
			eq(array("abc"=>123,"ghi"=>789,"def"=>456),iterator_to_array($x));
			$x->rm_attr("abc","ghi");
			eq(array("def"=>456),iterator_to_array($x));
		 */
	}
	/**
	 * アトリビュートがあるか
	 * @param string $name
	 * @return boolean
	 */
	final public function is_attr($name){
		return array_key_exists($name,$this->attr);
		/***
			$x = new self("test");
			eq(false,$x->is_attr("abc"));
			$x->attr("abc",123);
			eq(true,$x->is_attr("abc"));
			$x->attr("abc",null);
			eq(true,$x->is_attr("abc"));
			$x->rm_attr("abc");
			eq(false,$x->is_attr("abc"));
		 */
	}
	/**
	 * アトリビュートを設定
	 */
	final public function attr($key,$value){
		$this->attr[strtolower($key)] = is_bool($value) ? (($value) ? 'true' : 'false') : ($this->esc ? htmlentities($value,ENT_QUOTES,'UTF-8') : $value);
		/***
			$x = new self("test");
			$x->attr("abc",123);
			eq(123,$x->in_attr("abc"));
			$x->attr("Abc",456);
			eq(456,$x->in_attr("abc"));
			$x->attr("DEf",555);
			eq(555,$x->in_attr("def"));
			eq(456,$x->in_attr("abc"));
			$x->attr("Abc","<aaa>");
			eq("&lt;aaa&gt;",$x->in_attr("abc"));
			$x->attr("Abc",true);
			eq("true",$x->in_attr("abc"));
			$x->attr("Abc",false);
			eq("false",$x->in_attr("abc"));
			$x->attr("Abc",null);
			eq("",$x->in_attr("abc"));
			$x->attr("ghi",null);
			eq("",$x->in_attr("ghi"));
			eq(array("abc"=>"","def"=>555,"ghi"=>""),iterator_to_array($x));

			$x->attr("Jkl","Jkl");
			eq(array("abc"=>"","def"=>555,"ghi"=>"","jkl"=>"Jkl"),iterator_to_array($x));
		 */
	}
	final public function plain_attr($v){
		$this->plain_attr[] = $v;
	}
	/**
	 * XML文字列を返す
	 */
	public function get(){
		if(empty($this->name)) throw new LogicException("undef name");
		$attr = '';
		foreach($this->attr as $k => $v) $attr .= ' '.$k.'="'.$v.'"';
		return ('<'.$this->name.$attr.(implode(' ',$this->plain_attr)).(($this->close_empty && empty($this->value)) ? ' /' : '').'>')
				.$this->value
				.((!$this->close_empty || !empty($this->value)) ? sprintf("</%s>",$this->name) : '');
		/***
			$x = new self("test",123);
			eq("<test>123</test>",$x->get());
			$x = new self("test",new self("hoge","AAA"));
			eq("<test><hoge>AAA</hoge></test>",$x->get());
			$x = new self("test");
			eq("<test />",$x->get());
			$x = new self("test");
			$x->close_empty(false);
			eq("<test></test>",$x->get());
			$x = new self("test");
			$x->attr("abc",123);
			$x->attr("def",456);
			eq('<test abc="123" def="456" />',$x->get());
		 */
	}
	public function __toString(){
		return $this->get();
		/***
			$x = new self("test",123);
			eq("<test>123</test>",(string)$x);
		 */
	}
	/**
	 * 文字列からXMLを探す
	 * @param mixed $x 見つかった場合にインスタンスがセットされる
	 * @param string $plain 対象の文字列
	 * @param string $name 要素名
	 * @return boolean
	 */
	static public function set(&$x,$plain,$name=null){
		return self::_set($x,$plain,$name);
		/***
			$p = "<abc><def>111</def></abc>";
			if(eq(true,self::set($x,$p))){
				eq("abc",$x->name());
			}
			$p = "<abc><def>111</def></abc>";
			if(eq(true,self::set($x,$p,"def"))){
				eq("def",$x->name());
				eq(111,$x->value());
			}
			$p = "aaaa";
			eq(false,self::set($x,$p));
			$p = "<abc>sss</abc>";
			eq(false,self::set($x,$p,"def"));
			$p = "<abc>sss</a>";
			if(eq(true,self::set($x,$p))){
				eq("<abc />",$x->get());
			}
		 */
	}
	static private function _set(&$x,$plain,$name=null,$vtag=null){
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
					$x->value = (empty($match[4])) ? null : $match[4];
					$attrs = $match[3];
					break;
				}
			}
			if(!isset($x->plain)){
				return self::_set($x,preg_replace('/'.preg_quote($list[0][0][0],'/').'/',substr($list[0][0][0],0,-1).' />',$plain,1),$name,$list[0][0][0]);
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
	/**
	 * 指定の要素を検索する
	 * @param string $tag_name 要素名
	 * @param integer $offset 開始位置
	 * @param integer $length 取得する最大数
	 * @return XmlObjectIterator
	 */
	public function in($name,$offset=0,$length=0){
		return new XmlObjectIterator($name,$this->value(),$offset,$length);
		/***
			$x = new self("abc","<def>123</def><def>456</def><def>789</def>");
			$r = array(123,456,789);
			$i = 0;
			foreach($x->in("def") as $d){
				eq($r[$i],$d->value());
				$i++;
			}
			$x = new self("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
			$r = array(123,456,789);
			$i = 0;
			foreach($x->in("def") as $d){
				eq($r[$i],$d->value());
				$i++;
			}
			$x = new self("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
			$r = array(456,789);
			$i = 0;
			foreach($x->in("def",1) as $d){
				eq($r[$i],$d->value());
				$i++;
			}
			$x = new self("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
			$r = array(456);
			$i = 0;
			foreach($x->in("def",1,1) as $d){
				eq($r[$i],$d->value());
				$i++;
			}
			$x = new self("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
			$i = 0;
			foreach($x->in(array("def","abc")) as $d){
				$i++;
			}
			eq(6,$i);
			$x = new self("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><ghi>000</ghi><abc>GHI</abc><def>789</def>");
			$i = 0;
			foreach($x->in(array("def","abc")) as $d){
				$i++;
			}
			eq(6,$i);
		 */
	}
	/**
	 * パスで検索する
	 * @param string $path 検索文字列
	 * @return mixed
	 */
	public function f($path){
		$arg = (func_num_args() == 2) ? func_get_arg(1) : null;
		$paths = explode('.',$path);
		$last = (strpos($path,'(') === false) ? null : array_pop($paths);
		$tag = clone($this);
		$route = array();
		if($arg !== null) $arg = (is_bool($arg)) ? (($arg) ? 'true' : 'false') : strval($arg);

		foreach($paths as $p){
			$pos = 0;
			$t = null;
			if(preg_match("/^(.+)\[([\d]+?)\]$/",$p,$matchs)) list($tmp,$p,$pos) = $matchs;
			foreach($tag->in($p,$pos,1) as $t);
			if(!isset($t) || !($t instanceof self)){
				$tag = null;
				break;
			}
			$route[] = $tag = $t;
		}
		if($tag instanceof self){
			if($arg === null){
				switch($last){
					case '': return $tag;
					case 'plain()': return $tag->plain();
					case 'value()': return $tag->value();
					default:
						if(preg_match("/^(attr|in)\((.+?)\)$/",$last,$matchs)){
							list($null,$type,$name) = $matchs;
							if($type == 'in'){
								return $tag->in(trim($name));
							}else if($type == 'attr'){
								return $tag->in_attr($name);
							}
						}
						return null;
				}
			}
			if($arg instanceof self) $arg = $arg->get();
			if(is_bool($arg)) $arg = ($arg) ? 'true' : 'false';
			krsort($route,SORT_NUMERIC);
			$ltag = $rtag = $replace = null;
			$f = true;

			foreach($route as $r){
				$ltag = clone($r);
				if($f){
					switch($last){
						case 'value()':
							$replace = $arg;
							break;
						default:
							if(preg_match("/^(attr)\((.+?)\)$/",$last,$matchs)){
								list($null,$type,$name) = $matchs;
								if($type == 'attr'){
									$r->attr($name,$arg);
									$replace = $r->get();
								}else{
									return null;
								}
							}
					}
					$f = false;
				}
				$r->value(empty($rtag) ? $replace : str_replace($rtag->plain(),$replace,$r->value()));
				$replace = $r->get();
				$rtag = clone($ltag);
			}
			$this->value(str_replace($ltag->plain(),$replace,$this->value()));
			return null;
		}
		return (!empty($last) && substr($last,0,2) == 'in') ? array() : null;
		/***
			$src = "<tag><abc><def var='123'><ghi selected>hoge</ghi></def></abc></tag>";
			if(self::set($tag,$src,"tag")){
				eq("hoge",$tag->f("abc.def.ghi.value()"));
				eq("123",$tag->f("abc.def.attr(var)"));
				eq("selected",$tag->f("abc.def.ghi.attr(selected)"));
				eq("<def var='123'><ghi selected>hoge</ghi></def>",$tag->f("abc.def.plain()"));
				eq(null,$tag->f("abc.def.xyz"));
			}
		 	$src = pre('
						<tag>
							<abc>
								<def var="123">
									<ghi selected>hoge</ghi>
									<ghi>
										<jkl>rails</jkl>
									</ghi>
									<ghi ab="true">django</ghi>
								</def>
							</abc>
						</tag>
					');
			self::set($tag,$src,"tag");
			eq("django",$tag->f("abc.def.ghi[2].value()"));
			eq("rails",$tag->f("abc.def.ghi[1].jkl.value()"));
			$tag->f("abc.def.ghi[2].value()","python");
			eq("python",$tag->f("abc.def.ghi[2].value()"));

			eq("123",$tag->f("abc.def.attr(var)"));
			eq("true",$tag->f("abc.def.ghi[2].attr(ab)"));
			$tag->f("abc.def.ghi[2].attr(cd)",456);
			eq("456",$tag->f("abc.def.ghi[2].attr(cd)"));

			eq('selected',$tag->f("abc.def.ghi[0].attr(selected)"));
			eq(null,$tag->f("abc.def.ghi[1].attr(selected)"));
			eq(array(),$tag->f("abc.def.in(xyz)"));
			eq(array(),$tag->f("abc.opq.in(xyz)"));
		*/
	}
	/**
	 * idで検索する
	 *
	 * @param string $name 指定のID
	 * @return self
	 */
	public function id($name){
		if(preg_match("/<.+[\s]*id[\s]*=[\s]*([\"\'])".preg_quote($name)."\\1/",$this->value(),$match,PREG_OFFSET_CAPTURE)){
			if(self::set($tag,substr($this->value(),$match[0][1]))) return $tag;
		}
		return null;
		/***
			$src = pre('
						<aaa>
							<bbb id="DEF"></bbb>
							<ccc id="ABC">
								<ddd id="XYZ">hoge</ddd>
							</ccc>
						</aaa>
					');
			self::set($tag,$src);
			eq("ddd",$tag->id("XYZ")->name());
			eq(null,$tag->id("xyz"));
		 */
	}
	/**
	 * xmlとし出力する
	 * @param string $encoding エンコード名
	 * @param string $name ファイル名
	 */
	public function output($encoding=null,$name=null){
		header(sprintf('Content-Type: application/xml%s',(empty($name) ? '' : sprintf('; name=%s',$name))));
		print($this->get($encoding));
		exit;
	}
	/**
	 * attachmentとして出力する
	 * @param string $encoding エンコード名
	 * @param string $name ファイル名
	 */
	public function attach($encoding=null,$name=null){
		header(sprintf('Content-Disposition: attachment%s',(empty($name) ? '' : sprintf('; filename=%s',$name))));
		$this->output($encoding,$name);
	}
}
class XmlObjectIterator implements Iterator{
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
		if($this->length > 0 && ($this->offset + $this->length) <= $this->count) return false;
		if(is_array($this->name)){
			$tags = array();
			foreach($this->name as $name){
				if(XmlObject::set($get_tag,$this->plain,$name)) $tags[$get_tag->cur()] = $get_tag;
			}
			if(empty($tags)) return false;
			ksort($tags,SORT_NUMERIC);
			foreach($tags as $this->tag) return true;
		}
		return XmlObject::set($this->tag,$this->plain,$this->name);
	}
	public function next(){}
	public function rewind(){
		for($i=0;$i<$this->offset;$i++){
			$this->valid();
			$this->current();
		}
	}
}
