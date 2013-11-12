<?php
namespace org\rhaco;
/**
 * 基底クラス
 * @author tokushima
 */
class Object implements \IteratorAggregate{
	static private $_m = array(array(),array(),array());
	private $_im = array(array(),array());
	protected $_;

	public function getIterator(){
		$r = array();
		foreach($this->props(false) as $n => $null){
			if($this->prop_anon($n,'get') !== false && $this->prop_anon($n,'hash') !== false){
				switch($this->prop_anon($n,'type')){
					case 'boolean': $r[$n] = $this->{$n}(); break;
					default: $r[$n] = $this->{'fm_'.$n}();
				}
			}
		}
		return new \ArrayIterator($r);
	}
	/**
	 * クラスのアノテーションを取得する
	 * @param string $n アノテーション名
	 * @param mixed $df デフォルト値
	 * @return mixed
	 */
	final static public function anon($n,$df=null){
		$c = get_called_class();
		if(!isset(self::$_m[1][$c])){
			$d = '';
			$r = new \ReflectionClass($c);
			while($r->getName() != __CLASS__){
				$d = $r->getDocComment().$d;
				$r = $r->getParentClass();
			}
			self::$_m[1][$c] = self::anon_decode($d,'class');
		}
		return isset(self::$_m[1][$c][$n]) ? self::$_m[1][$c][$n] : $df;
	}
	/**
	 * アノテーション文字列をデコードする
	 * @param text $d デコード対象となる文字列
	 * @param string $name デコード対象のアノテーション名
	 * @param string $ns_name 型宣言を取得する場合の名前空間
	 * @param string $doc_name 説明を取得する場合の添字
	 * @throws \InvalidArgumentException
	 */
	final static public function anon_decode($d,$name,$ns_name=null,$doc_name=null){
		$result = array();
		$decode_func = function($s){
			if(empty($s)) return array();
			if(PHP_MAJOR_VERSION > 5 || PHP_MINOR_VERSION > 3){
				$d = @eval('return '.$s.';');
			}else{
				if(preg_match_all('/([\"\']).+?\\1/',$s,$m)){
					foreach($m[0] as $v) $s = str_replace($v,str_replace(array('[',']'),array('#{#','#}#'),$v),$s);
				}
				$d = @eval('return '.str_replace(array('[',']','#{#','#}#'),array('array(',')','[',']'),$s).';');
			}
			if(!is_array($d)) throw new \InvalidArgumentException('annotation error : `'.$s.'`');
			return $d;
		};
		if($ns_name !== null && preg_match_all("/@".$name."\s([\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$d,$m)){
			foreach($m[2] as $k => $n){
				$as = (false !== ($s=strpos($m[3][$k],'@['))) ? substr($m[3][$k],$s+1,strrpos($m[3][$k],']')-$s) : null;
				$decode = $decode_func($as);
				$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;

				if(!empty($doc_name)) $result[$n][$doc_name] = ($s===false) ? $m[3][$k] : substr($m[3][$k],0,$s);
				list($result[$n]['type'],$result[$n]['attr']) = (false != ($h = strpos($m[1][$k],'{}')) || false !== ($l = strpos($m[1][$k],'[]'))) ? array(substr($m[1][$k],0,-2),(isset($h) && $h !== false) ? 'h' : 'a') : array($m[1][$k],null);
				if(!ctype_lower($t=$result[$n]['type'])){
					if($t[0]!='\\') $t='\\'.$t;
					if(!class_exists($t=str_replace('.','\\',$t)) && !class_exists($t='\\'.$ns_name.$t)) throw new \InvalidArgumentException($t.' '.$result[$n]['type'].' not found');
					$result[$n]['type'] = (($t[0] !== '\\') ? '\\' : '').str_replace('.','\\',$t);
				}
			}
		}else if(preg_match_all("/@".$name."\s.*@(\[.*\])/",$d,$m)){
			foreach($m[1] as $j){
				$decode = $decode_func($j);
				$result = array_merge($result,$decode);
			}
		}
		return $result;
	}
	final public function __construct(){
		$c = get_class($this);
		if(!isset(self::$_m[0][$c])){
			self::$_m[0][$c] = array();
			$d = null;
			$t = new \ReflectionClass($this);
			$ns = $t->getNamespaceName();
			while($t->getName() != __CLASS__){
				$d = $t->getDocComment().$d;
				$t = $t->getParentClass();
			}
			$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$d));
			self::$_m[0][$c] = self::anon_decode($d,'var',$ns);
			foreach(array_keys(self::$_m[0][$c]) as $n){
				if(self::$_m[0][$c][$n]['type'] == 'serial'){
					self::$_m[0][$c][$n]['primary'] = true;
				}else if(self::$_m[0][$c][$n]['type'] == 'choice' && method_exists($this,'__choices_'.$n.'__')){
					self::$_m[0][$c][$n]['choices'] = $this->{'__choices_'.$n.'__'}();
				}
			}
			if(method_exists($this,'__anon__')) $this->__anon__($d);
		}
		if(method_exists($this,'__new__')){
			$args = func_get_args();
			call_user_func_array(array($this,'__new__'),$args);
		}
		if(method_exists($this,'__init__')) $this->__init__();
	}
	final public function __call($n,$args){
		if($n[0] != '_'){
			list($c,$p) = (in_array($n,array_keys(get_object_vars($this)))) ? array((empty($args) ? 'get' : 'set'),$n) : (preg_match("/^([a-z]+)_([a-zA-Z].*)$/",$n,$m) ? array($m[1],$m[2]) : array(null,null));
			if(method_exists($this,$am=('___'.$c.'___'))){
				$this->_ = $p;
				return call_user_func_array(array($this,(method_exists($this,$m=('__'.$c.'_'.$p.'__')) ? $m : $am)),$args);
			}
		}
		throw new \ErrorException(get_class($this).'::'.$n.' method not found');
	}
	final public function __destruct(){
		if(method_exists($this,'__del__')) $this->__del__();
	}
	final public function __toString(){
		return (method_exists($this,'__str__')) ? (string)$this->__str__() : get_class($this);
	}
	/**
	 * クラスモジュールを追加する
	 * @param object $o
	 */
	final static public function set_module($o){
		if(is_array($o)){
			foreach($o as $c => $plugins){
				$r = new \ReflectionClass('\\'.str_replace('.','\\',$c));
				if($r->isSubclassOf(__CLASS__)){
					foreach($plugins as $p) call_user_func_array(array($r->getName(),'set_module'),array($p));
				}
			}
		}else{
			self::$_m[2][get_called_class()][] = $o;
		}
	}
	/**
	 * 指定のクラスモジュールを実行する
	 * @param string $n
	 * @return mixed
	 */
	final static protected function module($n){
		$r = null;
		if(isset(self::$_m[2][$g=get_called_class()])){
			$a = func_get_args();
			array_shift($a);
			
			foreach(self::$_m[2][$g] as $k => $o){
				if(!is_object($o) && class_exists(($c='\\'.str_replace('.','\\',$o)))) self::$_m[2][$g][$k] = $o = new $c();
				if(method_exists($o,$n)) $r = call_user_func_array(array($o,$n),$a);
			}
		}
		return $r;
	}
	/**
	 * 指定のクラスモジュールが存在するか
	 * @param string $n
	 * @return boolean
	 */
	final static protected function has_module($n){
		foreach((isset(self::$_m[2][$g=get_called_class()]) ? self::$_m[2][$g] : array()) as $k => $o){
			if(!is_object($o) && class_exists(($c='\\'.str_replace('.','\\',$o)))) self::$_m[2][$g][$k] = $o = new $c();
			if(method_exists($o,$n)) return true;
		}
		return false;
	}
	/**
	 * インスタンスモジュールを追加する
	 * @param object $o
	 * @return mixed
	 */
	final public function set_object_module($o){
		$this->_im[1][] = $o;
		return $this;
	}
	/**
	 * 
	 * 指定のインスタンスモジュールを実行する
	 * @param string $n
	 * @return mixed
	 */
	final protected function object_module($n){
		$r = null;
		$a = func_get_args();
		array_shift($a);
		foreach($this->_im[1] as $o){
			if(method_exists($o,$n)) $r = call_user_func_array(array($o,$n),$a);
		}
		return $r;
	}
	/**
	 * 指定のインスタンスモジュールが存在するか
	 * @param string $n
	 * @return boolean
	 */
	final protected function has_object_module($n){
		if(isset($this->_im[1])){
			foreach($this->_im[1] as $o){
				if(method_exists($o,$n)) return true;
			}
		}
		return false;
	}
	/**
	 * プロパティのアノテーションを取得する
	 * @param string $p プロパティ名
	 * @param string $n アノテーション名
	 * @param mixed $d デフォルト値
	 * @parama boolean $f 値をデフォルト値で上書きするか
	 * @return mixed
	 */
	final public function prop_anon($p,$n,$d=null,$f=false){
		if($f) $this->_im[0][$p][$n] = $d;
		$v = isset($this->_im[0][$p][$n]) ? $this->_im[0][$p][$n] : ((isset(self::$_m[0][get_class($this)][$p][$n])) ? self::$_m[0][get_class($this)][$p][$n] : $d);
		if(is_string($v) && $d !== $v) $v = preg_replace('/array\((.+?)\)/','[\\1]',$v);
		return $v;
	}
	/**
	 * アクセス可能なプロパティを取得する
	 * @param boolean $in_value
	 * @return mixed{}
	 */
	final public function props($in_value=true){
		$r = array();
		foreach(get_object_vars($this) as $n => $v){
			if($n[0] != '_') $r[$n] = ($in_value) ? $this->{$n}() : null;
		}
		return $r;
	}
	final private function ___get___(){
		if($this->prop_anon($this->_,'get') === false) throw new \InvalidArgumentException('not permitted');
		if($this->prop_anon($this->_,'attr') !== null) return (is_array($this->{$this->_})) ? $this->{$this->_} : (is_null($this->{$this->_}) ? array() : array($this->{$this->_}));
		return $this->{$this->_};
	}
	final private function ___set___($v){
		if($this->prop_anon($this->_,'set') === false) throw new \InvalidArgumentException('not permitted');
		$t = $this->prop_anon($this->_,'type');
		switch($this->prop_anon($this->_,'attr')){
			case 'a':
				foreach(func_get_args() as $a) $this->{$this->_}[] = $this->set_prop($this->_,$t,$a);
				break;
			case 'h':
				$v = (func_num_args() === 2) ? array(func_get_arg(0)=>func_get_arg(1)) : (is_array($v) ? $v : array((string)$v=>$v));
				foreach($v as $k => $a) $this->{$this->_}[$k] = $this->set_prop($this->_,$t,$a);
				break;
			default:
				$this->{$this->_} = $this->set_prop($this->_,$t,$v);
		}
		return $this;
	}
	/**
	 * プロパティに値をセットする
	 * @param string $name
	 * @param string $type
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	protected function set_prop($name,$type,$value){
		try{
			return $this->_set_value($type,$value);
		}catch(\InvalidArgumentException $e){
			throw new \InvalidArgumentException($this->_.' must be an '.$type);
		}
	}
	final private function _set_value($t,$v){
		if($v === null) return null;
		switch($t){
			case null: return $v;
			case 'string':
			case 'text':
				if(is_array($v)) throw new \InvalidArgumentException();
				$v =is_bool($v) ? (($v) ? 'true' : 'false') : ((string)$v);
				return ($t == 'text') ? $v : str_replace(array("\r\n","\r","\n"),'',$v);
			default:
				if($v === '') return null;
				switch($t){
					case 'number':
						if(!is_numeric($v)) throw new \InvalidArgumentException();
						$dp = $this->prop_anon($this->_,'decimal_places');
						return (float)(isset($dp) ? (floor($v * pow(10,$dp)) / pow(10,$dp)) : $v);
					case 'serial':
					case 'integer':
						if(!is_numeric($v) || (int)$v != $v) throw new \InvalidArgumentException();
						return (int)$v;
					case 'boolean':
						if(is_string($v)){ $v = ($v === 'true' || $v === '1') ? true : (($v === 'false' || $v === '0') ? false : $v);
						}else if(is_int($v)){ $v = ($v === 1) ? true : (($v === 0) ? false : $v); }
						if(!is_bool($v)) throw new \InvalidArgumentException();
						return (boolean)$v;
					case 'timestamp':
					case 'date':
						if(ctype_digit((string)$v) || (substr($v,0,1) == '-' && ctype_digit(substr($v,1)))) return (int)$v;
						if(preg_match('/^0+$/',preg_replace('/[^\d]/','',$v))) return null;
						$time = strtotime($v);
						if($time === false) throw new \InvalidArgumentException();
						return $time;
					case 'time':
						if(is_numeric($v)) return $v;
						$d = array_reverse(preg_split("/[^\d\.]+/",$v));
						if($d[0] === '') array_shift($d);
						list($s,$m,$h) = array((isset($d[0]) ? (float)$d[0] : 0),(isset($d[1]) ? (float)$d[1] : 0),(isset($d[2]) ? (float)$d[2] : 0));
						if(sizeof($d) > 3 || $m > 59 || $s > 59 || strpos($h,'.') !== false || strpos($m,'.') !== false) throw new \InvalidArgumentException();
						return ($h * 3600) + ($m*60) + ((int)$s) + ($s-((int)$s));
					case 'intdate':
						if(preg_match("/^\d\d\d\d\d+$/",$v)){
							$v = sprintf('%08d',$v);
							list($y,$m,$d) = array((int)substr($v,0,-4),(int)substr($v,-4,2),(int)substr($v,-2,2));
						}else{
							$x = preg_split("/[^\d]+/",$v);
							if(sizeof($x) < 3) throw new \InvalidArgumentException();
							list($y,$m,$d) = array((int)$x[0],(int)$x[1],(int)$x[2]);
						}
						if($m < 1 || $m > 12 || $d < 1 || $d > 31 || (in_array($m,array(4,6,9,11)) && $d > 30) || (in_array($m,array(1,3,5,7,8,10,12)) && $d > 31)
							|| ($m == 2 && ($d > 29 || (!(($y % 4 == 0) && (($y % 100 != 0) || ($y % 400 == 0)) ) && $d > 28)))
						) throw new \InvalidArgumentException();
						return (int)sprintf('%d%02d%02d',$y,$m,$d);
					case 'email':
						$v = trim($v);
						if(!preg_match('/^[\w\''.preg_quote('./!#$%&*+-=?^_`{|}~','/').']+@(?:[A-Z0-9-]+\.)+[A-Z]{2,6}$/i',$v) 
							|| strlen($v) > 255 || strpos($v,'..') !== false || strpos($v,'.@') !== false || $v[0] === '.') throw new \InvalidArgumentException();
						return $v;
					case 'alnum':
						if(!ctype_alnum(str_replace('_','',$v))) throw new \InvalidArgumentException();
						return $v;
					case 'choice':
						$v = is_bool($v) ? (($v) ? 'true' : 'false') : $v;
						$ch = $this->prop_anon($this->_,'choices');
						if(!isset($ch) || !in_array($v,$ch,true)) throw new \InvalidArgumentException();
						return $v;
					case 'mixed': return $v;
					default:
						if(!($v instanceof $t)) throw new \InvalidArgumentException();
						return $v;
				}
		}
	}
	final private function ___rm___(){
		if($this->prop_anon($this->_,'set') === false) throw new \InvalidArgumentException('not permitted');
		if($this->prop_anon($this->_,'attr') === null){
			$this->{$this->_} = null;
		}else{
			if(func_num_args() == 0){
				$this->{$this->_} = array();
			}else{
				foreach(func_get_args() as $k) unset($this->{$this->_}[$k]);
			}
		}
	}
	final private function ___fm___($f=null,$d=null){
		$p = $this->_;
		$v = (method_exists($this,$m=('__get_'.$p.'__'))) ? call_user_func(array($this,$m)) : $this->___get___();
		switch($this->prop_anon($p,'type')){
			case 'timestamp': return ($v === null) ? null : (date((empty($f) ? 'Y/m/d H:i:s' : $f),(int)$v));
			case 'date': return ($v === null) ? null : (date((empty($f) ? 'Y/m/d' : $f),(int)$v));
			case 'time':
				if($v === null) return 0;
				$h = floor($v / 3600);
				$i = floor(($v - ($h * 3600)) / 60);
				$s = floor($v - ($h * 3600) - ($i * 60));
				$m = str_replace(' ','0',rtrim(str_replace('0',' ',(substr(($v - ($h * 3600) - ($i * 60) - $s),2,12)))));
				return (($h == 0) ? '' : $h.':').(sprintf('%02d:%02d',$i,$s)).(($m == 0) ? '' : '.'.$m);
			case 'intdate': if($v === null) return null;
							return str_replace(array('Y','m','d'),array(substr($v,0,-4),substr($v,-4,2),substr($v,-2,2)),(empty($f) ? 'Y/m/d' : $f));
			case 'boolean': return ($v) ? (isset($d) ? $d : 'true') : (empty($f) ? 'false' : $f);
		}
		return $v;
	}
	final private function ___ar___($i=null,$j=null){
		$v = $this->___get___();
		$a = is_array($v) ? $v : (($v === null) ? array() : array($v));
		if(isset($i)){
			$c = 0;
			$l = ((isset($j) ? $j : sizeof($a)) + $i);
			$r = array();
			foreach($a as $k => $p){
				if($i <= $c && $l > $c) $r[$k] = $p;
				$c++;
			}
			return $r;
		}
		return $a;
	}
	final private function ___in___($k=null,$d=null){
		$v = $this->___get___();
		return (isset($k)) ? ((is_array($v) && isset($v[$k]) && $v[$k] !== null) ? $v[$k] : $d) : $d;
	}
	final private function ___is___($k=null){
		$v = $this->___get___();
		if($this->prop_anon($this->_,'attr') !== null){
			if($k === null) return !empty($v);
			$v = isset($v[$k]) ? $v[$k] : null;
		}
		switch($this->prop_anon($this->_,'type')){
			case 'string':
			case 'text': return (isset($v) && $v !== '');
		}
		return (boolean)(($this->prop_anon($this->_,'type') == 'boolean') ? $v : isset($v));
	}
}
