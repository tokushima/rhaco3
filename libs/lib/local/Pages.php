<?php
namespace local;

class Pages{
	static public function export($template_path,$output_path){
		$template = new \sandra\Template();
		$template_path = \sandra\Util::path_slash($template_path,null,true);
		$output_path = \sandra\Util::path_slash($output_path,null,true);
		
		\sandra\Util::mkdir($output_path);
		foreach(\sandra\Util::ls($template_path) as $f){
			$path = str_replace($template_path,'',$f->getPathname());
			
			if($path == 'index.html'){
				$path = getcwd().'/index.html';
			}else{
				$path = \sandra\Util::path_absolute($output_path,$path);
			}
			file_put_contents($path,$template->read($f->getPathname()));
		}
	}
}