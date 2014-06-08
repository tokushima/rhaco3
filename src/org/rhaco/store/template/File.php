<?php
namespace org\rhaco\store\template;
/**
 * TemplateのFileキャッシュ
 * @author tokushima
 * @conf string $path キャッシュファイルを保存するディレクトリ
 */
class File{
	private $path;

	static private function path($dir=null){
		if($dir===null) $dir = \org\rhaco\Conf::get('path',\org\rhaco\io\File::work_path('templates'));
		if(substr(str_replace("\\",'/',$dir),-1) == '/') $dir = subustr($dir,0,-1);
		\org\rhaco\io\File::mkdir($dir,0777);
		return $dir;
	}
	public function __construct($dir=null){
		$this->path = self::path($dir);
	}
	/**
	 * @module org.rhaco.Template
	 * @param string $cname
	 * @return boolean
	 */
	public function has_template_cache($cname){
		return is_file($this->path.'/'.$cname);
	}
	/**
	 * @module org.rhaco.Template
	 * @param string $cname
	 * @param string $src
	 */
	public function set_template_cache($cname,$src){
		\org\rhaco\io\File::write($this->path.'/'.$cname,$src);
	}
	/**
	 * @module org.rhaco.Template
	 * @param string $cname
	 * @return boolean
	 */
	public function get_template_cache($cname){
		return \org\rhaco\io\File::read($this->path.'/'.$cname);
	}
}
