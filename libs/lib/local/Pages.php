<?php
namespace local;

class Pages{
	use \sandra\FlowPlugin;
	
	public function get_flow_plugins(){
		return [
			'sandra.flow.plugin.TwitterBootstrapHelper'
			,'sandra.Exceptions'
			,'local.Pages.Filter'
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
	
	static public function export($output_path){
		$files = self::files();
		foreach($files as $path => $filename){
			if($path == 'index.html'){
				$filepath = getcwd().'/index.html';
			}else{
				$filepath = \sandra\Util::path_absolute($output_path,$path);
			}
			$dir = dirname($filepath);
			\sandra\Util::mkdir($dir);
			
			$template = new \sandra\Template();
			$template->set_object_plugin(new \sandra\flow\plugin\TwitterBootstrapHelper());
			$template->set_object_plugin(new \local\Pages\Filter($dir,$files));
			$template->vars('f',new \local\Pages\Filter(true,$path));
			
			file_put_contents($filepath,$template->read($filename));
		}
	}
	static private function files(){
		$list = [];
		$template_path = getcwd().'/template/';
		
		foreach(\sandra\Util::ls($template_path,true) as $f){
			$path = str_replace($template_path,'',$f->getPathname());
			$list[$path] = $f->getPathname();
		}
		return $list;
	}
}