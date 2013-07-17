<?php
namespace local;

class Pages{
	use \sandra\FlowPlugin;
	
	public function get_flow_plugins(){
		return [
			'sandra.flow.plugin.TwitterBootstrapHelper'
			,'sandra.Exceptions'
			,new Pages\Filter()
		];
	}
	
	/**
	 * @automap
	 */
	public function index(){
		$req = new \sandra\Request();
		$path = \sandra\Conf::get('template_path',getcwd().'/resources/templates');		
		$view = \sandra\Util::path_absolute($path,$req->in_vars('contents','index.html'));
		if(!is_file($view)) $view = \sandra\Util::path_slash($view,null,true).'index.html';
		
		$this->set_template($view);
		return ['f'=>new \local\Pages\Filter()];
	}
	
	static public function publish($output_path){
		$template_path = \sandra\Util::path_slash(\sandra\Conf::get('template_path',getcwd().'/resources/templates'),null,true);
		
		foreach(\sandra\Util::ls($template_path,true) as $f){
			$path = str_replace($template_path,'',$f->getPathname());
			$filename = $f->getPathname();
			$replace = '../';
			$url = 'http://localhost/'.
					str_repeat('REPLACE/',substr_count(str_replace(getcwd().'/','',$path),'/')).
					'resources/media';
	
			if($path == 'index.html'){
				$filepath = getcwd().'/index.html';
				$replace = './';
			}else{
				$filepath = \sandra\Util::path_absolute($output_path,$path);
			}
			$dir = dirname($filepath);
			\sandra\Util::mkdir($dir);
			
			$template = new \sandra\Template($url);
			$template->set_object_plugin(new \sandra\flow\plugin\TwitterBootstrapHelper());
			$template->vars('f',new Pages\Filter($path));
			file_put_contents($filepath,str_replace(['http://localhost/','REPLACE/'],[$replace,'../'],$template->read($filename)));
		}
	}
}