<?php
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

return array(
	'urls'=>\org\rhaco\Dt::get_urls(),
	'setup_func'=>function(){
		\org\rhaco\Exceptions::clear();
	},
	'output_dir'=>dirname(__DIR__).'/work/test_output',
);

