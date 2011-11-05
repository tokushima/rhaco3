<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\net\Path;
/**
 * ファイルの一覧をセットする
 * @author tokushima
 * @conf string $document_root 一覧するディレクトリのベースパス
 */
class File extends RequestFlow{
	/**
	 * 指定ディレクトリ以下すべてのファイルの一覧
	 */
	public function tree($path=null){
		$files = array();
		$pattern = $this->map_arg('pattern');
		if($pattern !== null) $path = vsprintf($pattern,func_get_args());
		$path = Path::slash(Path::absolute($this->base(),Path::slash($path,false,true)),null,true);

		if(is_dir($path)){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $e){
				$p = str_replace($path,'',str_replace('\\','/',$e->getPathname()));
				if($p[0] != '.') $files[] = $p;
			}
		}
		sort($files);
		$this->vars('files',$files);
	}
	/**
	 * リクエストされたファイルを添付する
	 */
	public function attach($path=null){
		$pattern = $this->map_arg('pattern');
		if($pattern !== null) $path = vsprintf($pattern,func_get_args());
		if($this->has_object_module('before_attach')) $this->object_module('before_attach',$path);
		\org\rhaco\net\http\File::attach(Path::absolute($this->base(),$path));
	}
	private function base(){
		$base = Path::slash(\org\rhaco\Conf::get('document_root'),null,true);
		return $base.Path::slash($this->map_arg('path'),null,true);
	}
}

