<?php
/**
 * エントリファイルを書き出す
 * @param string $name エントリ名
 */
$name = isset($params['name']) ? $params['name'] : 'index';
$htaccess = isset($params['htaccess']) ? $params['htaccess'] : null;
$resources = getcwd().'/resources/';

$write_file = function($f,$value){
	if(!is_file($f)){
		\org\rhaco\io\File::write($f,$value);
		\org\rhaco\lang\AnsiEsc::println('Output file: '.$f,true);
	}
};
$write_file(getcwd().'/'.$name.'.php',file_get_contents(dirname(__DIR__).'/resources/entry/template_php'));
$write_file(getcwd().'/resources/templates/index.html',file_get_contents(dirname(__DIR__).'/resources/entry/index_html'));
$write_file(getcwd().'/resources/media/style.css',file_get_contents(dirname(__DIR__).'/resources/entry/style_css'));
$write_file(getcwd().'/__settings__.php',file_get_contents(dirname(__DIR__).'/resources/entry/__settings___php'));

if(isset($htaccess)){
	$htaccess = empty($htaccess) ? basename(getcwd()) : $htaccess;
	if(substr($htaccess,0,1) != '/') $htaccess = '/'.$htaccess;
	$str = 'RewriteEngine On'.PHP_EOL
			.'RewriteBase '.$htaccess.PHP_EOL
			.PHP_EOL
			.'RewriteCond %{REQUEST_FILENAME} !-f'.PHP_EOL
			.'RewriteRule ^(.*)$ '.$name.'.php/$1?%{QUERY_STRING} [L]'.PHP_EOL
			;
	$write_file(getcwd().'/.htaccess',$str);
}

