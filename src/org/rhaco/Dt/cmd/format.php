<?php
/**
 * PHPファイルの改行コードをCRに統一する
 */
$work = getcwd();
$count = 0;
foreach(new \RecursiveIteratorIterator(
	new \RecursiveDirectoryIterator(
			$work,
			\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
	),\RecursiveIteratorIterator::SELF_FIRST
) as $f){
	if($f->isFile() && substr($f->getFilename(),-4) == '.php'){
		$src = file_get_contents($f->getPathname());
		$nsrc = str_replace(array("\r\n","\r","\n"),"\n",$src);
		$nsrc = preg_replace('/^(.+)[\040\t]+$/','\\1',$nsrc);
		if($src != $nsrc){
			file_put_contents($f->getPathname(),$nsrc);
			print(' '.$f->getPathname().PHP_EOL);
			$count++;
		}
	}
}
print('trimming: '.$count.PHP_EOL);
