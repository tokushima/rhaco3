<?php
namespace angela{
/**
 * 設定情報
 */
class Conf{
	static private $conf_file;
	static private $entry_dir;
	static private $test_dir;
	static private $lib_dir;

	static private $urls;
	static private $setup_func;

	static private $ini_error_log = null;
	static private $ini_error_log_start_size = 0;
	static private $memory_limit = 32;

	/**
	 * 設定ファイル(.php)のセット
	 * @param string $path
	 */
	static public function set($path){
		self::$conf_file = $path;
	}
	/**
	 * 初期設定
	 * @param string $rootdir
	 * @param array $params
	 * @param string $conf_file
	 * @throws \RuntimeException
	 * @return Ambigous <string, mixed>|boolean
	 */
	static public function init($rootdir,$params,$conf_file=null){
		self::$ini_error_log = ini_get('error_log');
		self::$ini_error_log_start_size = (empty(self::$ini_error_log) || !is_file(self::$ini_error_log)) ? 0 : filesize(self::$ini_error_log);
		
		$conf = array();
		self::$conf_file = empty($conf_file) ? null : realpath($conf_file);
		if(is_file(self::$conf_file)){
			$conf = include(self::$conf_file);
			if(!is_array($conf)) throw new \RuntimeException('invalid '.$f);
		}
		foreach($conf as $k => $v){
			if(!array_key_exists($k,$params)) $params[$k] = $v;
		}
		$path_func = function($p){
			$p = str_replace('\\','/',$p);
			if(substr($p,-1) != '/') $p = $p.'/';
			return $p;
		};
		if(substr($rootdir,-1) != '/') $rootdir = $rootdir.'/';
		self::$entry_dir = $rootdir;
		self::$test_dir = isset($params['test_dir']) ? $path_func($params['test_dir']) : self::$entry_dir.'test/';
		self::$lib_dir = isset($params['lib_dir']) ? $path_func($params['lib_dir']) : self::$entry_dir.'lib/';
		
		set_include_path(get_include_path()
			.PATH_SEPARATOR.self::$lib_dir
		);
		spl_autoload_register(function($c){
			$cp = str_replace('\\','//',(($c[0] == '\\') ? substr($c,1) : $c));
			foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
				if(!empty($p) && ($r = realpath($p)) !== false && is_file($f=($r.'/'.$cp.'.php'))){
					require_once($f);
					break;
				}
			}
			return (class_exists($c,false) || interface_exists($c,false) || (function_exists('trait_exists') && trait_exists($c,false)));
		},true,false);		
		
		if(isset($params['memory_limit'])){
			self::$memory_limit = (int)(isset($params['memory_limit']) ? $params['memory_limit'] : $params['memory-limit']);
			if(!empty(self::$memory_limit) && self::$memory_limit >= 8){
				ini_set('memory_limit',self::$memory_limit.'M');
			}else{
				self::$memory_limit = null;
			}
		}
		if(isset($params['urls']) && is_array($params['urls'])){
			self::$urls = $params['urls'];
		}
		if(isset($params['setup_func'])){
			self::$setup_func = $params['setup_func'];
		}
	}
	/**
	 * 設定情報表示
	 * @param string $bool
	 */
	static public function info($bool=false){
		if(!empty(self::$conf_file)){
			print('load configuration file: '.self::$conf_file.PHP_EOL);
		}
		if(!empty(self::$memory_limit)){
			print('memory limit: '.self::$memory_limit.'M'.PHP_EOL);
		}
		print('searching entry '.self::$entry_dir.PHP_EOL);
		print('searching test '.self::$test_dir.PHP_EOL);
		print('searching lib  '.self::$lib_dir.PHP_EOL);
		print(PHP_EOL);
	
		if($bool){
			print('executing test ... '.PHP_EOL);
		}
	}
	/**
	 * テスト中に発生したPHPのエラー
	 * @return Ambigous <NULL, string>
	 */
	static public function error_log(){
		$ini_error_log_end_size = (empty(self::$ini_error_log) || !is_file(self::$ini_error_log)) ? 0 : filesize(self::$ini_error_log);
		return ($ini_error_log_end_size != self::$ini_error_log_start_size) ? file_get_contents(self::$ini_error_log,false,null,self::$ini_error_log_start_size) : null;
	}
	/**
	 * エントリディレクトリ
	 * @return string
	 */
	static public function entry_dir(){
		return self::$entry_dir;
	}
	/**
	 * ライブラリディレクトリ
	 * @return string
	 */
	static public function lib_dir(){
		return self::$lib_dir;
	}
	/**
	 * テストディレクトリ
	 * @return string
	 */
	static public function test_dir(){
		return self::$test_dir;
	}
	/**
	 * URLのマッピング
	 * @return array
	 */
	static public function urls(){
		return self::$urls;
	}
	/**
	 * URLをマッピングデータから取得
	 * @param string $map_name
	 * @throws \RuntimeException
	 * @return string
	 */
	static public function map_url($map_name){
		if(empty(self::$urls)) throw new \RuntimeException('urls empty');
		$args = func_get_args();
		array_shift($args);
				
		if(isset(self::$urls[$map_name]) && substr_count(self::$urls[$map_name],'%s') == sizeof($args)) return vsprintf(self::$urls[$map_name],$args);
		throw new \RuntimeException($map_name.(isset(self::$urls[$map_name]) ? '['.sizeof($args).']' : '').' not found');
	}
	/**
	 * 全体で都度実行されるsetup関数
	 */
	static public function call_setup_func(){
		if(isset(self::$setup_func)){
			call_user_func(self::$setup_func);
		}
	}
}
/**
 * ユーティリティ
 */
class Util{
	/**
	 * 絶対パスにする
	 * @param string $a
	 * @param string $b
	 * @return string
	 */
	static public function absolute($a,$b){
		$a = str_replace("\\",'/',$a);
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
	/**
	 * 文字色を指定したフォーマット
	 * @param string $msg
	 * @param string $color
	 * @return string
	 */
	static public function color_format($msg,$color='30'){
		return (php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg;
	}
}
/**
 * 例外発生時
 */
class AssertException extends \Exception{
}
/**
 * テスト失敗時
 */
class AssertFailure extends \Exception{
}
/**
 * 検証用メソッド
 */
class Assert{
	static private $failure_info = array('',0,null,null);
	
	static private function debug_info($file,$line){
		if(!isset($file)){
			list(,$debug) = debug_backtrace(false);
			$line = $debug['line'];
			$file = $debug['file'];
		}
		if(strpos($file,'eval()\'d') !== false){
			$file = \angela\Runner::current_file();
		}
		return array($file,$line);
	}
	/**
	 * 失敗とする
	 * @param string $msg
	 * @throws \angela\AssertFailure
	 */
	public function failure($msg='failure'){
		throw new \angela\AssertFailure($msg);
	}
	/**
	 * 一致
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @param string $file
	 * @param string $line
	 * @throws \angela\AssertException
	 */
	public function equals($arg1,$arg2,$file=null,$line=null){
		if(!(self::expvar($arg1) === self::expvar($arg2))){
			list($file,$line) = self::debug_info($file, $line);
			self::$failure_info = array($file,$line,var_export($arg1,true),var_export($arg2,true));
			throw new \angela\AssertException();
		}
	}
	/**
	 * 不一致
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @param string $file
	 * @param string $line
	 * @throws \angela\AssertException
	 */
	public function not_equals($arg1,$arg2,$file=null,$line=null){
		if(!(self::expvar($arg1) !== self::expvar($arg2))){
			list($file,$line) = self::debug_info($file, $line);
			self::$failure_info = array($file,$line,var_export($arg1,true),var_export($arg2,true));
			throw new \angela\AssertException();
		}
	}
	/**
	 * 文字列中に指定の文字列がすべて存在する
	 * @param string/array $keyword
	 * @param string $src
	 * @param string $file
	 * @param string $line
	 * @throws \angela\AssertException
	 */
	public function match_equals($keyword,$src,$file=null,$line=null){
		if($src instanceof \angela\Http) $src = $src->body();
		$valid = array();
		
		if(!is_array($keyword)) $keyword = array($keyword);
		foreach($keyword as $q){
			if(mb_strpos($src,$q) !== false){
				$valid[] = $q;
			}
		}
		if(!(self::expvar($keyword) === self::expvar($valid))){
			list($file,$line) = self::debug_info($file, $line);
			self::$failure_info = array($file,$line,var_export($keyword,true),var_export($valid,true));
			throw new \angela\AssertException();
		}
	}
	/**
	 * 文字列中に指定の文字列がすべて存在しない
	 * @param string/array $keyword
	 * @param string $src
	 * @param string $file
	 * @param string $line
	 * @throws \angela\AssertException
	 */
	public function match_not_equals($keyword,$src,$file=null,$line=null){
		if($src instanceof \angela\Http) $src = $src->body();
		$valid = array();
	
		if(!is_array($keyword)) $keyword = array($keyword);
		foreach($keyword as $q){
			if(mb_strpos($src,$q) === false){
				$valid[] = $q;
			}
		}
		if(!(self::expvar($keyword) === self::expvar($valid))){
			list($file,$line) = self::debug_info($file, $line);
			self::$failure_info = array($file,$line,var_export($keyword,true),var_export($valid,true));
			throw new \angela\AssertException();
		}
	}
	static public function failure_info(){
		return self::$failure_info;
	}
	static private function expvar($var){
		if(is_numeric($var)) return strval($var);
		if(is_object($var)) $var = get_object_vars($var);
		if(is_array($var)){
			foreach($var as $key => $v){
				$var[$key] = self::expvar($v);
			}
		}
		return $var;
	}
}
/**
 * テストランナー
 */
class Runner{
	static private $result = array();

	static private $current_execute_file;
	static private $current_file;
	static private $current_block_start_time;
	static private $current_entry;

	static private $read_file = array();
	static private $has_exception = false;
	static private 	$fixture = false;

	static public function get(){
		return self::$result;
	}
	/**
	 * エラーがあったか
	 * @return boolean
	 */
	static public function has_exception(){
		return self::$has_exception;
	}
	/**
	 * 実行中のファイル
	 * @return string
	 */
	static public function current_file(){
		return self::$current_file;
	}
	/**
	 * 実行中のエントリテスト
	 */
	static public function current_entry(){
		return self::$current_entry;
	}
	/**
	 * テストを実行する
	 * @param string $class_name クラス名
	 * @param string $method メソッド名
	 * @param string $block_name ブロック名
	 * @param boolean $include_tests testsディレクトリも参照するか
	 */
	static private function run($class_name,$method_name=null,$block_name=null,$include_tests=false){
		if($class_name == __FILE__) return new self();
		$test_list = array(\angela\Conf::test_dir()=>array('type'=>3,'tests'=>array()),\angela\Conf::entry_dir()=>array('type'=>2,'tests'=>array()));
		
		// テストを探す
		if(class_exists($cn=((substr($class_name,0,1) != '\\') ? '\\' : '').str_replace('.','\\',$class_name),true)
			|| interface_exists($cn,true)
			|| (function_exists('trait_exists') && trait_exists($cn,true))
		){
			// class
			$rc = new \ReflectionClass($cn);
			if(!isset(self::$read_file[$rc->getFileName()])){
				$tests = array();
				self::$read_file[$rc->getFileName()] = true;
				$class_src_lines = file($rc->getFileName());
				$class_src = implode('',$class_src_lines);
				
				foreach($rc->getMethods() as $method){
					if($method->getDeclaringClass()->getName() == $rc->getName()){
						$method_src = implode('',array_slice($class_src_lines,$method->getStartLine()-1,$method->getEndLine()-$method->getStartLine(),true));
						$tests = array_merge($tests,self::get_method_doctest($rc->getName(),$method->getName(),$method->getStartLine(),$method->isPublic(),$method_src));
						$class_src = str_replace($method_src,str_repeat("\n",sizeof(explode("\n",$method_src)) - 1),$class_src);
					}
				}
				$tests = array_merge($tests,self::get_method_doctest($rc->getName(),'class',1,false,$class_src));
				$test_list[$rc->getFileName()] = array('type'=>1,'tests'=>$tests);
			}
		}else if((is_file($f=$class_name) && strpos($class_name,\angela\Conf::entry_dir()) === 0 && strpos(str_replace(\angela\Conf::entry_dir(),'',$class_name),'/') === false)
			|| is_file($f=\angela\Conf::entry_dir().$class_name.'.php')
		){
			// entry
			if(!isset(self::$read_file[$f])){
				self::$read_file[$f] = true;
				
				$tests = array();
				$entry = basename($f,'.php');
				$src = file_get_contents($f);
				if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$src,$doctests,PREG_OFFSET_CAPTURE)){
					foreach($doctests[0] as $doctest){
						if(isset($doctest[0][5]) && $doctest[0][5] != '*'){
							$test_start_line = sizeof(explode("\n",substr($src,0,$doctest[1]))) - 1;
							$test_block = str_repeat("\n",$test_start_line).preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array("/"."***","*"."/"),"",$doctest[0]));
							$test_block_name = preg_match("/^[\s]*#([^#].*)/",trim($test_block),$match) ? trim($match[1]) : null;
							if(trim($test_block) == '') $test_block = null;
							
							if($test_block_name == '__setup__' || $test_block_name == '__teardown__'){
								$test_list[\angela\Conf::entry_dir()]['tests']['@'][$test_block_name] = array($test_block_name,$test_block,$test_start_line);
							}else{
								$test_list[\angela\Conf::entry_dir()]['tests'][$f][] = array($test_block_name,$test_block,$test_start_line);
							}
						}
					}
				}
			}
		}else if(is_file($f=$class_name) && strpos($class_name,\angela\Conf::test_dir()) === 0
			|| is_file($f=\angela\Conf::test_dir().$class_name.'.php')
		){
			// test
			if(!isset(self::$read_file[$f]) &&
				substr($f,-4) == '.php' &&
				!preg_match('/\/[\._]/',$f)
			){
				self::$read_file[$f] = true;
				$test_list[\angela\Conf::test_dir()]['tests'][$f] = array(array(basename($f,'.php'),$f,0));
			}
		}
		// test
		if(is_file($f=\angela\Conf::test_dir().str_replace('.','/',$class_name).'.php')){
			if(!isset(self::$read_file[$f]) &&
				substr($f,-4) == '.php' &&
				!preg_match('/\/[\._]/',$f)
			){
				self::$read_file[$f] = true;
				$test_list[\angela\Conf::test_dir()]['tests'][$f] = array(array(basename($f,'.php'),$f,0));
			}
		}
		// test
		if(is_dir($d=\angela\Conf::test_dir().str_replace('.','/',$class_name))){
			foreach(self::file_list($d,true,'/\.php/') as $f){
				if(!isset(self::$read_file[$f->getPathname()]) &&
					!preg_match('/\/[\._]/',$f->getPathname())
				){
					self::$read_file[$f->getPathname()] = true;
					$test_list[\angela\Conf::test_dir()]['tests'][$f->getPathname()] = array(array(basename($f->getPathname(),'.php'),$f,0));
				}
			}
		}
		
		// テスト実行
		foreach($test_list as $filename => $doctest){
			self::$current_file = $filename;
			self::$current_entry = null;
			self::$current_execute_file = null;
			$s_block_name = (substr($block_name,-4) == '.php') ? substr(basename($block_name),0,-4) : $block_name;
			
			foreach($doctest['tests'] as $test_method_name => $tests){
				if($method_name === null || $method_name === $test_method_name){
					self::$current_execute_file = $test_method_name;
	
					if(!empty($tests)){
						foreach($tests as $test_block){
							list($name,$block,$start_line) = $test_block;
							$current_block_start_time = microtime(true);
							$s_name = (substr($name,-4) == '.php') ? substr(basename($name),0,-4) : $name;
	
							if($block_name === null || $s_block_name === $s_name){
								$exec_block_name = ' #'.$s_name;
								print($exec_block_name);
								try{
									ob_start();
									\angela\Conf::call_setup_func();
	
									if($doctest['type'] == 3){
										self::include_setup_teardown($block,'__setup__.php');
										include($block);
									}else{
										if($doctest['type'] == 2){
											self::$current_entry = basename($test_method_name,'.php');
										}
										eval($block);
									}
									$result = ob_get_clean();
									
									if(preg_match('/(Parse|Fatal) error:.+/',$result,$match)){
										$err = (preg_match('/syntax error.+code on line\s*(\d+)/',$result,$line) ?
												'Parse error: syntax error '.$doctest['filename'].' code on line '.$line[1]
												: $match[0]);
										throw new \ErrorException($err);
									}
									$test_result = array(
											'success',
											(round(microtime(true) - $current_block_start_time,3)),
											array(),
											$name,
									);
								}catch(\angela\AssertException $e){
									self::$has_exception = true;
									$test_result = array(
											'fail',
											(round(microtime(true) - $current_block_start_time,3)),
											\angela\Assert::failure_info(),
											$name,
									);
								}catch(\Exception $e){
									self::$has_exception = true;
									if(ob_get_level() > 0) $result = ob_get_clean();
									list($message,$file,$line) = array(get_class($e).': '.$e->getMessage(),$e->getFile(),$e->getLine());
									$trace = $e->getTrace();
									$eval = false;
									$exeline = -1;									
									
									foreach($trace as $k => $t){
										if(isset($t['class']) && isset($t['function']) && ($t['class'].'::'.$t['function']) == __METHOD__ && isset($trace[$k-2])
												&& isset($trace[$k-1]['file']) && $trace[$k-1]['file'] == __FILE__ && isset($trace[$k-1]['function']) && $trace[$k-1]['function'] == 'eval'
										){
											$file = self::$current_file;
											$line = $trace[$k-2]['line'];
											$eval = true;
											break;
										}
									}									
									if(!$eval && isset($trace[0]['file']) && self::$current_file == $trace[0]['file']){
										$file = $trace[0]['file'];
										$line = $trace[0]['line'];
									}

									krsort($trace);
									foreach($trace as $k => $t){
										if(isset($t['file']) && $t['file'] != __FILE__ && isset($t['line'])){
											$exeline = $t['line'];
											$exe = $k;
											break;
										}
									}
									$test_result = array(
											'exception',
											(round(microtime(true) - $current_block_start_time,3)),
											array(self::$current_execute_file,$exeline,sprintf('%s: %d',$file,$line),$message),
											$name,
									);
								}
								if($doctest['type'] == 3){
									self::include_setup_teardown($block,'__teardown__.php');
								}
								print("\033[".strlen($exec_block_name).'D'."\033[0K");
								
								self::$result[self::$current_file][self::$current_execute_file][] = $test_result;
							}
						}
					}else{
						self::$result[self::$current_file][self::$current_execute_file][] = array('none',0,array(),null,null);
					}
				}
			}
		}
	}
	static private function get_method_doctest($class_name,$method_name,$method_start_line,$is_public,$method_src){
		$result = array();
		if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$method_src,$doctests,PREG_OFFSET_CAPTURE)){
			foreach($doctests[0] as $doctest){
				if(isset($doctest[0][5]) && $doctest[0][5] != "*"){
					$test_start_line = $method_start_line + substr_count(substr($method_src,0,$doctest[1]),"\n") - 1;
					$test_block = str_repeat("\n",$test_start_line).str_replace(array('self::','new self(','extends self{'),array($class_name.'::','new '.$class_name.'(','extends '.$class_name.'{'),preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."***","*"."/"),"",$doctest[0])));
					$test_block_name = preg_match("/^[\s]*#([^#].*)/",trim($test_block),$match) ? trim($match[1]) : null;
					if(trim($test_block) == '') $test_block = null;
					$result[$method_name][] = array($test_block_name,$test_block,$test_start_line);
				}
			}
		}else if($is_public && $method_name[0] != '_'){
			$result[$method_name] = array();
		}
		return $result;
	}
	static private function include_setup_teardown($test_file,$include_file){
		if(strpos($test_file,\angela\Conf::test_dir()) === 0){
			$inc = array();
			$dir = dirname($test_file);
			while($dir.'/' != \angela\Conf::test_dir()){
				if(is_file($f=($dir.'/'.$include_file))) array_unshift($inc,$f);
				$dir = dirname($dir);
			}
			if(is_file($f=(\angela\Conf::test_dir().$include_file))) array_unshift($inc,$f);
			foreach($inc as $i) include($i);
		}else if(is_file($f=(dirname($test_file).$include_file))){
			include($f);
		}
	}
	/**
	 * テストの実行
	 * @param string $class_name クラス名、またはファイル名
	 * @param string $m メソッド名
	 * @param string $b ブロック名
	 * @param boolean $include_tests スペックテストも含むか
	 * @throws Exception
	 */
	static public function varidate($class_name,$m=null,$b=null,$include_tests=false,$label=null){
		if(strpos($class_name,substr(__FILE__,0,-4)) !== 0){
			if(!self::$fixture){
				if(is_file(\angela\Conf::test_dir().'__fixture__.php')){
					ob_start();
						include_once(\angela\Conf::test_dir().'__fixture__.php');
					ob_end_clean();
				}
				self::$fixture = true;
			}
			$f = ' '.(empty($label) ? $class_name : $label).(isset($m) ? '::'.$m : '');
			print($f);
			$throw = null;
			$starttime = microtime(true);
			$startmem = round(number_format((memory_get_usage() / 1024 / 1024),3),4);
			try{
				self::run($class_name,$m,$b,$include_tests);
			}catch(\Exception $e){
				$throw = $e;
			}
			$time = round((microtime(true) - (float)$starttime),4);
			$mem = round(number_format((memory_get_usage() / 1024 / 1024),3),4) - $startmem;
			print(' ('.$time.' sec)'.PHP_EOL);
			if(isset($throw)) throw $throw;
		}
	}
	static private function file_list($d,$res=false,$reg=null){
		$it = new \RecursiveDirectoryIterator($d,
				\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
		);
		if($res) $it = new \RecursiveIteratorIterator($it,\RecursiveIteratorIterator::SELF_FIRST);
		if(!empty($reg)) $it = new \RegexIterator($it,$reg);
		return $it;
	}
	/**
	 * すべてのテストを実行する
	 */
	static public function run_all(){
		if(is_dir(\angela\Conf::lib_dir())){
			print(\angela\Util::color_format(PHP_EOL.'Library'.PHP_EOL,'1;34'));
			
			$lib = \angela\Conf::lib_dir();
			foreach(self::file_list($lib,true,'/\.php/') as $f){
				if(ctype_upper(substr($f->getFilename(),0,1))){
					if(strpos($f->getPathname(),'/test/') === false){
						$class = substr(str_replace(array($lib,'/'),array('','.'),$f->getPathname()),0,-4);
						self::varidate($class,null,null,false,$f->getPathname());
					}
				}
			}
		}
		if(is_dir(\angela\Conf::entry_dir())){
			print(\angela\Util::color_format(PHP_EOL.'Entry'.PHP_EOL,'1;34'));
		
			$pre = getcwd();
			chdir(\angela\Conf::entry_dir());
		
			foreach(self::file_list(\angela\Conf::entry_dir(),false,'/\.php/') as $f){
				if(substr($f->getFilename(),-4) == '.php' && !preg_match('/\/[\._]/',$f->getPathname())){
					self::varidate($f->getPathname(),null,null,false);
				}
			}
			chdir($pre);
		}
		if(is_dir(\angela\Conf::test_dir())){
			print(\angela\Util::color_format(PHP_EOL.'Test'.PHP_EOL,'1;34'));
				
			foreach(self::file_list(\angela\Conf::test_dir(),true,'/\.php/') as $f){
				if(substr($f->getFilename(),-4) == '.php' && !preg_match('/\/[\._]/',$f->getPathname())){
					self::varidate($f->getPathname(),null,null,false);
				}
			}
		}
	}
}
/**
 * カヴァレッジ
 */
class Coverage{
	static private $start = false;
	static private $db;

	/**
	 * 実行中か
	 * @param array $vars
	 * @return boolean
	 */
	static public function has_started(&$vars){
		if(self::$start){
			$vars['savedb'] = self::$db;
			return true;
		}
		return false;
	}
	/**
	 * 実行中のdbファイルパス
	 */
	static public function db(){
		return self::$db;
	}
	/**
	 * dbファイルの削除
	 */
	static public function delete(){
		if(is_file(self::$db)){
			unlink(self::$db);
		}
	}
	/**
	 * カヴァレッジ開始
	 * @param string $savedb
	 * @param string $lib_dir
	 * @throws \RuntimeException
	 * @return multitype:
	 */
	static public function start($savedb,$lib_dir){
		if(extension_loaded('xdebug')){
			$exist = (is_file($savedb));
			if(substr($lib_dir,-1) != '/') $lib_dir = $lib_dir.'/';
			
			if($db = new \PDO('sqlite:'.$savedb)){
				self::$start = true;
				self::$db = $savedb;

				if(!$exist){
					$sql = 'create table coverage_info('.
							'id integer not null primary key,'.
							'covered_line text null,'.
							'ignore_line text,'.
							'active_len integer,'.
							'file_path text null,'.
							'percent integer'.
							')';
					if(false === $db->query($sql)) throw new \RuntimeException('failure create target_info table');
					if(is_dir($lib_dir)){
						foreach(new \RecursiveIteratorIterator(
								new \RecursiveDirectoryIterator(
										$lib_dir,
										\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
								),\RecursiveIteratorIterator::SELF_FIRST
						) as $f){
							if($f->isFile() &&
									substr($f->getFilename(),-4) == '.php' &&
									strpos($f->getPathname(),'/test/') === false &&
									ctype_upper(substr($f->getFilename(),0,1))
							){
								$src = file_get_contents($f->getPathname());
								$ignore_line = array();
									
								foreach(array(
										"/(\/\*.*?\*\/)/ms",
										"/^((namespace|use|class)[\s].+)$/m",
										"/^[\s]*(include)[\040\(].+$/m",
										"/^([\s]*(final|static|protected|private|public|const)[\s].+)$/m",
										"/^([\s]*\/\/.+)$/m",
										"/^([\s]*#.+)$/m",
										"/^([\s]*<\?php[\s]*)$/ms",
										"/^([\s]*\?>[\s]*)$/m",
										"/^([\s]*try[\s]*\{[\s]*)$/m",
										"/^([\s\}]*catch[\s]*\(.+\).+)$/m",
										"/^(.*array\()[\s]*$/m",
										"/^([\s]*\}[\s]*else[\s]*\{[\s]*)$/m",
										"/^([\s]*\{[\s]*)$/m",
										"/^([\s]*\}[\s]*)$/m",
										"/^([\s\(\)]+)$/m",
										"/^([\s]*)$/ms",
										"/(\n)$/s",
								) as $pattern){
									if(preg_match_all($pattern,$src,$m,PREG_OFFSET_CAPTURE)){
										foreach($m[1] as $c){
											$ignore_line = array_merge($ignore_line,call_user_func_array(function($c0,$c1,$src){
												$s = substr_count(substr($src,0,$c1),PHP_EOL);
												$e = substr_count($c0,PHP_EOL);
												return range($s+1,$s+1+$e);
											},array($c[0],$c[1],$src)));
										}
									}
								}
								$ignore_line = array_unique($ignore_line);
								sort($ignore_line);
								$active_len = empty($src) ? 0 : (substr_count($src,PHP_EOL) - sizeof($ignore_line) + 1);
									
								$ps = $db->prepare('insert into coverage_info(file_path,ignore_line,active_len,percent) values(?,?,?,?)');
								$ps->execute(array($f->getPathname(),implode(',',$ignore_line),$active_len,0));
							}
						}
					}
				}
			}
			xdebug_start_code_coverage();
		}
	}
	/**
	 * カヴァレッジ終了
	 */
	static public function stop(){
		if(is_file(self::$db) && $db = new \PDO('sqlite:'.self::$db)){
			foreach(xdebug_get_code_coverage() as $file_path => $lines){
				$sql = 'select id,covered_line,ignore_line,active_len from coverage_info where file_path = ?';
				$ps = $db->prepare($sql);
				$ps->execute(array($file_path));
					
				if($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$id = (int)$resultset['id'];
					$active_len = (int)$resultset['active_len'];
					$ignore_line = explode(',',$resultset['ignore_line']);
					$covered_line = empty($resultset['covered_line']) ? array() : explode(',',$resultset['covered_line']);
					$covered_line = array_merge(array_keys($lines),$covered_line);
					$covered_line = array_unique($covered_line);
					sort($covered_line);
						
					$covered_len = sizeof(array_diff($covered_line,$ignore_line));
					$percent = (!empty($covered_line) && $active_len === 0) ? 100 : (($covered_len === 0) ? 0 : (floor($covered_len / $active_len * 100)));
					if($percent > 100) $percent = 100;
						
					$ps = $db->prepare('update coverage_info set covered_line=?,percent=? where id=?');
					$ps->execute(array(implode(',',$covered_line),$percent,$id));
				}
			}
		}
		xdebug_stop_code_coverage();
	}
	/**
	 * 結果取得
	 * @return array
	 */
	static public function get(){
		$list = array();
		if(is_file(self::$db)){
			if($db = new \PDO('sqlite:'.self::$db)){
				$sql = 'select file_path,percent,covered_line,ignore_line from coverage_info order by file_path';
				$ps = $db->prepare($sql);
				$ps->execute();
	
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$list[] = $resultset;
				}
			}
		}
		return $list;
	}
}
/**
 * HTTPリクエスト
 */
class Http{
	private $resource;
	private $agent;
	private $timeout = 30;
	private $redirect_max = 20;
	private $redirect_count = 1;

	private $request_header = array();
	private $request_vars = array();
	private $request_file_vars = array();
	private $head;
	private $body;
	private $cookie = array();
	private $url;
	private $status;

	public function __construct($agent=null,$timeout=30,$redirect_max=20){
		$this->agent = $agent;
		$this->timeout = (int)$timeout;
		$this->redirect_max = (int)$redirect_max;
	}
	/**
	 * 最大リダイレクト回数
	 * @param integer $redirect_max
	 */
	public function redirect_max($redirect_max){
		$this->redirect_max = (integer)$redirect_max;
	}
	/**
	 * タイムアウトするまでの秒数
	 * @param integer $timeout
	 */
	public function timeout($timeout){
		$this->timeout = (int)$timeout;
	}
	/**
	 * リクエスト時のユーザエージェント
	 * @param string $agent
	 */
	public function agent($agent){
		$this->agent = $agent;
	}
	public function __toString(){
		return $this->body();
	}
	/**
	 * リクエスト時のヘッダ
	 * @param string $key
	 * @param string $value
	 */
	public function header($key,$value=null){
		$this->request_header[$key] = $value;
	}
	/**
	 * リクエスト時のクエリ
	 * @param string $key
	 * @param string $value
	 */
	public function vars($key,$value=null){
		if(is_bool($value)) $value = ($value) ? 'true' : 'false';
		$this->request_vars[$key] = $value;
		if(isset($this->request_file_vars[$key])) unset($this->request_file_vars[$key]);
	}
	/**
	 * リクエスト時の添付ファイル
	 * @param string $key
	 * @param string $filepath
	 */
	public function file_vars($key,$filepath){
		$this->request_file_vars[$key] = $filepath;
		if(isset($this->request_vars[$key])) unset($this->request_vars[$key]);
	}
	/**
	 * リクエストクエリがセットされているか
	 * @param string $key
	 * @return boolean
	 */
	public function has_vars($key){
		return (array_key_exists($key,$this->request_vars) || array_key_exists($key,$this->request_file_vars));
	}
	/**
	 * curlへのオプション
	 * @param string $key
	 * @param string $value
	 */
	public function setopt($key,$value){
		if(!isset($this->resource)) $this->resource = curl_init();
		curl_setopt($this->resource,$key,$value);
	}
	/**
	 * 結果ヘッダの取得
	 * @returnstring
	 */
	public function head(){
		return $this->head;
	}
	/**
	 * 結果ボディーの取得
	 * @return string
	 */
	public function body(){
		return ($this->body === null || is_bool($this->body)) ? '' : $this->body;
	}
	/**
	 * 最終実行URL
	 * @return string
	 */
	public function url(){
		return $this->url;
	}
	/**
	 * 最終HTTPステータス
	 * @return integer
	 */
	public function status(){
		return empty($this->status) ? null : (int)$this->status;
	}
	/**
	 * HEADでリクエスト
	 * @param string $url
	 * @return self
	 */
	public function do_head($url){
		return $this->request('HEAD',$url);
	}
	/**
	 * PUTでリクエスト
	 * @param string $url
	 * @return self
	 */
	public function do_put($url){
		return $this->request('PUT',$url);
	}
	/**
	 * DELETEでリクエスト
	 * @param string $url
	 * @return self
	 */
	public function do_delete(){
		return $this->request('DELETE',$url);
	}
	/**
	 * GETでリクエスト
	 * @param string $url
	 * @return self
	 */
	public function do_get($url){
		return $this->request('GET',$url);
	}
	/**
	 * POSTでリクエスト
	 * @param string $url
	 * @return self
	 */
	public function do_post($url){
		return $this->request('POST',$url);
	}
	/**
	 * GETでリクエストしてダウンロード
	 * @param string $url
	 * @param string $download_path 保存パス
	 * @return self
	 */
	public function do_download($url,$download_path){
		return $this->request('GET',$url,$download_path);
	}
	/**
	 * POSTでリクエストしてダウンロード
	 * @param string $url
	 * @param string $download_path 保存パス
	 * @return self
	 */
	public function do_post_download($url,$download_path){
		return $this->request('POST',$url,$download_path);
	}
	public function callback_head($resource,$data){
		$this->head .= $data;
		return strlen($data);
	}
	public function callback_body($resource,$data){
		$this->body .= $data;
		return strlen($data);
	}
	private function request($method,$url,$download_path=null){
		if(!isset($this->resource)) $this->resource = curl_init();
		$url_info = parse_url($url);
		$cookie_base_domain = (isset($url_info['host']) ? $url_info['host'] : '').(isset($url_info['path']) ? $url_info['path'] : '');
		if(\angela\Coverage::has_started($vars)) $this->request_vars['_coverage_vars_'] = $vars;
		
		if(isset($url_info['query'])){
			parse_str($url_info['query'],$vars);
			foreach($vars as $k => $v){
				if(!isset($this->request_vars[$k])) $this->request_vars[$k] = $v;
			}
			list($url) = explode('?',$url,2);
		}
		switch($method){
			case 'POST': curl_setopt($this->resource,CURLOPT_POST,true); break;
			case 'GET': curl_setopt($this->resource,CURLOPT_HTTPGET,true); break;
			case 'HEAD': curl_setopt($this->resource,CURLOPT_NOBODY,true); break;
			case 'PUT': curl_setopt($this->resource,CURLOPT_PUT,true); break;
			case 'DELETE': curl_setopt($this->resource,CURLOPT_CUSTOMREQUEST,'DELETE'); break;
		}
		switch($method){
			case 'POST':
				if(!empty($this->request_file_vars)){
					$vars = array();
					if(!empty($this->request_vars)){
						foreach(explode('&',http_build_query($this->request_vars)) as $q){
							$s = explode('=',$q,2);
							$vars[urldecode($s[0])] = isset($s[1]) ? urldecode($s[1]) : null;
						}
					}
					foreach(explode('&',http_build_query($this->request_file_vars)) as $q){
						$s = explode('=',$q,2);
						if(isset($s[1])){
							if(!is_file($f=urldecode($s[1]))) throw new \RuntimeException($f.' not found');
							$vars[urldecode($s[0])] = (class_exists('\\CURLFile',false)) ? new \CURLFile($f) : '@'.$f;
						}
					}
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,$vars);
				}else{
					curl_setopt($this->resource,CURLOPT_POSTFIELDS,http_build_query($this->request_vars));
				}
				break;
			case 'GET':
			case 'HEAD':
			case 'PUT':
			case 'DELETE':
				$url = $url.(!empty($this->request_vars) ? '?'.http_build_query($this->request_vars) : '');
		}
		curl_setopt($this->resource,CURLOPT_URL,$url);
		curl_setopt($this->resource,CURLOPT_FOLLOWLOCATION,false);
		curl_setopt($this->resource,CURLOPT_HEADER,false);
		curl_setopt($this->resource,CURLOPT_RETURNTRANSFER,false);
		curl_setopt($this->resource,CURLOPT_FORBID_REUSE,true);
		curl_setopt($this->resource,CURLOPT_FAILONERROR,false);
		curl_setopt($this->resource,CURLOPT_TIMEOUT,$this->timeout);

		if(!isset($this->request_header['Expect'])){
			$this->request_header['Expect'] = null;
		}
		if(!isset($this->request_header['Cookie'])){
			$cookies = '';
			foreach($this->cookie as $domain => $cookie_value){
				if(strpos($cookie_base_domain,$domain) === 0 || strpos($cookie_base_domain,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
					foreach($cookie_value as $k => $v){
						if(!$v['secure'] || ($v['secure'] && substr($url,0,8) == 'https://')) $cookies .= sprintf('%s=%s; ',$k,$v['value']);
					}
				}
			}
			curl_setopt($this->resource,CURLOPT_COOKIE,$cookies);
		}
		if(!isset($this->request_header['User-Agent'])){
			curl_setopt($this->resource,CURLOPT_USERAGENT,
					(empty($this->agent) ?
							(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null) :
							$this->agent
					)
			);
		}
		if(!isset($this->request_header['Accept']) && isset($_SERVER['HTTP_ACCEPT'])){
			$this->request_header['Accept'] = $_SERVER['HTTP_ACCEPT'];
		}
		if(!isset($this->request_header['Accept-Language']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
			$this->request_header['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		if(!isset($this->request_header['Accept-Charset']) && isset($_SERVER['HTTP_ACCEPT_CHARSET'])){
			$this->request_header['Accept-Charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
		}
		curl_setopt($this->resource,
			CURLOPT_HTTPHEADER,
			array_map(
				function($k,$v){ return $k.': '.$v; },
				array_keys($this->request_header),
				$this->request_header
			)
		);
		curl_setopt($this->resource,CURLOPT_HEADERFUNCTION,array($this,'callback_head'));

		if(empty($download_path)){
			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,array($this,'callback_body'));
		}else{
			if(!is_dir(dirname($download_path))) mkdir(dirname($download_path),0777,true);
			$fp = fopen($download_path,'wb');

			curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,function($c,$data) use(&$fp){
				if($fp) fwrite($fp,$data);
				return strlen($data);
			});
		}
		$this->request_header = $this->request_vars = array();
		$this->head = $this->body = '';
		curl_exec($this->resource);
		if(!empty($download_path) && $fp){
			fclose($fp);
		}
		if(($err_code = curl_errno($this->resource)) > 0){
			if($err_code == 47) return $this;
			throw new \RuntimeException($err_code.': '.curl_error($this->resource));
		}

		$this->status = curl_getinfo($this->resource,CURLINFO_HTTP_CODE);
		$this->url = curl_getinfo($this->resource,CURLINFO_EFFECTIVE_URL);

		if(strpos($this->url,'?') !== false){
			list($url,$query) = explode('?',$this->url,2);
			if(!empty($query)){
				parse_str($query,$vars);
				if(isset($vars['_coverage_vars_'])) unset($vars['_coverage_vars_']);
				if(!empty($vars)){
					$url = $url.'?'.http_build_query($vars);
				}
			}
			$this->url = $url;
		}
		if(preg_match_all('/Set-Cookie:[\s]*(.+)/i',$this->head,$match)){
			$unsetcookie = $setcookie = array();
			foreach($match[1] as $cookies){
				$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
				$cookie_domain = $cookie_base_domain;
				$cookie_path = '/';
				$secure = false;

				foreach(explode(';',$cookies) as $cookie){
					$cookie = trim($cookie);
					if(strpos($cookie,'=') !== false){
						list($k,$v) = explode('=',$cookie,2);
						$k = trim($k);
						$v = trim($v);
						switch(strtolower($k)){
							case 'expires': $cookie_expires = ctype_digit($v) ? (int)$v : strtotime($v); break;
							case 'domain': $cookie_domain = preg_replace('/^[\w]+:\/\/(.+)$/','\\1',$v); break;
							case 'path': $cookie_path = $v; break;
							default:
								$cookie_name = $k;
								$cookie_value = $v;
						}
					}else if(strtolower($cookie) == 'secure'){
						$secure = true;
					}
				}
				$cookie_domain = substr(\angela\Util::absolute('http://'.$cookie_domain,$cookie_path),7);

				if($cookie_expires !== null && $cookie_expires < time()){
					if(isset($this->cookie[$cookie_domain][$cookie_name])) unset($this->cookie[$cookie_domain][$cookie_name]);
				}else{
					$this->cookie[$cookie_domain][$cookie_name] = array('value'=>$cookie_value,'expires'=>$cookie_expires,'secure'=>$secure);
				}
			}
		}
		curl_close($this->resource);
		unset($this->resource);

		if($this->redirect_count++ < $this->redirect_max){
			switch($this->status){
				case 300:
				case 301:
				case 302:
				case 303:
				case 307:
					if(preg_match('/Location:[\040](.*)/i',$this->head,$redirect_url)){
						return $this->request('GET',trim($redirect_url[1]),$download_path);
					}
			}
		}
		$this->redirect_count = 1;

		return $this;
	}
	public function __destruct(){
		if(isset($this->resource)) curl_close($this->resource);
	}
}
/**
 * 結果出力
 */
class Output{
	/**
	 * カバレッジ結果を出力
	 * @param array $coverage_list
	 */
	public function coverage($coverage_list){
		print(PHP_EOL);
		print(\angela\Util::color_format('Coverage: '.PHP_EOL,'1;34'));
		
		foreach($coverage_list as $resultset){
			$color = '1;31';
			
			if($resultset['percent'] == 100){
				$color = '1;32';
			}else if($resultset['percent'] > 50){
				$color = '1;33';
			}
			print(\angela\Util::color_format(sprintf(' %3d%% %s'.PHP_EOL,$resultset['percent'],$resultset['file_path']),$color));
		}
		print(PHP_EOL);
	}
	/**
	 * カバレッジをXMLで返す
	 * @param array $coverage_list
	 * @return string
	 */
	public function xml_coverage($coverage_list){
		$xml = new \SimpleXMLElement('<coverage></coverage>');
		if(!empty($name)) $xml->addAttribute('name',$name);

		foreach($coverage_list as $resultset){
			$f = $xml->addChild('file');
			$f->addAttribute('name',$resultset['file_path']);
			$f->addAttribute('percent',$resultset['percent']);
			
			$f->addChild('covered',$resultset['covered_line']);
			$f->addChild('ignore',$resultset['ignore_line']);			
		}
		return $xml->asXML();
	}
	/**
	 * テスト結果
	 * @param array $result_list
	 * @param array $params
	 * @param string $error_str
	 * @return boolean
	 */
	public function result($result_list,$error_str){
		$result = '';
		$tab = '  ';
		$success = $count = $fail = $none = $exception = $total_time = 0;
		
		foreach($result_list as $file => $f){
			foreach($f as $method => $info_list){
				$count++;

				foreach($info_list as $info){
					$time = $info[1];
					$name = $info[3];
					$total_time = (float)$total_time + (float)$time;

					switch($info[0]){
						case 'none':
							$none++;
							break;
						case 'success':
							$success++;
							break;
						case 'fail':
							$fail++;
							list($file,$line,$r1,$r2) = $info[2];

							$result .= "\n";
							$result .= $file."\n";
							$result .= str_repeat("-",80)."\n";

							$result .= "[".$line."]: ".\angela\Util::color_format("failure","1;31")."\n";
							$result .= $tab.str_repeat("=",70)."\n";
							ob_start();
								var_dump($r1);
								$result .= \angela\Util::color_format($tab.str_replace("\n","\n".$tab,ob_get_contents()),"33");
							ob_end_clean();
							$result .= "\n".$tab.str_repeat("=",70)."\n";

							ob_start();
								var_dump($r2);
								$result .= \angela\Util::color_format($tab.str_replace("\n","\n".$tab,ob_get_contents()),"31");
							ob_end_clean();
							$result .= "\n".$tab.str_repeat("=",70)."\n";
							break;
						case 'exception':
							$exception++;
							list($file,$line,$pos,$msg) = $info[2];

							$result .= "\n";
							$result .= $file."\n";
							$result .= str_repeat("-",80)."\n";
							
							$result .= '['.$line.']: '.\angela\Util::color_format('error','1;31')."\n";
							$result .= $tab.str_repeat("=",70)."\n";
							$result .= \angela\Util::color_format($tab.$pos,33)."\n";
							$result .= \angela\Util::color_format($tab.$msg,31);
							$result .= "\n".$tab.str_repeat("=",70)."\n";
							break;
					}
				}
			}
		}
		$result .= "\n";
		$result .= \angela\Util::color_format(" success: ".$success." ","7;32")
		." ".\angela\Util::color_format(" failures: ".$fail." ","7;31")
		." ".\angela\Util::color_format(" errors: ".$exception." ","7;31")
		.sprintf(' ( %.05f sec / %s MByte ) ',$total_time,round(number_format((memory_get_usage() / 1024 / 1024),3),4));

		print($result.PHP_EOL);

		if(!empty($error_str)){
			print(PHP_EOL.\angela\Util::color_format('Errors','1;31').PHP_EOL);
			print(\angela\Util::color_format($error_str,'0;31'));
		}
		return (empty($fail) && empty($exception));
	}
	/**
	 * テスト結果をXMLで返す
	 * @param array $result_list
	 * @param string $system_err
	 * @return string
	 */
	static public function xml_result($result_list,$system_err){
		$xml = new \SimpleXMLElement('<testsuites></testsuites>');
		if(!empty($name)) $xml->addAttribute('name',$name);
		
		$count = $success = $fail = $none = $exception = $alltime = 0;
		foreach($result_list as $file => $f){
			
			$case = $xml->addChild('testsuite');
			$case->addAttribute('name',$file);
			$case->addAttribute('file',$file);
		
			foreach($f as $method => $info_list){
				foreach($info_list as $info){
					$time = $info[1];
					$name = $info[3];
					$alltime += $time;
					$count++;
					
					switch($info[0]){
						case 'none':
							$none++;
							break;
						case 'success':
							$success++;
	
							$x = $case->addChild('testcase');
							$x->addAttribute('name',$method);
							$x->addAttribute('file',$file);
							$x->addAttribute('time',$time);
							break;
						case 'fail':
							$fail++;
							list($file,$line,$r1,$r2) = $info[2];
							ob_start();
								var_dump($r2);
							$failure_value = 'Line. '.$line.': '."\n".ob_get_clean();
	
							$x = $case->addChild('testcase');
							$x->addAttribute('name',$method);
							$x->addAttribute('file',$file);
							$x->addAttribute('time',$time);
							$x->addAttribute('line',$line);
							$x->addChild('failure',$failure_value);
							break;
						case 'exception':
							$exception++;
							list($file,$line,$pos,$msg) = $info[2];
							$error_value = 'Line. '.$line.': '.$msg;
	
							$x = $case->addChild('testcase');
							$x->addAttribute('name',$method);
							$x->addAttribute('file',$file);
							$x->addAttribute('time',$time);
							$x->addAttribute('line',$line);
	
							$error = $x->addChild('error',$error_value);
							$error->addAttribute('line',$line);
							break;
					}
				}
			}
		}
		$xml->addAttribute('failures',$fail);
		$xml->addAttribute('tests',$count);
		$xml->addAttribute('errors',$exception);
		$xml->addAttribute('skipped',$none);
		$xml->addAttribute('time',$alltime);
		$xml->addChild('system-out');
		$xml->addChild('system-err',$system_err);

		return $xml->asXML();
	}
}
}

/**
 * main
 */
namespace{
ini_set('display_errors','On');
ini_set('html_errors','Off');
ini_set('error_reporting',E_ALL);
ini_set('xdebug.var_display_max_children',-1);
ini_set('xdebug.var_display_max_data',-1);
ini_set('xdebug.var_display_max_depth',-1);

if(ini_get('date.timezone') == ''){
	date_default_timezone_set('Asia/Tokyo');
}
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
set_error_handler(function($n,$s,$f,$l){
	throw new \ErrorException($s,0,$n,$f,$l);
});

$params = array();
$conf = array();
$argv = array_slice($_SERVER['argv'],1);
$value = (empty($argv)) ? null : array_shift($argv);

if(substr($value,0,1) == '-'){
	array_unshift($argv,$value);
	$value = null;
}
for($i=0;$i<sizeof($argv);$i++){
	if($argv[$i][0] == '-'){
		$k = str_replace('-','_',substr($argv[$i],($argv[$i][1] == '-') ? 2 : 1));
		$v = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
		if(isset($params[$k]) && !is_array($params[$k])) $params[$k] = array($params[$k]);
		$params[$k] = (isset($params[$k])) ? array_merge($params[$k],array($v)) : $v;
	}
}
if(isset($params['cc'])){
	$out = substr(__FILE__,0,-4).'.cc.php';
	file_put_contents($out,'<?'.'php'.PHP_EOL.
<<< '_SRC_'
if(extension_loaded('xdebug')){
	$coverage_vars = isset($_POST['_coverage_vars_']) ? $_POST['_coverage_vars_'] : 
						(isset($_GET['_coverage_vars_']) ? $_GET['_coverage_vars_'] : array());	
	if(isset($_POST['_coverage_vars_'])) unset($_POST['_coverage_vars_']);
	if(isset($_GET['_coverage_vars_'])) unset($_GET['_coverage_vars_']);

	if(isset($coverage_vars['savedb']) && is_file($coverage_vars['savedb'])){
		register_shutdown_function(function() use($coverage_vars){
			$savedb = $coverage_vars['savedb'];
			if(is_file($savedb) && $db = new \PDO('sqlite:'.$savedb)){
				foreach(xdebug_get_code_coverage() as $file_path => $lines){
					$sql = 'select id,covered_line,ignore_line,active_len from coverage_info where file_path = ?';
					$ps = $db->prepare($sql);
					$ps->execute(array($file_path));
						
					if($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$id = (int)$resultset['id'];
						$active_len = (int)$resultset['active_len'];
						$ignore_line = explode(',',$resultset['ignore_line']);
						$covered_line = empty($resultset['covered_line']) ? array() : explode(',',$resultset['covered_line']);
						$covered_line = array_merge(array_keys($lines),$covered_line);
						$covered_line = array_unique($covered_line);
						sort($covered_line);
							
						$covered_len = sizeof(array_diff($covered_line,$ignore_line));
						$percent = ($active_len === 0) ? 100 : (($covered_len === 0) ? 0 : (floor($covered_len / $active_len * 100)));
						if($percent > 100) $percent = 100;
							
						$ps = $db->prepare('update coverage_info set covered_line=?,percent=? where id=?');
						$ps->execute(array(implode(',',$covered_line),$percent,$id));
					}
				}
			}
			xdebug_stop_code_coverage();			
		});
		xdebug_start_code_coverage();
	}
}
_SRC_
	);
	print('output: '.$out.PHP_EOL);
	exit;
}

$rootdir = (basename(__DIR__) == 'test') ? dirname(__DIR__) : __DIR__;
$conf = array();

// load app conf
if(is_file($f=$rootdir.'/bootstrap.php') || is_file($f=$rootdir.'/vendor/autoload.php')){
	ob_start();
		include_once($f);
	ob_end_clean();
}
// test init
\angela\Conf::init($rootdir,$params,substr(__FILE__,0,-4).'.conf.php');
\angela\Conf::info(isset($value));


/**
 * 検証用メソッドの省略関数
 */
{
	/**
	 * 失敗
	 * @param string $msg
	 */
	function fail($msg='failure'){
		$assert = new \angela\Assert();
		$assert->failure($msg);
	}
	/**
	 *　等しい
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 */
	function eq($expectation,$result){
		list($debug) = debug_backtrace(false);
		$assert = new \angela\Assert();
		$assert->equals($expectation,$result,$debug['file'],$debug['line']);
		return true;
	}
	/**
	 * 等しくない
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 */
	function neq($expectation,$result){
		list($debug) = debug_backtrace(false);
		$assert = new \angela\Assert();
		$assert->not_equals($expectation,$result,$debug['file'],$debug['line']);
		return true;
	}
	/**
	 *　文字列中に指定した文字列がすべて存在していれば成功
	 * @param string|array $keyword
	 * @param string $src
	 */
	function meq($keyword,$src){
		list($debug) = debug_backtrace(false);
		$assert = new \angela\Assert();
		$assert->match_equals($keyword,$src,$debug['file'],$debug['line']);
		return true;
	}
	/**
	 *　文字列中に指定した文字列がすべて存在していなければ成功
	 * @param string|array $keyword
	 * @param string $src
	 */
	function mneq($keyword,$src){
		list($debug) = debug_backtrace(false);
		$assert = new \angela\Assert();
		$assert->match_not_equals($keyword,$src,$debug['file'],$debug['line']);
		return true;
	}
	/**
	 * mapに定義されたurlをフォーマットして返す
	 * @param string $name
	 * @return string
	 */
	function test_map_url($map_name){
		$args = func_get_args();
		if(strpos($args[0],'::') == false) $args[0] = \angela\Runner::current_entry().'::'.$args[0];
		return call_user_func_array(array('\angela\Conf','map_url'),$args);
	}
	/**
	 * Httpリクエスト
	 * @return angela.Http
	 */
	function b($agent=null,$timeout=30,$redirect_max=20){
		return new \angela\Http($agent,$timeout,$redirect_max);
	}	
}
/**
 * Help
 */
if(isset($params['h']) || isset($params['help'])){
	print('usage: php '.basename(__FILE__).' [-t target] [-m method] [-b block] [-c coverage.xml] [-o result.xml]'.PHP_EOL);
	print('       php '.basename(__FILE__).' --cc'.PHP_EOL);
	print('       php '.basename(__FILE__).' --help'.PHP_EOL);
	print(PHP_EOL);
	exit;
}


$value = (isset($params['t']) ? $params['t'] : $value);
$coverage = isset($params['c']) ? \angela\Util::absolute(getcwd(),$params['c']) : null;

if(!empty($coverage)){
	if(!extension_loaded('xdebug')) die('xdebug extension not loaded'.PHP_EOL);
	file_put_contents($coverage,'');
	$db = dirname($coverage).'/'.microtime(true).'.coverage.db';
	\angela\Coverage::start($db,\angela\Conf::lib_dir());
}
if(!empty($value)){
	\angela\Runner::varidate(
			$value
			,(isset($params['m']) ? $params['m'] : null)
			,(isset($params['b']) ? $params['b'] : null)
			,true
	);
}else{
	\angela\Runner::run_all();
}
$output_obj = new \angela\Output();
$output_obj->result(\angela\Runner::get(),\angela\Conf::error_log());
if(isset($params['o']) && !empty($params['o'])){
	$filename = \angela\Util::absolute(getcwd(),$params['o']);
	file_put_contents($filename, $output_obj->xml_result(\angela\Runner::get(),\angela\Conf::error_log()));
	print(\angela\Util::color_format('Written XML: '.$filename.' ','1;34').PHP_EOL);
}
if(!empty($coverage)){
	\angela\Coverage::stop();

	$output_obj->coverage(\angela\Coverage::get());
	file_put_contents($coverage, $output_obj->xml_coverage(\angela\Coverage::get()));
	print(\angela\Util::color_format('Written XML: '.$coverage.' ','1;34').PHP_EOL);
	
	\angela\Coverage::delete();
}
exit(\angela\Runner::has_exception() ? 1 : 0);
}
