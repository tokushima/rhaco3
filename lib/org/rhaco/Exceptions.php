<?php
namespace org\rhaco;
/**
 * 例外の集合
 * @author tokushima
 * @var integer $line
 * @var string $code
 * @var string $file
 * @var text $message
 */
class Exceptions extends \org\rhaco\Exception implements \Iterator{
	static private $self;
	private $messages = array();
	private $pos = 0;
	private $g = null;

	public function rewind(){
		$this->pos = 0;
	}
	public function current(){
		return $this->messages[$this->pos]['exception'];
	}
	public function key(){
		return $this->messages[$this->pos]['group'];
	}
	public function valid(){
		while($this->pos < sizeof($this->messages)){
			if(empty($this->g) || $this->messages[$this->pos]['group'] == $this->g){
				return true;
			}
			$this->pos++;
		}
		return false;
	}
	public function next(){
		$this->pos++;
	}
	
	/**
	 * Exceptionを追加する
	 * @param Exception $exception 例外
	 * @param string $group グループ名
	 */
	static public function add(\Exception $exception,$group=''){
		if(self::$self === null) self::$self = new self();
		self::$self->messages[] = array('exception'=>$exception,'group'=>$group);
		self::$self->message = (empty(self::$self->message) ? '' : PHP_EOL).$exception->getMessage();
		return self::$self;
	}
	/**
	 * 追加されたExceptionのクリア
	 */
	static public function clear(){
		self::$self = null;
	}
	/**
	 * Exceptionが追加されているか
	 * @param string $group グループ名
	 * @return boolean
	 */
	static public function has($group=null){
		if(self::$self === null) return false;
		if(empty($group)) return !empty(self::$self->messages);
		foreach(self::$self->messages as $e){
			if($e['group'] == $group) return true;
		}
		return false;
	}
	/**
	 * Exceptionが追加されていればthrowする
	 */
	static public function throw_over(){
		if(!empty(self::$self->messages)) throw self::$self;
	}
	/**
	 * 追加されたExceptionからException配列を取得
	 * @param string $group グループ名
	 * @return Exception[]
	 */
	static public function gets($group=null){
		if(!self::has($group)) return array();
		self::$self->g = $group;
		return self::$self;
	}
}