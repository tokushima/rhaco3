<?php
/**
 * .htaccessを書き出す
 * @param string $base 
 */
$base = $has('base') ? $in_value('base') : '/'.basename(getcwd());
if(substr($base,0,1) !== '/') $base = '/'.$base;
$rules = "RewriteEngine On\nRewriteBase ".$base."\n\n";
foreach(new DirectoryIterator(getcwd()) as $f){
	if($f->isFile() && substr($f->getPathname(),-4) == '.php' && substr($f->getFilename(),0,1) != '_' && $f->getFilename() != 'index.php'){
		$src = file_get_contents($f->getPathname());
		if(strpos($src,'Flo'.'w') !== false && (strpos($src,'->outpu'.'t(') !== false || strpos($src,'Flo'.'w::out(') !== false)){
			$app = substr($f->getFilename(),0,-4);
			$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^".$app."[/]{0,1}(.*)\$ ".$app.".php/\$1?%{QUERY_STRING} [L]\n\n";
		}
	}
}
if(is_file(getcwd().'/index.php')) $rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^(.*)\$ index.php/\$1?%{QUERY_STRING} [L]\n\n";
file_put_contents('.htaccess',$rules);
print('Written: '.realpath('.htaccess').PHP_EOL.str_repeat('-',60).PHP_EOL.trim($rules).PHP_EOL.str_repeat('-',60).PHP_EOL);
