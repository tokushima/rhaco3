<?php
/**
 * 
 * @author tokushima
 *
 */
class Pagen{
	use \ebi\FlowPlugin;
	
	public function get_flow_plugins(){
		return [
			'ebi.flow.plugin.TwitterBootstrap3Helper'
			,'ebi.FlowInvalid'
			,new Pagen\Filter()
		];
	}
	
	/**
	 * @automap
	 */
	public function index(){
		$req = new \ebi\Request();
		$path = \ebi\Conf::get('template_path',\ebi\Conf::resource_path('templates'));		
		$view = \ebi\Util::path_absolute($path,$req->in_vars('contents','index.html'));
		if(!is_file($view)) $view = \ebi\Util::path_slash($view,null,true).'index.html';
		
		$this->set_template($view);
		return ['f'=>new Pagen\Filter()];
	}
	/**
	 * 書き出す
	 * @param string $output_path
	 */
	public static function publish($output_path){
		$written = [];
		$template_path = \ebi\Util::path_slash(\ebi\Conf::get('template_path',\ebi\Conf::resource_path('templates')),null,true);
		
		foreach(\ebi\Util::ls($template_path,true) as $f){
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
				$filepath = \ebi\Util::path_absolute($output_path,$path);
			}
			$dir = dirname($filepath);
			\ebi\Util::mkdir($dir);
			
			$template = new \ebi\Template($url);
			$template->set_object_plugin(new \ebi\flow\plugin\TwitterBootstrap3Helper());
			$template->vars('f',new Pagen\Filter($path));
			file_put_contents($filepath,str_replace(['http://localhost/','REPLACE/'],[$replace,'../'],$template->read($filename)));
			$written[] = $filepath;
		}
		return $written;
	}
}