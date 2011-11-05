<?php
namespace org\rhaco;
/**
 * ログ処理
 *
 * @author tokushima
 * @var string $level
 * @var timestamp $time
 * @var string $file
 * @var integer $line
 * @conf string $level ログレベル (none,error,warn,info,debug)
 * @conf boolean $disp 標準出力に出すか
 */
class Log extends \org\rhaco\Object{
	static private $stdout = true;
	static private $level_strs = array('none','error','warn','info','debug');
	static private $logs = array();
	static private $id;
	static private $current_level;
	static private $disp;

	protected $level; # ログのレベル
	protected $time; # 発生時間
	protected $file; # 発生したファイル名
	protected $line; # 発生した行
	protected $value; # 内容

	static public function __import__(){
		self::$id = uniqid('');
		self::$logs[] = new self(4,'--- logging start '
									.date('Y-m-d H:i:s')
									.' ( '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null)).' )'
									.' { '.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null).' }'
								.' --- ');
	}
	static public function __shutdown__(){
		if(self::cur_level() >= 4){
			if(function_exists('memory_get_usage')){
				self::$logs[] = new self(4,sprintf('--- end logger ( %s MByte) --- ',round(number_format((memory_get_usage() / 1024 / 1024),3),2)));
			}
		}
		if(self::cur_level() >= 2){
			foreach(\org\rhaco\Exceptions::gets() as $e) self::$logs[] = new self(2,$e);
		}
		self::flush();
	}
	static private function cur_level(){
		if(self::$current_level === null) self::$current_level = array_search(\org\rhaco\Conf::get('level','none'),self::$level_strs);
		return self::$current_level;
	}
	static private function disp(){
		if(self::$disp === null) self::$disp = (boolean)\org\rhaco\Conf::get('disp',false);
		return self::$disp;
	}
	final protected function __new__($level,$value,$file=null,$line=null,$time=null){
		$class = null;
		if($file === null){
			$debugs = debug_backtrace(false);
			if(sizeof($debugs) > 4){
				list($dumy,$dumy,$dumy,$debug,$op) = $debugs;
			}else{
				list($dumy,$debug) = $debugs;
			}
			$file = (isset($debug['file']) ? $debug['file'] : $dumy['file']);
			$line = (isset($debug['line']) ? $debug['line'] : $dumy['line']);
			$class = (isset($op['class']) ? $op['class'] : $dumy['class']);
		}
		$this->level = $level;
		$this->file = $file;
		$this->line = intval($line);
		$this->time = ($time === null) ? time() : $time;
		$this->class = $class;
		$this->value = (is_object($value)) ? 
							(($value instanceof \Exception) ? 
								array_merge(array($value->getMessage()),array($value->getTraceAsString()))
								: clone($value)
							)
							: $value;
	}
	protected function __fm_value__(){
		if(!is_string($this->value)){
			ob_start();
				var_dump($this->value);
			return ob_get_clean();
		}
		return $this->value;
	}
	protected function __fm_level__(){
		return self::$level_strs[$this->level()];
	}
	protected function __get_time__($format='Y/m/d H:i:s'){
		return (empty($format)) ? $this->time : date($format,$this->time);
	}
	protected function __str__(){
		return '['.$this->time().']'.'['.self::$id.']'.'['.$this->fm_level().']'.':['.$this->file().':'.$this->line().']'.' '.$this->fm_value();
	}
	/**
	 * 格納されたログを出力する
	 */
	final static public function flush(){
		if(!empty(self::$logs)){
			foreach(self::$logs as $log){
				if(self::cur_level() >= $log->level()){
					if(self::disp() && self::$stdout) print(((string)$log).PHP_EOL);
					switch($log->fm_level()){
						/**
						 * debugログの場合の処理
						 * @param self $log
						 * @param string $id
						 */
						case 'debug': self::module('debug',$log,self::$id); break;
						/**
						 * infoログの場合の処理
						 * @param self $log
						 * @param string $id
						 */
						case 'info': self::module('info',$log,self::$id); break;
						/**
						 * warnログの場合の処理
						 * @param self $log
						 * @param string $id
						 */
						case 'warn': self::module('warn',$log,self::$id); break;
						/**
						 * errorログの場合の処理
						 * @param self $log
						 * @param string $id
						 */
						case 'error': self::module('error',$log,self::$id); break;
					}
				}
			}
		}
		/**
		 * フラッシュ時の処理
		 * @param self[] $logs
		 * @param string $id
		 * @param boolean $stdout 標準出力に出力するか
		 */
		self::module('flush',self::$logs,self::$id,self::$stdout);
		/**
		 * フラッシュの後処理
		 * @param string $id
		 */
		self::module('after_flush',self::$id);
		self::$logs = array();
	}
	/**
	 * 一時的に無効にされた標準出力へのログ出力を有効にする
	 * ログのモードに依存する
	 */
	static public function enable_display(){
		self::debug('log stdout on');
		self::$stdout = true;
	}

	/**
	 * 標準出力へのログ出力を一時的に無効にする
	 */
	static public function disable_display(){
		self::debug('log stdout off');
		self::$stdout = false;
	}
	/**
	 * 標準出力へのログ可不可
	 * @return boolean
	 */
	static public function is_display(){
		return self::$stdout;
	}
	/**
	 * errorを生成
	 * @param mixed $value 内容
	 */
	static public function error(){
		if(self::cur_level() >= 1){
			foreach(func_get_args() as $value) self::$logs[] = new self(1,$value);
		}
	}
	/**
	 * warnを生成
	 * @param mixed $value 内容
	 */
	static public function warn($value){
		if(self::cur_level() >= 2){
			foreach(func_get_args() as $value) self::$logs[] = new self(2,$value);
		}
	}
	/**
	 * infoを生成
	 * @param mixed $value 内容
	 */
	static public function info($value){
		if(self::cur_level() >= 3){
			foreach(func_get_args() as $value) self::$logs[] = new self(3,$value);
		}
	}
	/**
	 * debugを生成
	 * @param mixed $value 内容
	 */
	static public function debug($value){
		if(self::cur_level() >= 4){
			foreach(func_get_args() as $value) self::$logs[] = new self(4,$value);
		}
	}
	/**
	 * var_dumpで出力する
	 * @param mixed $value 内容
	 */
	static public function d($value){
		list($debug_backtrace) = debug_backtrace(false);
		$args = func_get_args();
		var_dump(array_merge(array($debug_backtrace['file'].':'.$debug_backtrace['line']),$args));
	}
}