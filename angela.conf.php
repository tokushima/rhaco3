<?php
$dir = getcwd();
$urls = array();
foreach(new \RecursiveDirectoryIterator(
		$dir,
		\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
) as $e){
	if(substr($e->getFilename(),-4) == '.php' &&
			strpos($e->getPathname(),'/.') === false &&
			strpos($e->getPathname(),'/_') === false
	){
		$entry_name = substr($e->getFilename(),0,-4);
		$src = file_get_contents($e->getFilename());

		if(strpos($src,'Flow') !== false){
			$entry_name = substr($e->getFilename(),0,-4);
			foreach(\org\rhaco\Flow::get_maps($e->getPathname()) as $p => $m){
				$urls[$entry_name.'::'.$m['name']] = $m['pattern'];
			}
		}
	}
}

return array(
	'urls'=>$urls
);

