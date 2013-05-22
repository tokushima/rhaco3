<?php
namespace org\rhaco;
/**
 * ログ処理
 *
 * @author tokushima
 * @var string $level ログのレベル
 * @var timestamp $time 発生時間
 * @var string $file 発生したファイル名
 * @var integer $line 発生した行
 * @var mixed $value 内容
 * @conf string $level ログレベル (none,error,warn,info,debug)
 * @conf boolean $stdout 標準出力に出すか
 */
class Log extends \org\rhaco\Object{
	static private $level_strs = array('none','error','warn','info','debug');
	static private $logs = array();
	static private $id;
	static private $current_level;
	static private $disp = true;
	static private $logfile;

	protected $level;
	protected $time;
	protected $file;
	protected $line;
	protected $value;
	
	final protected function __new__($level,$value,$file=null,$line=null,$time=null){
		$class = null;
			if($file === null){
			$debugs = debug_backtrace(false);
			$i = 0;
			rsort($debugs);
			foreach($debugs as $d){
				if(isset($d['file'])){
					$file = $d['file'];
					$line = $d['line'];
					$class = isset($d['class']) ? $d['class'] : null;
					if($i++ >= 1) break;
				}
			}
		}
		$this->level = $level;
		$this->file = $file;
		$this->line = intval($line);
		$this->time = ($time === null) ? time() : $time;
		$this->class = $class;
		$this->value = (is_object($value)) ? 
							(($value instanceof \Exception) ? 
								(string)$value
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
		return ($this->level() >= 0) ? self::$level_strs[$this->level()] : 'trace';
	}
	protected function __get_time__($format='Y/m/d H:i:s'){
		return (empty($format)) ? $this->time : date($format,$this->time);
	}
	protected function __str__(){
		return '['.date('Y-m-d H:i:s',$this->time).']'.'['.self::$id.']'.'['.$this->fm_level().']'.':['.$this->file().':'.$this->line().']'.' '.$this->fm_value();
	}
	static private function cur_level(){
		if(!isset(self::$id)){
			self::$id = base_convert(date('md'),10,36).base_convert(date('G'),10,36).base_convert(mt_rand(1296,46655),10,36);
			register_shutdown_function(array(__CLASS__,'flush'));
			
			self::$logfile = \org\rhaco\Conf::get('file');
			if(!empty(self::$logfile)){
				if(!is_dir($dir = dirname(self::$logfile)) && (@mkdir($dir,0777,true) == false)){
					self::$logfile = '';
				}
				if(!empty(self::$logfile) && @file_put_contents(self::$logfile,'',FILE_APPEND) === false){
					self::$logfile = '';
				}
			}
		}
		if(self::$current_level === null) self::$current_level = array_search(\org\rhaco\Conf::get('level','none'),self::$level_strs);
		return self::$current_level;
	}
	/**
	 * 格納されたログを出力する
	 */
	final static public function flush(){
		if(!empty(self::$logs)){
			$stdout = \org\rhaco\Conf::get('stdout',false);

			foreach(self::$logs as $log){
				if(self::cur_level() >= $log->level()){
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
						default:
						/**
						 * traceログの場合の処理
						 * @param self $log
						 * @param string $id
						 */
						self::module('trace',$log,self::$id);
					}
					if(!empty(self::$logfile) && is_file(self::$logfile)) file_put_contents(self::$logfile,((string)$log).PHP_EOL,FILE_APPEND);
					if(self::$disp === true && $stdout) print(((string)$log).PHP_EOL);
				}
			}
			/**
			 * フラッシュ時の処理
			 * @param self[] $logs
			 * @param string $id
			 * @param boolean $stdout 標準出力に出力するか
			 */
			self::module('flush',self::$logs,self::$id,$stdout);
			self::$logs = array();
		}
	}
	/**
	 * 一時的に無効にされた標準出力へのログ出力を有効にする
	 * ログのモードに依存する
	 */
	static public function enable_display(){
		self::debug('log stdout on');
		self::$disp = true;
	}

	/**
	 * 標準出力へのログ出力を一時的に無効にする
	 */
	static public function disable_display(){
		self::debug('log stdout off');
		self::$disp = false;
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
	 * traceを生成
	 * @param mixed $value 内容
	 */
	static public function trace($value){
		if(self::cur_level() >= -1){
			foreach(func_get_args() as $value) self::$logs[] = new self(-1,$value);
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