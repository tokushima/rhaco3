<?php
/**
 * Preparing an application
 */
$cmddir = defined('COMMONDIR') ? constant('COMMONDIR') : (getcwd().'/commons');

$default = null;
$mode_list = array();
if(is_dir($cmddir)){
	foreach(new \RecursiveDirectoryIterator($cmddir,\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $f){
		if(substr($f->getFilename(),-4) == '.php'){
			$mode_list[] = substr($f->getFilename(),0,-4);
		}
	}
}
if(empty($mode_list)){
	$mode_list[] = 'local';
}
if(array_search('local',$mode_list) !== false){
	$default = 'local';
}

$mode = \brev\Std::read('app mode?',$default,$mode_list);
file_put_contents(getcwd().'/__settings__.php',
	'<?php'
	.PHP_EOL.'define(\'APPMODE\',\''.$mode.'\');'
	.PHP_EOL.'define(\'COMMONDIR\',\''.$cmddir.'\');'
	.PHP_EOL
);
\brev\Std::println_success('Written: '.realpath(getcwd().'/__settings__.php'));

if(\brev\Std::read('create .htaccess?','n',['y','n']) == 'y'){
	$base = \brev\Std::read('base path?','/'.basename(getcwd()));
	
	list($path,$rules) = \org\rhaco\Dt::htaccess($base);
	\brev\Std::println_success('Written: '.realpath($path));	
}
