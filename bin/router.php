<?php
/**
 * router with built-in web server
 */
$logger = function($state,$file,$uri=null){
	$error_log = ini_get('error_log');

	$data = array(
			date('Y-m-d H:i:s'),
			$state,
			(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
			$file,
			(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''),
			(is_file($error_log) ? filesize($error_log) : 0),
			$uri,
	);
	file_put_contents('php://stdout',implode("\t",$data).PHP_EOL);
};
$include_php = function($filename,$subdir){
	if(isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SERVER_PORT'])){
		$_ENV['APP_URL'] = 'http://'.$_SERVER['SERVER_NAME'].(($_SERVER['SERVER_PORT'] != 80) ? (':'.$_SERVER['SERVER_PORT']) : '').$subdir;
	}
	include($filename);
};
	
	
	
if(ini_get('date.timezone') == ''){
	date_default_timezone_set('Asia/Tokyo');
}
$dir = getcwd();
$subdir = '';
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
if(strpos($uri,'?') !== false) list($uri) = explode('?',$uri,2);
if(substr($uri,0,1) == '/'){
	$uri = substr($uri,1);
}
$uri_exp = explode('/',$uri,2);
if(is_dir($uri_exp[0])){
	$subdir = '/'.$uri_exp[0];
	$dir = $dir.$subdir;
		
	if(isset($uri_exp[1])){
		$uri_exp = explode('/',$uri_exp[1],2);
	}
}
chdir($dir);
if(is_file($f=($dir.'/'.implode('/',$uri_exp)))){
	$logger('success',$f,$uri);
	$info = pathinfo($f);
	if(isset($info['extension']) && strtolower($info['extension']) == 'php'){
		$include_php($f,$subdir);
	}else{
		$mime = function($filename){
			$ext = (false !== ($p = strrpos($filename,'.'))) ? strtolower(substr($filename,$p+1)) : null;
			switch($ext){
				case 'jpg':
				case 'jpeg': return 'jpeg';
				case 'png':
				case 'gif':
				case 'bmp':
				case 'tiff': return 'image/'.$ext;
				case 'css': return 'text/css';
				case 'txt': return 'text/plain';
				case 'html': return 'text/html';
				case 'xml': return 'application/xml';
				case 'js': return 'text/javascript';
				case 'flv':
				case 'swf': return 'application/x-shockwave-flash';
				case '3gp': return 'video/3gpp';
				case 'gz':
				case 'tgz':
				case 'tar':
				case 'gz': return 'application/x-compress';
				case 'csv': return 'text/csv';
				case null:
				default:
					return 'application/octet-stream';
			}
		};
		header('Content-Type: '.$mime($f));
		readfile($f);
	}
}else if(is_file($f=($dir.'/'.$uri_exp[0])) || is_file($f=($dir.'/'.$uri_exp[0].'.php'))){
	$_SERVER['PATH_INFO'] = '/'.(isset($uri_exp[1]) ? $uri_exp[1] : '');
	$logger('success',$f,$uri);
		
	$include_php($f,$subdir,$uri);
}else if(is_file($f=($dir.'/index.php'))){
	$_SERVER['PATH_INFO'] = '/'.implode('/',$uri_exp);
		
	$logger('success',$f,$uri);
	$include_php($f,$subdir,$uri);
}else{
	header('HTTP/1.1 404 Not Found');
	$logger('failure','',$uri);
}

