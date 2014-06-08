<?php
namespace org\rhaco;
/**
 * XMLを処理する
 * @author tokushima
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
		return $this;
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
	final public function name($name=null){
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
	final public function value(){
		if(func_num_args() > 0) $this->value = $this->get_value(func_get_arg(0));
		if(strpos($this->value,'<![CDATA[') === 0) return substr($this->value,9,-3);
		return $this->value;
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
	}
	/**
	 * アトリビュートを取得する
	 * @param string $n 取得するアトリビュート名
	 * @param string $d アトリビュートが存在しない場合の代替値
	 * @return string
	 */
	final public function in_attr($n,$d=null){
		return isset($this->attr[strtolower($n)]) ? ($this->esc ? htmlentities($this->attr[strtolower($n)],ENT_QUOTES,'UTF-8') : $this->attr[strtolower($n)]) : (isset($d) ? (string)$d : null);
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
	}
	/**
	 * アトリビュートがあるか
	 * @param string $name
	 * @return boolean
	 */
	final public function is_attr($name){
		return array_key_exists($name,$this->attr);
	}
	/**
	 * アトリビュートを設定
	 * @return self $this
	 */
	final public function attr($key,$value){
		$this->attr[strtolower($key)] = is_bool($value) ? (($value) ? 'true' : 'false') : $value;
		return $this;
	}
	/**
	 * 値の無いアトリビュートを設定
	 * @param string $v
	 */
	final public function plain_attr($v){
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
	 * 文字列からXMLを探す
	 * @param mixed $x 見つかった場合にインスタンスがセットされる
	 * @param string $plain 対象の文字列
	 * @param string $name 要素名
	 * @return boolean
	 */
	static public function set(&$x,$plain,$name=null){
		return self::_set($x,$plain,$name);
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
					$x->value = ($match[4] === '' || $match[4] === null) ? null : $match[4];
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
	 * @return XmlIterator
	 */
	public function in($name,$offset=0,$length=0){
		return new Xml\XmlIterator($name,$this->value(),$offset,$length);
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
				$r->escape(false);
				$r->value(empty($rtag) ? $replace : str_replace($rtag->plain(),$replace,$r->value()));
				$replace = $r->get();
				$rtag = clone($ltag);
			}
			$pe = $this->esc;
			$this->escape(false);
			$this->value(str_replace($ltag->plain(),$replace,$this->value()));
			$this->escape($pe);
			return null;
		}
		return (!empty($last) && substr($last,0,2) == 'in') ? array() : null;
	}
	/**
	 * 置換して新規のインスタンスを返す
	 * @param string $path
	 * @param string $value
	 * @return self
	 */
	public function replace($path,$value){
		$path = str_replace('/','.',$path);
		$xml = clone($this);
		$xml->f($path.'.value()',$value);
		return $xml;
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
