<?php
if(isset($_SERVER['argv'][1])){
	$op = $target = null;
	if($_SERVER['argv'][1][0] == '-'){
		$op = substr($_SERVER['argv'][1],1);
		$target = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null;
	}else{
		$target = $_SERVER['argv'][1];
	}
	if(!is_file($target)){
		print($target." not found".PHP_EOL);
		exit;
	}
	$src = file_get_contents($target);
	$src = trim(preg_replace("/\/\*\*\*.*?\*\//s","",$src));
	if(strpos($op,'d') !== false) $src = trim(preg_replace("/\/\*\*.*?\*\//s","",$src));	
	if(strpos($op,'t') !== false) $src = preg_replace("/\t+/"," ",$src);
	
	$src = preg_replace("/\n[\t\s]*\n/","\n",$src);
	$src = preg_replace("/[\n]+/","\n",$src);
	print($src);
}

