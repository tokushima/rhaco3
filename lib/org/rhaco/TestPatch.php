<?php
namespace org\rhaco;
/**
 * TestRunner用のpatchモジュール
 * @author tokushima
 *
 */
class TestPatch{
	private $flow_output_maps = array();

	public function maps(){
		if(empty($this->flow_output_maps)){
			$entry_path = getcwd();

			if(class_exists('\org\rhaco\Flow')){
				foreach(new \RecursiveDirectoryIterator(
						$entry_path,
						\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
				) as $e){
					if(substr($e->getFilename(),-4) == '.php' &&
							strpos($e->getPathname(),'/.') === false &&
							strpos($e->getPathname(),'/_') === false
					){
						$src = file_get_contents($e->getFilename());

						if(strpos($src,'Flow') !== false){
							$entry_name = substr($e->getFilename(),0,-4);
							foreach(\org\rhaco\Flow::get_maps($e->getPathname()) as $p => $m){
								$this->flow_output_maps[$m['name']] = $m['pattern'];
							}
						}

					}
				}
			}
		}
		return $this->flow_output_maps;
	}
	public function setup(){
		\org\rhaco\Exceptions::clear();
	}
	public function teardown(){
		\org\rhaco\Exceptions::clear();
	}
}