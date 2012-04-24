<?php
namespace org\rhaco\service;
/**
 * Amazonライブラリのラッパ
 * @author tokushima
 * @incomplete
 * @conf string $key AWS_KEY
 * @conf string $secret AWS_SECRET_KEY
 * @conf string $bucket 接続するバケット(S3のフォルダ)
 * @pear $AmazonS3 @['path'=>'pear.amazonwebservices.com/sdk']
 */
class Amazon{
	private $s3;
	private $bucket;

	static public function __import__(){
		\org\rhaco\Pear::load(
			'AWSSDKforPHP/sdk.class.php'
		);
	}
	public function __construct(){
		if(!defined('AWS_KEY')) define('AWS_KEY',\org\rhaco\Conf::get('key'));
		if(!defined('AWS_SECRET_KEY')) define('AWS_SECRET_KEY',\org\rhaco\Conf::get('secret'));
		$this->s3 = new \AmazonS3();
		$this->bucket = \org\rhaco\Conf::get('bucket');
	}
	/**
	 * 一時的な公開URLを発行する
	 * @see http://choilog.com/katty0324/blog/19
	 * @param string $remote_path S3のバケット内のパス
	 * @param string $expire_sec 有効期間（秒）
	 * @return string URL
	 */
	public function temp_open_url($remote_path,$expire_sec=30){
		return $this->s3->get_object_url($this->bucket,$remote_path,(int)$expire_sec+time());
	}
	/**
	 * ファイルが存在するか
	 * @param string $remote_path S3のバケット内のパス
	 * @return boolean
	 */
	public function exists($remote_path){
		return $this->s3->if_object_exists($this->bucket,$remote_path);
	}
	/**
	 * S3からファイルをダウンロードする
	 * @param string $remote_path S3のバケット内のパス
	 * @param string $download_path 保存するファイルパス
	 */
	public function download($remote_path,$download_path){
		$this->s3->get_object($this->bucket,$remote_path,array('fileDownload'=>$download_path));
	}
	/**
	 * ファイルをアップロードする
	 * @param string $remote_path S3のバケット内のパス
	 * @param string $src_path アップロードするファイルのフルパス
	 */
	public function upload($remote_path,$src_path){
		$this->s3->create_object($this->bucket,$remote_path,array('fileUpload'=>$src_path,'acl'=>\AmazonS3::ACL_PRIVATE));
	}
	/**
	 * S3のファイルを削除する
	 * @param string $remote_path
	 */
	public function rm($remote_path){
		$this->s3->delete_object($this->bucket,$remote_path);
	}
	/**
	 * ファイルの一覧を取得する
	 * @param string $path 取得するパス
	 */
	public function ls($path=null){
		if(substr($path,-1) != '/') $path = $path.'/';
		return $this->s3->get_object_list($this->bucket,array('pcre'=>'/^'.preg_quote($path,'/').'/'));
	}
	/**
	 * ディレクトリを削除する
	 * @param string $path 削除するディレクトリ
	 */
	public function rmdir($path){
		if(substr($path,-1) != '/') $path = $path.'/';
		$base = dirname($path);
		$dirs = array();
		foreach($this->ls($path) as $p){
			$this->rm($p);
			while(strpos($p,'/') !== false){
				$p = dirname($p);
				if(isset($dirs[$p]) || $base == $p) break;
				$dirs[$p] = true;
			}
		}
		$dirs = array_keys($dirs);
		rsort($dirs,SORT_STRING);
		foreach($dirs as $d){
			$this->rm($d);
		}
	}
}