<?php
namespace org\rhaco\io\log;
/**
 * ファイルにログを日付毎に出力するLogモジュール
 * @author tokushima
 * @conf string $path ログファイルを保存するディレクトリ
 */
class File{
	private $path;

	static private function path($dir=null){
		if($dir===null) $dir = \org\rhaco\Conf::get('path',\org\rhaco\io\File::work_path('logs'));
		if(substr(str_replace("\\",'/',$dir),-1) == '/') $dir = subustr($dir,0,-1);
		\org\rhaco\io\File::mkdir($dir,0777);
		return $dir.'/'.date('Ymd').'.log';
	}
	public function __construct($dir=null){
		$this->path = self::path($dir);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function debug(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function info(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function warn(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function error(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
}
