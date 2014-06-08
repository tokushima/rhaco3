<?php
/**
 * Application setup
 * @param boolean $create
 */
if($create){
	$path = getcwd();
	
	if(!is_dir($f=$path.'/lib')){
		mkdir($f,0777,true);
		\cmdman\Std::println_success('Written dir '.$f);
	}
	if(!is_dir($f=$path.'/resources/media')){
		mkdir($f,0777,true);
		\cmdman\Std::println_success('Written dir '.$f);
	}
	if(!is_dir($f=$path.'/resources/templates')){
		mkdir($f,0777,true);
		\cmdman\Std::println_success('Written dir '.$f);
	}
	if(!is_file($f=$path.'/resources/templates/index.html')){
		copy(__DIR__.'/create/index.html',$f);
		\cmdman\Std::println_success('Written file '.$f);
	}
	if(!is_file($f=$path.'/index.php')){
		copy(__DIR__.'/create/index.php',$f);
		\cmdman\Std::println_success('Written file '.$f);
	}
	if(!is_file($f=$path.'/bootstrap.php')){
		$autoload_file = 'vendor/autoload.php';
		if(class_exists('Composer\Autoload\ClassLoader')){
			$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
			$composer_dir = dirname($r->getFileName());
		
			if(is_file($bf=realpath(dirname($composer_dir).'/autoload.php'))){
				$autoload_file = str_replace(str_replace("\\",'/',getcwd()).'/','',str_replace("\\",'/',$bf));
			}
		}
		file_put_contents($f,'<?php'.PHP_EOL.'include_once(\''.$autoload_file.'\');');
		\cmdman\Std::println_success('Written file '.$f.PHP_EOL);
	}
}
$appmode = defined('APPMODE') ? constant('APPMODE') : null;
$cmddir = defined('COMMONDIR') ? constant('COMMONDIR') : (getcwd().'/commons');

$mode_list = array();
if(is_dir($cmddir)){
	foreach(new \RecursiveDirectoryIterator($cmddir,\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $f){
		if(substr($f->getFilename(),-4) == '.php'){
			$mode_list[] = substr($f->getFilename(),0,-4);
		}
	}
}
$default = (empty($appmode) || array_search($appmode,$mode_list) !== false) ? $appmode : 'local';

$mode = \cmdman\Std::read('Application mode',$default,$mode_list);

$settings_file = getcwd().'/__settings__.php';
file_put_contents($settings_file,
	'<?php'
	.PHP_EOL.'define(\'APPMODE\',\''.$mode.'\');'
	.PHP_EOL.'define(\'COMMONDIR\',\''.$cmddir.'\');'
	.PHP_EOL
);
\cmdman\Std::println_success('Written: '.realpath($settings_file));

if($mode != $appmode){
	\cmdman\Std::println_info('Application mode changed.');
	exit;
}
if(\cmdman\Std::read('create .htaccess?','n',array('y','n')) == 'y'){
	$base = \cmdman\Std::read('base path?','/'.basename(getcwd()));
	
	list($path,$rules) = \org\rhaco\Dt::htaccess($base);
	\cmdman\Std::println_success('Written '.realpath($path));
}
$setup_cmd = substr(\org\rhaco\Dt::setup_file(),0,-4).'.cmd.php';
if(is_file($setup_cmd)){
	include($setup_cmd);
}
if(is_file($f=\org\rhaco\Dt::setup_file())){
	\cmdman\Std::println_success('Loading '.$f);
	\org\rhaco\Dt::setup();
}

