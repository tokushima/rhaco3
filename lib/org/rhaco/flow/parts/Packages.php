<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\net\Path;
use org\rhaco\flow\parts\Packages\Model;
/**
 * ライブラリパッケージを一覧する
 * @author tokushima
 * @conf string $document_root packages.csvがあるディレクトリ
 */
class Packages extends RequestFlow{
	/**
	 * ライブラリパッケージを一覧する
	 * @param string $path
	 */
	public function find($path=null){
		$object_list = array();
		$pattern = $this->map_arg('pattern');
		if($pattern !== null) $path = vsprintf($pattern,func_get_args());
		$path = Path::slash(Path::absolute($this->base(),Path::slash($path,false,true)),null,true).'packages.csv';
		$query = $this->in_vars('query');

		$paginator = new \org\rhaco\Paginator(30,$this->in_vars('page',1));
		if(is_file($path)){
			foreach(file($path) as $line){
				$line = trim($line);
				if($line != ''){
					if(empty($query) || stripos($line,$query) !== false){
						list($package,$summary) = explode(',',$line,2);
						$model = new Model();
						$model->package($package);
						$model->summary($summary);
						$paginator->add($model);
					}
				}
			}
		}
		$this->vars('object_list',$paginator->contents());
		$this->vars('paginator',$paginator->vars(array('query'=>$query)));
	}
	private function base(){
		$base = Path::slash(\org\rhaco\Conf::get('document_root'),null,true);
		return $base.Path::slash($this->map_arg('path'),null,true);
	}
}