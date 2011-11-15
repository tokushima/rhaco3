<?php
namespace org\rhaco\io\log;
/**
 * ファイルにログを出力するLogモジュール
 * @author tokushima
 */
class OneFile{
	private $path;

	public function __construct($path=null){
		$this->path = self::path($path);
	}
	static private function path($path=null){
		if($path === null) $path = \org\rhaco\Conf::get('path',getcwd().'/work/output.log');
		$path = str_replace("\\",'/',$path);
		$dir = dirname($path);
		if(!is_dir($dir)) mkdir($dir,0777,true);
		return $path;
	}
	public function debug(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	public function info(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	public function warn(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
	public function error(\org\rhaco\Log $log,$id){
		file_put_contents($this->path,((string)$log).PHP_EOL,FILE_APPEND);
	}
}
