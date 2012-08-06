<?php
/**
 * application information
 */
$flow = new \org\rhaco\Flow();

$info = array(
'mode'=>(defined('APP_MODE') ? constant('APP_MODE') : null),
'lib'=>(defined('LIB_DIR') ? constant('LIB_DIR') : null),
'CWD'=>getcwd(),
'url'=>\org\rhaco\Conf::get('org.rhaco.Flow@app_url'),
'url secure'=>(\org\rhaco\Conf::get('org.rhaco.Flow@secure') ? 'true' : 'false'),
'media'=>$flow->media_url(),
'template'=>$flow->template_path(),
'umask'=>sprintf('%04o',umask()),
'log level'=>\org\rhaco\Conf::get('org.rhaco.Log@level'),
);

$len = \org\rhaco\lang\Text::length(array_keys($info));
foreach($info as $label => $value){
	\org\rhaco\lang\AnsiEsc::println(' '.str_pad($label,$len).' : '.$value);
}
print(PHP_EOL);

