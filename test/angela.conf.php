<?php
/**
 * 互換用
 */
function r($obj){
	return $obj;
}
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
function xml(&$xml,$src,$name=null){
	if(\org\rhaco\Xml::set($xml,$src,$name)){
		return $xml;
	}
	\org\rhaco\Log::warn('none of the `'.$name.'` noodes:'.PHP_EOL.$src);
	throw new \RuntimeException('none of the `'.$name.'` noodes');
}
function success(){
}
function fail($msg='failure'){
}

return array(
	'urls'=>\org\rhaco\Dt::get_urls(),
	'setup_func'=>function(){
		\org\rhaco\Exceptions::clear();
	}
);

