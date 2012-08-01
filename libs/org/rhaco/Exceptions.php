<?php
namespace org\rhaco;
/**
 * 例外
 * @author tokushima
 * @var integer $line
 * @var string $code
 * @var string $file
 * @var text $message
 */
class Exceptions extends \org\rhaco\Exception{
	static private $self;
	protected $id;
	private $messages = array();

	/**
	 * 現在の例外群を返す
	 * @return self
	 */
	static public function trace(){
		return (isset(self::$self) ? self::$self : new self());
	}
	/**
	 * ID
	 * @return string
	 */
	static public function id(){
		return (self::$self !== null) ? self::$self->id : '000000';
	}
	/**
	 * IDの復元
	 * @param string $id
	 */
	static public function parse_id($id){
		return sprintf('%04d, %02d: %s',base_convert(substr($id,0,2),36,10),hexdec(substr($id,2,1)),substr($id,3,3));
	}
	/**
	 * Exceptionを追加する
	 * @param Exception $exception 例外
	 * @param string $group グループ名
	 */
	static public function add(\Exception $exception,$group=null){
		if(self::$self === null){
			$self = new self('multiple exceptions');
			$self->id = strtoupper(base_convert(date('md'),10,36).base_convert(date('G'),10,36).base_convert(mt_rand(1296,46655),10,36));
			self::$self = $self;
		}
		if($exception instanceof self){
			foreach($exception->messages as $key => $es){
				foreach($es as $e) self::$self->messages[$key][] = $e;
			}
		}else{
			if(empty($group)) $group = ($exception instanceof \org\rhaco\Exception) ? $exception->getGroup() : 'exceptions';
			if(is_object($group)) $group = str_replace("\\",'.',get_class($group));
			self::$self->messages[$group][] = $exception;
		}
	}
	/**
	 * 追加されたExceptionのクリア
	 */
	static public function clear(){
		self::$self = null;
	}
	/**
	 * 追加されたExceptionからメッセージ配列を取得
	 * @param string $group グループ名
	 * @return string[]
	 */
	static public function messages($group=null){
		$result = array();
		foreach(self::gets($group) as $m) $result[] = $m->getMessage();
		return $result;
	}
	/**
	 * 追加されたExceptionからException配列を取得
	 * @param string $group グループ名
	 * @return Exception[]
	 */
	static public function gets($group=null){
		if(!self::has($group)) return array();
		if(!empty($group)) return self::$self->messages[$group];
		$result = array();
		foreach(self::$self->messages as $k => $exceptions) $result = array_merge($result,$exceptions);
		return $result;
	}
	/**
	 * 追加されたグループ名一覧
	 * @return string[]
	 */
	static public function groups(){
		if(!self::has()) return array();
		return array_keys(self::$self->messages);
	}
	/**
	 * Exceptionが追加されているか
	 * @param string $group グループ名
	 * @return boolean
	 */
	static public function has($group=null){
		return (isset(self::$self) && ((empty($group) && !empty(self::$self->messages)) || (!empty($group) && isset(self::$self->messages[$group]))));
	}
	/**
	 * Exceptionが追加されていればthrowする
	 * @param string $group グループ名
	 */
	static public function throw_over($group=null){
		if(self::has($group)) throw self::$self;
	}
	/**
	 * (non-PHPdoc)
	 * @see Exception::__toString()
	 */
	public function __toString(){
		if(self::$self === null || empty(self::$self->messages)) return null;
		$exceptions = self::gets();
		return count($exceptions).' exceptions [#'.self::$self->id.']: '
				.PHP_EOL.implode(PHP_EOL.PHP_EOL,array_map(function($e){ return (string)$e; },$exceptions));
	}
}