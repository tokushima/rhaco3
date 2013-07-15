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
		$path = \sandra\Conf::get('template_path',getcwd().'/template');		
		$view = \sandra\Util::path_absolute($path,$req->in_vars('contents','index.html'));
		if(!is_file($view)) $view = \sandra\Util::path_slash($view,null,true).'index.html';
		
		$this->set_template($view);
		return ['f'=>new \local\Pages\Filter()];
	}
	
	static public function publish($output_path){
		$template_path = getcwd().'/template/';
		
		foreach(\sandra\Util::ls($template_path,true) as $f){
			$path = str_replace($template_path,'',$f->getPathname());
			$filename = $f->getPathname();
			
			if($path == 'index.html'){
				$filepath = getcwd().'/index.html';
			}else{
				$filepath = \sandra\Util::path_absolute($output_path,$path);
			}
			$dir = dirname($filepath);
			\sandra\Util::mkdir($dir);
			
			$template = new \sandra\Template();
			$template->set_object_plugin(new \sandra\flow\plugin\TwitterBootstrapHelper());
			$template->vars('f',new Pages\Filter($path));
			
			file_put_contents($filepath,$template->read($filename));
		}
	}
}