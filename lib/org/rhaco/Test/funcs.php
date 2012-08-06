<?php
if(!function_exists('r')){
	function r($obj){
		return $obj;
	}
}
if(!function_exists('eq')){
	/**
	 *　等しい
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 * @return boolean 期待通りか
	 */
	function eq($expectation,$result){
		list($debug) = debug_backtrace(false);
		return \org\rhaco\Test::equals($expectation,$result,true,$debug["line"],$debug["file"]);
	}
}
if(!function_exists('neq')){
	/**
	 * 等しくない
	 * @param mixed $expectation 期待値
	 * @param mixed $result 実行結果
	 * @return boolean 期待通りか
	 */
	function neq($expectation,$result){
		list($debug) = debug_backtrace(false);
		return \org\rhaco\Test::equals($expectation,$result,false,$debug["line"],$debug["file"]);
	}
}
if(!function_exists('meq')){
	/**
	 *　文字列中に指定した文字列がすべて存在していれば成功
	 * @param string $keyword スペース区切りで複数可能
	 * @param string $src
	 * @return boolean
	 */
	function meq($keyword,$src){
		list($debug) = debug_backtrace(false);
		foreach(explode(' ',$keyword) as $q){
			if(mb_strpos($src,$q) === false) return \org\rhaco\Test::equals(true,false,true,$debug['line'],$debug['file']);
		}		
		return \org\rhaco\Test::equals(true,true,true,$debug['line'],$debug['file']);
	}
}
if(!function_exists('nmeq')){
	/**
	 *　文字列中に指定した文字列がすべて存在していなければ成功
	 * @param string $keyword スペース区切りで複数可能
	 * @param string $src
	 * @return boolean
	 */
	function nmeq($keyword,$src){
		list($debug) = debug_backtrace(false);
		foreach(explode(' ',$keyword) as $q){
			if(mb_strpos($src,$q) !== false) return \org\rhaco\Test::equals(true,false,true,$debug['line'],$debug['file']);
		}		
		return \org\rhaco\Test::equals(true,true,true,$debug['line'],$debug['file']);
	}
}

if(!function_exists('success')){
	/**
	 * 成功
	 */
	function success(){
		list($debug) = debug_backtrace(false);
		\org\rhaco\Test::equals(true,true,true,$debug['line'],$debug['file']);
	}
}
if(!function_exists('fail')){
	/**
	 * 失敗
	 */
	function fail($msg=null){
		throw new \LogicException('Test fail: '.$msg);
	}
}
if(!function_exists('notice')){
	/**
	 * メッセージ
	 */
	function notice($msg=null){
		list($debug) = debug_backtrace(false);
		\org\rhaco\Test::notice((($msg instanceof \Exception) ? $msg->getMessage()."\n\n".$msg->getTraceAsString() : (string)$msg),$debug['line'],$debug['file']);
	}
}
if(!function_exists('newclass')){
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
}
if(!function_exists('pre')){
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
}
if(!function_exists('test_map_url')){
	/**
	 * mapに定義されたurlをフォーマットして返す
	 * @param string $name
	 * @return string
	 */
	function test_map_url($name){
		list($entry,$map_name) = (strpos($name,'::') !== false) ? explode('::',$name,2) : array(\org\rhaco\Test::current_entry(),$name);
		$maps = \org\rhaco\Test::flow_output_maps($entry);
		$args = func_get_args();
		array_shift($args);
		if(isset($maps[$map_name]) && $maps[$map_name]['num'] == sizeof($args)) return vsprintf($maps[$map_name]['pattern'],$args);
		throw new \RuntimeException($name.'['.sizeof($args).'] not found');
	}
}
if(!function_exists('b')){
	/**
	 * Httpリクエスト
	 * @return org.rhaco.net.Http
	 */
	function b(){
		return new \org\rhaco\net\Http();
	}
}
if(!function_exists('xml')){
	/**
	 * XMLで取得する
	 * @param $xml 取得したXmlオブジェクトを格納する変数
	 * @param $src 対象の文字列
	 * @param $name ノード名
	 * @return boolean
	 */
	function xml(&$xml,$src,$name=null){
		return \org\rhaco\Xml::set($xml,$src,$name);
	}
}
