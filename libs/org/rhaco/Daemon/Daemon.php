<?php
namespace org\rhaco;
/**
 * デーモン
 * @author tokushima
 * @incomplete
 */
class Daemon{
	static protected $state = true;
	static protected $pid;
	static protected $pid_file;
	static protected $child = array();
	static protected $signal_list = array(
					SIGHUP=>array('SIGHUP','terminal line hangup'),
					SIGINT=>array('SIGINT','interrupt program'),
					SIGQUIT=>array('SIGQUIT','quit program'),
					SIGILL=>array('SIGILL','illegal instruction'),
					SIGTRAP=>array('SIGTRAP','trace trap'),
					SIGABRT=>array('SIGABRT','abort program'),
					SIGBUS=>array('SIGBUS','bus error'),
					SIGSEGV=>array('SIGSEGV','segmentation violation'),
					SIGPIPE=>array('SIGPIPE','write on a pipe with no reader'),
					SIGALRM=>array('SIGALRM','real-time timer expired'),
					SIGTERM=>array('SIGTERM','software termination signal'),
					SIGURG=>array('SIGURG','urgent condition present on socket'),
					SIGCONT=>array('SIGCONT','continue after stop'),
					SIGCHLD=>array('SIGCHLD','child status has changed'),
					SIGXCPU=>array('SIGXCPU','cpu time limit exceeded'),
					SIGXFSZ=>array('SIGXFSZ','file size limit exceeded'),
					SIGVTALRM=>array('SIGVTALRM','virtual time alarm'),
					SIGPROF=>array('SIGPROF','profiling timer alarm'),
					SIGUSR1=>array('SIGUSR1','User defined signal 1'),
					SIGUSR2=>array('SIGUSR2','User defined signal 2'),
				);
	
	final public function __construct(){
	}
	static protected function signal_func($signal){
		switch($signal){
			case SIGCHLD:
				while(pcntl_wait($status,WNOHANG|WUNTRACED) > 0) usleep(1000);
				break;
			case SIGHUP:
			case SIGINT:
			case SIGQUIT:
			case SIGABRT:
			case SIGALRM:
			case SIGTERM:
				if(!empty(self::$pid_file) && is_file(self::$pid_file)) @unlink(self::$pid_file);
			default:
				self::$state = false;
		}
		$signal_name = strtolower(self::$signal_list[$signal][0]);
		$re = new \ReflectionClass(new static);
		foreach($re->getMethods(\ReflectionMethod::IS_STATIC) as $m){
			if($m->getName() == $signal_name){
				call_user_func(array($re->getName(),$signal_name));
				break;
			}
		}
	}
	static public function stop($pid_file){
		if((php_sapi_name() !== 'cli')) return;
		if(!extension_loaded('pcntl')) throw new \Exception('require pcntl module');
		if(!is_file($pid_file)) throw new \Exception($pid_file.' not found');
		$pid = (int)file_get_contents($pid_file);
		posix_kill($pid,SIGTERM);
		if(posix_kill($pid,0)) new \Exception('stop fail');
		if(is_file($pid_file)) unlink($pid_file);
		print('stop PID:'.$pid.PHP_EOL);
	}
	final static public function start($php_path,$pid_file=null,$max=1,$wait=0){
		if((php_sapi_name() !== 'cli')) return;
		if(!extension_loaded('pcntl')) throw new \Exception('require pcntl module');
		if(!is_file($php_path)) throw new \Exception($php_path.' not found');
		if(!empty($pid_file)){
			self::$pid_file = $pid_file;
			if(is_file($pid_file)){
				if(posix_kill((int)file_get_contents($pid_file),0)) throw new \Exception('started PID:'.(int)file_get_contents($pid_file));
				@unlink($pid_file);
			}
			if(!is_dir(dirname($pid_file)) || false === file_put_contents($pid_file,'')) throw new \Exception('permission denied '.$pid_file);
		}
		gc_enable();
		umask(0);
		clearstatcache();

		declare(ticks=1){
			if(!empty(self::$pid_file)){
				if(pcntl_fork() !== 0) return;
				posix_setsid();
			}
			foreach(self::$signal_list as $sig => $dec) pcntl_signal($sig,array(__CLASS__,'signal_func'));
			self::$pid = posix_getpid();

			if(!empty(self::$pid_file)){
				file_put_contents(self::$pid_file,self::$pid);
			}else{
				printf('Started process PID:%d, CMD:%s'.PHP_EOL,self::$pid,$php_path);
			}
			while(true){
				if(self::$state === false) break;
				$pid = pcntl_fork();
				self::$child[$pid] = true;
				
				if($pid === -1) throw new \Exception('Unable to fork');
				if($pid === 0){
					$pid = posix_getpid();	
					pcntl_exec(isset($_ENV['_']) ? $_ENV['_'] : '/usr/bin/php',array($php_path,self::$pid,$pid));
				}
				if(sizeof(self::$child) >= $max){
					$exist_pid = pcntl_wait($status);
					if(isset(self::$child[$exist_pid])) unset(self::$child[$exist_pid]);
					if(pcntl_wifexited($status)){}
				}
				if($wait > 0) usleep($wait * 1000000);
				clearstatcache();
			}
			if(!empty(self::$pid_file) && is_file(self::$pid_file)) @unlink(self::$pid_file);
		}
	}
}
