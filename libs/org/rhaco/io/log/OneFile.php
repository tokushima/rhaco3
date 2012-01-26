<?php
namespace org\rhaco\io\log;
/**
 * ファイルにログを出力するLogモジュール
 * @author tokushima
 * @conf string $path ログファイルを保存するファイルパス
 */
class OneFile{
	private $path;

	public function __construct($path=null){
		$this->path = (empty($path)) ? \org\rhaco\Conf::get('path',getcwd().'/work/output.log') : $path;
		$dir = dirname($this->path);
		if(!is_dir($dir)) mkdir($dir,0777,true);
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
