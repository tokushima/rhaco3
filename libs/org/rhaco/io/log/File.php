<?php
namespace org\rhaco\io\log;
/**
 * ファイルにログを出力するLogモジュール
 * @author tokushima
 */
class File{
	private $path;

	static public function __import__(){
		ini_set('log_errors','On');
		ini_set('error_log',self::path());
	}
	static private function path($dir=null){
		if($dir===null) $dir = \org\rhaco\Conf::get('path',getcwd().'/work/logs');
		if(substr(str_replace("\\",'/',$dir),-1) == '/') $dir = subustr($dir,0,-1);

		if(!is_dir($dir)) mkdir($dir,0777,true);
		return $dir.'/'.date('Ymd').'.log';
	}
	public function __construct($dir=null){
		$this->path = self::path($dir);
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
