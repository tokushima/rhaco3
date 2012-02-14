<?php
/**
 * テスト処理
 * @author tokushima
 */
class Doctest{
	static private $result = array();
	static private $current_class;
	static private $current_method;
	static private $current_file;
	static private $start_time;

	/**
	 * 結果を取得する
	 * @return string{}
	 */
	final public function get(){
		return self::$result;
	}
	/**
	 * 結果をクリアする
	 */
	final public static function clear(){
		self::$result = array();
		self::$start_time = microtime(true);
	}
	/**
	 * 開始時間
	 * @return integer
	 */
	static public function start_time(){
		if(self::$start_time === null) self::$start_time = microtime(true);
		return self::$start_time;
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
	final public static function equals($arg1,$arg2,$eq,$line,$file=null){
		$result = ($eq) ? (self::expvar($arg1) === self::expvar($arg2)) : (self::expvar($arg1) !== self::expvar($arg2));
		self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = ($result) ? array() : array(var_export($arg1,true),var_export($arg2,true));
		return $result;
	}
	/**
	 * テストを実行する
	 * @param string $class_name クラス名
	 * @param string $method メソッド名
	 * @param string $block_name ブロック名
	 */
	static final public function run($class_name,$method_name=null,$block_name=null){
		if(!class_exists($class_name,true) && !function_exists($class_name)){
			$name = '';
			foreach(explode('_',$class_name) as $n) $name = $name.ucfirst($n);
			if(!class_exists($name,true)) throw new ErrorException($class_name.' not found');
			$class_name = $name;
		}
		if(class_exists($class_name)) $class_name = ucfirst($class_name);
		$doctest = (ctype_upper(substr($class_name,0,1))) ? self::get_doctest($class_name) : self::get_func_doctest($class_name);
		self::$current_file = $doctest['filename'];
		self::$current_class = $doctest['class_name'];
		self::$current_method = null;

		foreach($doctest['tests'] as $test_method_name => $tests){
			if($method_name === null || $method_name === $test_method_name){
				self::$current_method = $test_method_name;

				if(empty($tests['blocks'])){
					self::$result[self::$current_file][self::$current_class][self::$current_method][$tests['line']][] = array('none');
				}else{
					foreach($tests['blocks'] as $test_block){
						list($name,$block) = $test_block;
						if($block_name === null || $block_name === $name){
							try{
								ob_start();
								if(isset($tests['__setup__'])) eval($tests['__setup__'][1]);
								eval($block);
								if(isset($tests['__teardown__'])) eval($tests['__teardown__'][1]);
								$result = ob_get_clean();
								if(preg_match("/(Parse|Fatal) error:.+/",$result,$match)) throw new ErrorException($match[0]);
							}catch(Exception $e){
								if(ob_get_level() > 0) $result = ob_get_clean();
								list($message,$file,$line) = array($e->getMessage(),$e->getFile(),$e->getLine());
								$trace = $e->getTrace();
								foreach($trace as $k => $t){
									if(isset($t['class']) && isset($t['function']) && ($t['class'].'::'.$t['function']) == __METHOD__ && isset($trace[$k-2])
										&& $trace[$k-1]['file'] == __FILE__ && isset($trace[$k-1]['function']) && $trace[$k-1]['function'] == 'eval'
									){
										$file = self::$current_file;
										$line = $trace[$k-2]['line'];
										break;
									}
								}
								self::$result[self::$current_file][self::$current_class][self::$current_method][$line][] = array("exception",$message,$file,$line);
							}
						}
					}
				}
			}
		}
		return new self();
	}
	public function __toString(){
		$result = "";
		$tab = "  ";
		$success = $fail = $none = 0;
		$cli = (isset($_SERVER['argc']) && !empty($_SERVER['argc']) && substr(PHP_OS,0,3) != 'WIN');

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
										$result .= (empty($class) ? "*****" : $class)." [ ".$file." ]\n";
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
										$result .= (empty($class) ? "*****" : $class)." [ ".$file." ]\n";
										$result .= str_repeat("-",80)."\n";
										$print_head = true;
									}
									$result .= "[".$line."]".$method.": ".self::fcolor("exception","1;31")."\n";
									$result .= $tab.str_repeat("=",70)."\n";
									$result .= self::fcolor($tab.$l[1]."\n\n".$tab.$l[2].":".$l[3],"31");
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
	final static private function get_func_doctest($func_name){
		$result = array();
		$r = new ReflectionFunction($func_name);
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
		return array('filename'=>$filename,'class_name'=>null,'tests'=>$result);
	}
	final static private function get_doctest($class_name){
		$result = array();
		$rc = new ReflectionClass($class_name);
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
		self::merge_setup_teardown($result);

		return array('filename'=>$filename,'class_name'=>$class_name,'tests'=>$result);
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
	static private function fcolor($msg,$color="30"){
		return (php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg;
	}
}
Doctest::start_time();
/**
 *　等しい
 * @param mixed $expectation 期待値
 * @param mixed $result 実行結果
 * @return boolean 期待通りか
 */
function eq($expectation,$result){
	list($debug) = debug_backtrace(false);
	return Doctest::equals($expectation,$result,true,$debug["line"],$debug["file"]);
}
/**
 * 等しくない
 * @param mixed $expectation 期待値
 * @param mixed $result 実行結果
 * @return boolean 期待通りか
 */
function neq($expectation,$result){
	list($debug) = debug_backtrace(false);
	return Doctest::equals($expectation,$result,false,$debug["line"],$debug["file"]);
}
/**
 * 成功
 */
function success(){
	list($debug) = debug_backtrace(false);
	Doctest::equals(true,true,true,$debug["line"],$debug["file"]);
}
/**
 * 失敗
 */
function fail(){
	list($debug) = debug_backtrace(false);
	Doctest::equals(false,true,true,$debug["line"],$debug["file"]);
}
/**
 * ユニークな名前でクラスを生成しインスタンスを返す
 * @param string $class クラスのソース
 * @return object
 */
function newclass($class){
	$class_name = '_';
	foreach(debug_backtrace() as $d) $class_name .= (empty($d['file'])) ? '' : '__'.basename($d['file']).'_'.$d['line'];
	$class_name = substr(preg_replace("/[^\w]/","",str_replace('.php','',$class_name)),0,100);

	for($i=0,$c=$class_name;;$i++,$c=$class_name.'_'.$i){
		if(!class_exists($c)){
			$args = func_get_args();
			array_shift($args);
			$doc = null;
			if(strpos($class,'-----') !== false){
				list($doc,$class) = preg_split("/----[-]+/",$class,2);
				$doc = "/**\n".trim($doc)."\n*/\n";
			}
			call_user_func(create_function('',$doc.vsprintf(preg_replace("/\*(\s+class\s)/","*/\\1",preg_replace("/class\s\*/",'class '.$c,trim($class))),$args)));
			return new $c;
		}
	}
}
/**
 * ヒアドキュメントのようなテキストを生成する
 * １行目のインデントに合わせてインデントが消去される
 * @param string $text 対象の文字列
 * @return string
 */
function pre($text){
	if(!empty($text)){
		$lines = explode("\n",$text);
		if(sizeof($lines) > 2){
			if(trim($lines[0]) == '') array_shift($lines);
			if(trim($lines[sizeof($lines)-1]) == '') array_pop($lines);
			return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode("\n",$lines)) : implode("\n",$lines);
		}
	}
	return $text;
}
if(isset($_SERVER['argv'][1]) && sizeof(debug_backtrace()) == 0){
	try{
		$args = array();
		$argv = $_SERVER['argv'];
		array_shift($argv);
		$target = array_shift($argv);
		$op = array();

		$size = sizeof($argv);
		for($i=0;$i<$size;$i++){
			if($argv[$i][0] == '-'){
				if(isset($argv[$i+1]) && $argv[$i+1][0] != '-'){
					$k = substr($argv[$i],1);
					if(isset($op[$k])){
						if(!is_array($op[$k])) $op[$k] = array($op[$k]);
						$op[$k][] = $argv[$i+1];
					}else{
						$op[$k] = $argv[$i+1];
					}
					$i++;
				}else{
					$op[substr($argv[$i],1)] = '';
				}
			}
		}
		include($target);
		if(isset($op['i'])){
			foreach((is_array($op['i']) ? $op['i'] : array($op['i'])) as $i) include($i);
		}
		print(
			Doctest::run(
				preg_replace("/\.php/",'',basename($target))
				,(isset($op['m']) ? $op['m'] : null)
				,(isset($op['b']) ? $op['b'] : null)
			)
		);
	}catch(Exception $e){
		print($e->getMessage());
	}
}
ini_set('display_errors','On');
ini_set('html_errors','Off');
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if('neutral' == mb_language()) mb_language('Japanese');
mb_internal_encoding('UTF-8');
function error_handler($errno,$errstr,$errfile,$errline){
	if(strpos($errstr,'Use of undefined constant') !== false && preg_match("/\'(.+?)\'/",$errstr,$m) && class_exists($m[1])) return define($m[1],$m[1]);
	if(strpos($errstr,' should be compatible with that of') !== false || strpos($errstr,'Strict Standards') !== false) return true;
	throw new ErrorException($errstr,0,$errno,$errfile,$errline);
}
set_error_handler('error_handler');
