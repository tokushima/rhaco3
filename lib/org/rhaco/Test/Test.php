<?php
namespace org\rhaco;
include_once(__DIR__.'/funcs.php');
/**
 * テスト処理
 * @author tokushima
 */
class Test{
	static private $result = array();
	static private $current_entry;
	static private $current_class;
	static private $current_method;
	static private $current_file;
	static private $start_time;
	static private $flow_output_maps;

	/**
	 * 結果を取得する
	 * @return string{}
	 */
	final static public function get(){
		return self::$result;
	}
	/**
	 * 結果をクリアする
	 */
	final static public function clear(){
		self::$result = array();
		self::$start_time = microtime(true);
	}
	/**
	 * 開始時間
	 * @return integer
	 */
	final static public function start_time(){
		if(self::$start_time === null) self::$start_time = microtime(true);
		return self::$start_time;
	}
	/**
	 * 現在実行中のエントリ
	 * @return string
	 */
	final static public function current_entry(){
		return self::$current_entry;
	}
	/**
	 * 判定を行う
	 * @param mixed $arg1 期待値
	 * @param mixed $arg2 実行結果
	 * @param boolean 真偽どちらで判定するか
	 * @param int $line 行番号
	 * @param string $file ファイル名
	 * @return boolean
	 */
	final static public function equals($arg1,$arg2,$eq,$line,$file=null){
		$result = ($eq) ? (self::expvar($arg1) === self::expvar($arg2)) : (self::expvar($arg1) !== self::expvar($arg2));
		self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = ($result) ? array() : array(var_export($arg1,true),var_export($arg2,true));
		return $result;
	}
	/**
	 * メッセージを登録
	 * @param string $msg メッセージ
	 * @param int $line 行番号
	 * @param string $file ファイル名
	 */
	final static public function notice($msg,$line,$file=null){
		self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = array('notice',$msg,$file,$line);
	}
	/**
	 * 取得済みのFlowの定義を返す
	 * @param string $entry_name
	 * @return array
	 */
	static public function flow_output_maps($entry_name=null){
		return (isset($entry_name)) ? (isset(self::$flow_output_maps[$entry_name]) ? self::$flow_output_maps[$entry_name] : null) : self::$flow_output_maps;
	}
	/**
	 * テスト対象の取得
	 * @return string[]
	 */
	static public function search_path(){
		$cwd = str_replace("\\",'/',getcwd()).'/';
		return array($cwd,$cwd.'tests/',$cwd.'lib/');
	}
	/**
	 * テストを実行する
	 * @param string $class_name クラス名
	 * @param string $method メソッド名
	 * @param string $block_name ブロック名
	 * @param boolean $print_progress 実行中のブロック名を出力するか
	 */
	static final public function run($class_name,$method_name=null,$block_name=null,$print_progress=false){
		list($entry_path,$tests_path) = self::search_path();
		if(class_exists($f=((substr($class_name,0,1) != "\\") ? "\\" : '').str_replace('.',"\\",$class_name),true) || interface_exists($f)){
			$doctest = ((strpos($f,"\\") !== false || ctype_upper(substr($class_name,0,1))) ? self::get_doctest($f) : self::get_func_doctest($f));
		}else if(is_file($class_name)){
			$doctest = (strpos($class_name,'/tests/') === false) ? self::get_entry_doctest($class_name) : self::get_unittest($class_name);
		}else{
			if(is_file($f=$entry_path.$class_name.'.php')){
				$doctest = self::get_entry_doctest($f);
			}else if(is_file($f=$tests_path.str_replace('.','/',$class_name).'.php')){
				$doctest = self::get_unittest($f);
			}else{
				throw new \ErrorException($class_name.' not found');
			}
		}
		if(!isset(self::$flow_output_maps)){
			self::$flow_output_maps = array();
			foreach(new \RecursiveDirectoryIterator($entry_path,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $e){
				if(substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false){
					$src = file_get_contents($e->getFilename());
					if((strpos($src,"\\org\\rhaco\\Flow") !== false && (strpos($src,'->output(') !== false || strpos($src,'::out(') !== false))){
						$entry_name = substr($e->getFilename(),0,-4);
						foreach(\org\rhaco\Flow::get_maps($e->getPathname()) as $p => $m){
							if(isset($m['name'])) self::$flow_output_maps[$entry_name][$m['name']] = $m;
						}
					}
				}
			}
		}
		self::$current_file = $doctest['filename'];
		self::$current_class = ($doctest['type'] == 1) ? $doctest['name'] : null;
		self::$current_entry = ($doctest['type'] == 2 || $doctest['type'] == 3) ? $doctest['name'] : null;
		self::$current_method = null;
		
		foreach($doctest['tests'] as $test_method_name => $tests){
			if($method_name === null || $method_name === $test_method_name){
				self::$current_method = $test_method_name;
				
				if(empty($tests['blocks'])){
					self::$result[self::$current_file][self::$current_class][self::$current_method][$tests['line']][] = array('none');
				}else{
					foreach($tests['blocks'] as $test_block){
						list($name,$block) = $test_block;
						$exec_block_name = ' ::'.basename($name);

						if($block_name === null || $block_name === $name){
							if($print_progress && substr(PHP_OS,0,3) != 'WIN') print($exec_block_name);
							try{
								ob_start();
								if($doctest['type'] == 3){
									if(is_file($f=(dirname($doctest['filename']).'/__setup__.php'))) include($f);
									include($doctest['filename']);
									if(is_file($f=(dirname($doctest['filename']).'/__teardown__.php'))) include($f);
								}else{
									if(isset($tests['__setup__'])) eval($tests['__setup__'][1]);
									eval($block);
									if(isset($tests['__teardown__'])) eval($tests['__teardown__'][1]);
								}
								$result = ob_get_clean();
								if(preg_match("/(Parse|Fatal) error:.+/",$result,$match)){
									$err = (preg_match('/syntax error.+code on line\s*(\d+)/',$result,$line) ? 
												'Parse error: syntax error '.$doctest['filename'].' code on line '.$line[1]
												: $match[0]);
									throw new \ErrorException($err);
								}
							}catch(\Exception $e){
								if(ob_get_level() > 0) $result = ob_get_clean();
								list($message,$file,$line) = array($e->getMessage(),$e->getFile(),$e->getLine());
								$trace = $e->getTrace();
								$eval = false;

								foreach($trace as $k => $t){
									if(isset($t['class']) && isset($t['function']) && ($t['class'].'::'.$t['function']) == __METHOD__ && isset($trace[$k-2])
										&& $trace[$k-1]['file'] == __FILE__ && isset($trace[$k-1]['function']) && $trace[$k-1]['function'] == 'eval'
									){
										$file = self::$current_file;
										$line = $trace[$k-2]['line'];
										$eval = true;
										break;
									}
								}
								if(!$eval && self::$current_file == $trace[0]['file']){
									$file = $trace[0]['file'];
									$line = $trace[0]['line'];
								}
								self::$result[self::$current_file][self::$current_class][self::$current_method][$line][] = array('exception',$message,$file,$line);
								\org\rhaco\Log::error($e);
							}
							if($print_progress && substr(PHP_OS,0,3) != 'WIN') print("\033[".strlen($exec_block_name).'D'."\033[0K");
							\org\rhaco\Exceptions::clear();
						}
					}
				}
			}
		}
		if($doctest['type'] == 1 || $doctest['type'] == 2){
			$test_name = ($doctest['type'] == 1) ? str_replace("\\",'/',substr($doctest['name'],1)) : $doctest['name'];
			if(!empty($test_name) && is_dir($d=($tests_path.str_replace(array('.'),'/',$test_name)))){
				foreach(new \RecursiveDirectoryIterator($d,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $e){
					if(substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false
						&& ($block_name === null || $block_name === substr($e->getFilename(),0,-4) || $block_name === $e->getFilename())
					){
						self::run($e->getPathname(),null,null,$print_progress);
					}
				}
			}
		}
		return new self();
	}
	/**
	 * 
	 * テスト結果をXMLで取得する
	 * @return org.rhaco.Xml
	 */
	static public function xml($name=null,$system_err=null){
		$xml = new \org\rhaco\Xml('testsuite');
		if(!empty($name)) $xml->attr('name',$name);
		
		$count = $success = $fail = $none = $exception = 0;
		foreach(\org\rhaco\Test::get() as $file => $f){
			$case = new \org\rhaco\Xml('testcase');
			$case->close_empty(false);
			$case->attr('name',$file);
			
			foreach($f as $class => $c){
				foreach($c as $method => $m){
					foreach($m as $line => $r){
						foreach($r as $l){
							$count++;
							switch(sizeof($l)){
								case 0:
									$success++;
									break;
								case 1:
									$none++;
									break;
								case 2:
									$fail++;
									$x = new \org\rhaco\Xml('failure');
									$x->attr('line',$line);
									ob_start();
										var_dump($l[1]);
										$content = ob_get_contents();
									ob_end_clean();
									$x->value('Line. '.$line.' '.$method.': '."\n".$content);
									$case->add($x);
									break;
								case 4:
									$exception++;
									$x = new \org\rhaco\Xml('failure');
									$x->attr('line',$line);
									$x->value(
											'Line. '.$line.' '.$method.': '.$l[0]."\n".
											$l[1]."\n\n".$l[2].':'.$l[3]
									);
									$case->add($x);
									break;
							}
						}
					}
				}
			}		
			$xml->add($case);
		}
		$xml->attr('failures',$fail);
		$xml->attr('tests',$count);
		$xml->attr('errors',$exception);
		$xml->attr('skipped',$none);
		$xml->attr('time',round((microtime(true) - (float)\org\rhaco\Test::start_time()),4));
		$xml->add(new \org\rhaco\Xml('system-out'));
		$xml->add(new \org\rhaco\Xml('system-err',$system_err));
		return $xml;
	}
	public function __toString(){
		$result = '';
		$tab = '  ';
		$success = $fail = $none = 0;

		foreach(self::$result as $file => $f){
			foreach($f as $class => $c){
				$print_head = false;

				foreach($c as $method => $m){
					foreach($m as $line => $r){
						foreach($r as $l){
							switch(sizeof($l)){
								case 0:
									$success++;
									break;
								case 1:
									$none++;
									break;
								case 2:
									$fail++;
									if(!$print_head){
										$result .= "\n";
										$result .= (empty($class) ? "*****" : str_replace("\\",'.',(substr($class,0,1) == "\\") ? substr($class,1) : $class))." [ ".$file." ]\n";
										$result .= str_repeat("-",80)."\n";
										$print_head = true;
									}
									$result .= "[".$line."]".$method.": ".self::fcolor("fail","1;31")."\n";
									$result .= $tab.str_repeat("=",70)."\n";
									ob_start();
										var_dump($l[0]);
										$result .= self::fcolor($tab.str_replace("\n","\n".$tab,ob_get_contents()),"33");
									ob_end_clean();
									$result .= "\n".$tab.str_repeat("=",70)."\n";

									ob_start();
										var_dump($l[1]);
										$result .= self::fcolor($tab.str_replace("\n","\n".$tab,ob_get_contents()),"31");
									ob_end_clean();
									$result .= "\n".$tab.str_repeat("=",70)."\n";
									break;
								case 4:
									$fail++;
									if(!$print_head){
										$result .= "\n";
										$result .= (empty($class) ? "*****" : str_replace("\\",'.',(substr($class,0,1) == "\\") ? substr($class,1) : $class))." [ ".$file." ]\n";
										$result .= str_repeat("-",80)."\n";
										$print_head = true;
									}
									$color = ($l[0] == 'exception') ? 31 : 34;
									$result .= "[".$line."]".$method.": ".self::fcolor($l[0],"1;".$color)."\n";
									$result .= $tab.str_repeat("=",70)."\n";
									$result .= self::fcolor($tab.$l[1]."\n\n".$tab.$l[2].":".$l[3],$color);
									$result .= "\n".$tab.str_repeat("=",70)."\n";
									break;
							}
						}
					}
				}
			}
		}
		$result .= "\n";
		$result .= self::fcolor(" success: ".$success." ","7;32")." ".self::fcolor(" fail: ".$fail." ","7;31")." ".self::fcolor(" none: ".$none." ","7;35")
					.sprintf(' ( %s sec / %s MByte) ',round((microtime(true) - (float)self::start_time()),4),round(number_format((memory_get_usage() / 1024 / 1024),3),2));
		$result .= "\n";
		self::clear();
		return $result;
	}
	final static private function get_unittest($filename){
		$result = array();
		$result['@']['line'] = 0;
		$result['@']['blocks'][] = array($filename,$filename,0);
		$name = (preg_match("/^.+\/tests\/(.+)\/[^\/]+\.php$/",$filename,$match)) ? $match[1] : null;
		return array('filename'=>$filename,'type'=>3,'name'=>$name,'tests'=>$result);
	}
	final static private function get_entry_doctest($filename){
		$result = array();
		$entry = basename($filename,'.php');
		$src = file_get_contents($filename);
		if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$src,$doctests,PREG_OFFSET_CAPTURE)){
			foreach($doctests[0] as $doctest){
				if(isset($doctest[0][5]) && $doctest[0][5] != '*'){
					$test_start_line = sizeof(explode("\n",substr($src,0,$doctest[1]))) - 1;
					$test_block = str_repeat("\n",$test_start_line).preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array("/"."***","*"."/"),"",$doctest[0]));
					$test_block_name = preg_match("/^[\s]*#(.+)/",$test_block,$match) ? trim($match[1]) : null;
					if(trim($test_block) == '') $test_block = null;
					$result['@']['line'] = $test_start_line;
					$result['@']['blocks'][] = array($test_block_name,$test_block,$test_start_line);
				}
			}
			self::merge_setup_teardown($result);
		}
		return array('filename'=>$filename,'type'=>2,'name'=>$entry,'tests'=>$result);
	}
	final static private function get_func_doctest($func_name){
		$result = array();
		$r = new \ReflectionFunction($func_name);
		$filename = $r->getFileName();
		$src_lines = file($filename);
		$func_src = implode('',array_slice($src_lines,$r->getStartLine()-1,$r->getEndLine()-$r->getStartLine(),true));				

		if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$func_src,$doctests,PREG_OFFSET_CAPTURE)){
			foreach($doctests[0] as $doctest){
				if(isset($doctest[0][5]) && $doctest[0][5] != "*"){
					$test_start_line = $r->getStartLine() - 1;
					$test_block = str_repeat("\n",$test_start_line).preg_replace("/([^\w_])self\(/","\\1".$func_name.'(',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array("/"."***","*"."/"),"",$doctest[0])));
					$test_block_name = preg_match("/^[\s]*#(.+)/",$test_block,$match) ? trim($match[1]) : null;
					if(trim($test_block) == '') $test_block = null;
					$result[$func_name]['line'] = $r->getStartLine();
					$result[$func_name]['blocks'][] = array($test_block_name,$test_block,$test_start_line);
				}
			}
		}else if($is_public && $method_name[0] != '_'){
			$result[$method_name]['line'] = $r->getStartLine();
			$result[$method_name]['blocks'] = array();
		}
		return array('filename'=>$filename,'type'=>4,'name'=>null,'tests'=>$result);
	}
	final static private function get_doctest($class_name){
		$result = array();
		$rc = new \ReflectionClass($class_name);
		$filename = $rc->getFileName();
		$class_src_lines = file($filename);
		$class_src = implode('',$class_src_lines);

		foreach($rc->getMethods() as $method){
			if($method->getDeclaringClass()->getName() == $rc->getName()){
				$method_src = implode('',array_slice($class_src_lines,$method->getStartLine()-1,$method->getEndLine()-$method->getStartLine(),true));				
				$result = array_merge($result,self::get_method_doctest($rc->getName(),$method->getName(),$method->getStartLine(),$method->isPublic(),$method_src));
				$class_src = str_replace($method_src,str_repeat("\n",sizeof(explode("\n",$method_src)) - 1),$class_src);
			}
		}
		$result = array_merge($result,self::get_method_doctest($rc->getName(),'@',1,false,$class_src));
		if(basename(dirname($filename)) === basename($filename,'.php')){
			$dirpath = dirname($filename);

			foreach(new \RecursiveDirectoryIterator($dirpath,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $e){
				if(preg_match('/\/test([^\/]*)\.php$/',str_replace('\\','/',$e->getPathName()))){
					$test_file = $e->getPathName();
					$test_name = basename($e->getPathName());
					$test_src = str_replace(array('__DIR__','__FILE__'),array("'".$dirpath."'","'".$e->getPathName()."'"),file_get_contents($test_file));
					$result[$test_name]['line'] = 1;
					$result[$test_name]['blocks'][] = array('@',str_replace(array('<?php','?>'),array('    ','  '),$test_src));
				}
			}
			if(is_dir($dirpath.'/test')){
				foreach(new \RecursiveDirectoryIterator($dirpath.'/test',\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $e){
					if(substr($e->getPathName(),-4) == '.php'){
						$test_file = $e->getPathName();
						$test_name = substr(basename($e->getPathName()),0,-4);
						$test_src = str_replace(array('__DIR__','__FILE__'),array("'".dirname($e->getPathName())."'","'".$e->getPathName()."'"),file_get_contents($test_file));
						$result[$test_name]['line'] = 1;
						$result[$test_name]['blocks'][] = array('@',str_replace(array('<?php','?>'),array('    ','  '),$test_src));
					}
				}
			}
		}
		self::merge_setup_teardown($result);
		return array('filename'=>$filename,'type'=>1,'name'=>$rc->getName(),'tests'=>$result);
	}
	final static private function merge_setup_teardown(&$result){
		if(isset($result['@']['blocks'])){
			foreach($result['@']['blocks'] as $k => $block){
				if($block[0] == '__setup__' || $block[0] == '__teardown__'){
					$result['@'][$block[0]] = array($result['@']['blocks'][$k][2],$result['@']['blocks'][$k][1]);
					unset($result['@']['blocks'][$k]);
				}
			}
		}
	}
	final static private function get_method_doctest($class_name,$method_name,$method_start_line,$is_public,$method_src){
		$result = array();
		if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$method_src,$doctests,PREG_OFFSET_CAPTURE)){
			foreach($doctests[0] as $doctest){
				if(isset($doctest[0][5]) && $doctest[0][5] != "*"){
					$test_start_line = $method_start_line + substr_count(substr($method_src,0,$doctest[1]),"\n") - 1;
					$test_block = str_repeat("\n",$test_start_line).str_replace(array('self::','new self(','extends self{'),array($class_name.'::','new '.$class_name.'(','extends '.$class_name.'{'),preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."***","*"."/"),"",$doctest[0])));
					$test_block_name = preg_match("/^[\s]*#(.+)/",$test_block,$match) ? trim($match[1]) : null;
					if(trim($test_block) == '') $test_block = null;
					$result[$method_name]['line'] = $method_start_line;
					$result[$method_name]['blocks'][] = array($test_block_name,$test_block,$test_start_line);
				}
			}
		}else if($is_public && $method_name[0] != '_'){
			$result[$method_name]['line'] = $method_start_line;
			$result[$method_name]['blocks'] = array();
		}
		return $result;
	}
	final static private function expvar($var){
		if(is_numeric($var)) return strval($var);
		if(is_object($var)) $var = get_object_vars($var);
		if(is_array($var)){
			foreach($var as $key => $v){
				$var[$key] = self::expvar($v);
			}
		}
		return $var;
	}
	static private function fcolor($msg,$color='30'){
		return (php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg;
	}
}
