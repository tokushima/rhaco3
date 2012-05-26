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
	public function main(){
		
	}
	static protected function signal_func($signal){
		switch($signal){
			case SIGCHLD:
				while(pcntl_wait($status,WNOHANG|WUNTRACED) > 0) usleep(1000);
				break;
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
	/**
	 * プロセスを停止させる
	 * @param string $pid_file 
	 * @throws \Exception
	 */
	static public function stop($php_path,$pid_file=null,$opt=array()){
		if((php_sapi_name() !== 'cli')) return;
		$name = isset($opt['name']) ? $opt['name'] : null;
		if(isset($pid_file)){
			if(empty($pid_file) && !empty($name)) $pid_file = sprintf('/var/run/%s.pid',$name);
			if(empty($pid_file)) $pid_file = sprintf('/var/run/%s.pid',basename($php_path,'.php'));
		}		
		if(!is_file($pid_file)) throw new \Exception($pid_file.' not found');
		$pid = (int)file_get_contents($pid_file);
		posix_kill($pid,SIGTERM);
		if(posix_kill($pid,0)) new \Exception('stop fail');
		if(is_file($pid_file)) unlink($pid_file);
	}
	/**
	 * プロセスを開始させる
	 * @param string $php_path
	 * @param string $pid_file
	 * @throws \Exception
	 */
	static public function start($php_path,$pid_file=null,$opt=array()){
		if((php_sapi_name() !== 'cli')) return;
		if(!extension_loaded('pcntl')) throw new \Exception('require pcntl module');		
		$clients = isset($opt['clients']) ? $opt['clients'] : 1;
		$sleep = isset($opt['sleep']) ? $opt['sleep'] : 0;
		$phpcmd = isset($_ENV['_']) ? $_ENV['_'] : (isset($_SERVER['_']) ? $_SERVER['_'] : (isset($cmd['phpcmd']) ? $cmd['phpcmd'] : '/usr/bin/php'));

		$name = isset($opt['name']) ? $opt['name'] : null;
		if(isset($pid_file)){
			if(empty($pid_file) && !empty($name)) $pid_file = sprintf('/var/run/%s.pid',$name);
			if(empty($pid_file)) $pid_file = sprintf('/var/run/%s.pid',basename($php_path,'.php'));
		}		
		if(isset($opt['dir'])) chdir($opt['dir']);
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
			if(
				(isset($opt['uid']) && !posix_setuid($opt['uid'])) || 
				(isset($opt['euid']) && !posix_seteuid($opt['euid'])) || 
				(isset($opt['gid']) && !posix_setgid($opt['gid'])) ||
				(isset($opt['egid']) && !posix_setegid ($opt['egid']))
			){
				throw new \Exception(posix_strerror(posix_get_last_error()));
			}				
			if(!empty(self::$pid_file)){
				if(pcntl_fork() !== 0) return;
				posix_setsid();
			}
			foreach(self::$signal_list as $sig => $dec) pcntl_signal($sig,array(__CLASS__,'signal_func'));
			self::$pid = posix_getpid();
			if(!empty(self::$pid_file)) file_put_contents(self::$pid_file,self::$pid);
			
			while(true){
				if(self::$state === false) break;
				$pid = pcntl_fork();
				self::$child[$pid] = true;
				
				if($pid === -1) throw new \Exception('Unable to fork');
				if($pid === 0){
					$pid = posix_getpid();
					pcntl_exec($phpcmd,array($php_path,self::$pid,$pid));
				}
				if(sizeof(self::$child) >= $clients){
					$exist_pid = pcntl_wait($status);
					if(isset(self::$child[$exist_pid])) unset(self::$child[$exist_pid]);
					if(pcntl_wifexited($status)){}
				}
				if($sleep > 0) usleep($sleep * 1000000);
				clearstatcache();
			}
			if(!empty(self::$pid_file) && is_file(self::$pid_file)) @unlink(self::$pid_file);
		}
	}
}
