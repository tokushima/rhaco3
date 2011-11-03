<?php
/**
 * ファイル操作
 * @author tokushima
 * @var string $directory フォルダパス
 * @var string $fullname ファイルパス
 * @var string $name ファイル名
 * @var string $oname 拡張子がつかないファイル名
 * @var string $ext 拡張子
 * @var string $mime ファイルのコンテントタイプ
 * @var text $value 内容
 */
class File{
	protected $fullname;
	protected $value;
	protected $mime;
	protected $directory;
	protected $name;
	protected $oname;
	protected $ext;

	public function __construct($fullname=null,$value=null){
		$this->fullname	= str_replace("\\",'/',$fullname);
		$this->value = $value;
		$this->parse_fullname();
	}
	public function __toString(){
		return $this->fullname;
	}
	public function ext(){
		if(func_num_args() > 0){
			$this->ext = func_get_arg(0);
			return $this;
		}else{
			return $this->ext;
		}
	}
	public function oname(){
		if(func_num_args() > 0){
			$this->oname = func_get_arg(0);
			return $this;
		}else{
			return $this->oname;
		}
	}
	public function name(){
		if(func_num_args() > 0){
			$this->name = func_get_arg(0);
			return $this;
		}else{
			return $this->name;
		}
	}
	public function directory(){
		if(func_num_args() > 0){
			$this->directory = func_get_arg(0);
			return $this;
		}else{
			return $this->directory;
		}
	}
	public function mime(){
		if(func_num_args() > 0){
			$this->mime = func_get_arg(0);
			return $this;
		}else{
			return $this->mime;
		}
	}
	public function fullname(){
		if(func_num_args() > 0){
			$this->fullname = func_get_arg(0);
			return $this;
		}else{
			return $this->fullname;
		}
	}
	public function value(){
		if(func_num_args() > 0){
			$this->value = func_get_arg(0);
			$this->size = sizeof($this->value);
			return $this;
		}else{
			return $this->value;
		}
	}
	public function is_ext($ext){
		return ('.'.strtolower($ext) === strtolower($this->ext()));
	}
	public function is_fullname(){
		return is_file($this->fullname);
	}
	public function is_tmp(){
		return is_file($this->tmp);
	}
	public function is_error(){
		return (intval($this->error) > 0);
	}
	/**
	 * 標準出力に出力する
	 */
	public function output(){
		if(empty($this->value) && @is_file($this->fullname)){
			readfile($this->fullname);
		}else{
			print($this->value);
		}
		exit;
	}
	/**
	 * 内容を取得する
	 * @return string
	 */
	public function get(){
		if($this->value !== null) return $this->value;
		if(is_file($this->fullname)) return file_get_contents($this->fullname);
		if(is_file($this->tmp)) return file_get_contents($this->tmp);
		throw new InvalidArgumentException(sprintf('permission denied `%s`',$this->fullname));
	}
	public function update(){
		return (@is_file($this->fullname)) ? @filemtime($this->fullname) : time();
	}
	public function size(){
		return (@is_file($this->fullname)) ? @filesize($this->fullname) : strlen($this->value);
	}
	private function parse_fullname(){
		$fullname = str_replace("\\",'/',$this->fullname);
		if(preg_match("/^(.+[\/]){0,1}([^\/]+)$/",$fullname,$match)){
			$this->directory = empty($match[1]) ? "./" : $match[1];
			$this->name = $match[2];
		}
		if(false !== ($p = strrpos($this->name,'.'))){
			$this->ext = '.'.substr($this->name,$p+1);
			$filename = substr($this->name,0,$p);
		}
		$this->oname = @basename($this->name,$this->ext);

		if(empty($this->mime)){
			$ext = strtolower(substr($this->ext,1));
			switch($ext){
				case 'jpg':
				case 'jpeg': $ext = 'jpeg';
				case 'png':
				case 'gif':
				case 'bmp':
				case 'tiff': $this->mime = 'image/'.$ext; break;
				case 'css': $this->mime = 'text/css'; break;
				case 'txt': $this->mime = 'text/plain'; break;
				case 'html': $this->mime = 'text/html'; break;
				case 'xml': $this->mime = 'application/xml'; break;
				case 'js': $this->mime = 'text/javascript'; break;
				case 'flv':
				case 'swf': $this->mime = 'application/x-shockwave-flash'; break;
				case '3gp': $this->mime = 'video/3gpp'; break;
				case 'gz':
				case 'tgz':
				case 'tar':
				case 'gz':  $this->mime = 'application/x-compress'; break;
				default:
					if(empty($this->mime)) $this->mime = 'application/octet-stream';
			}
		}
	}
	/**
	 * フォルダを作成する
	 * @param string $source 作成するフォルダパス
	 */
	static public function mkdir($source){
		if(!is_dir($source)){
			try{
				mkdir($source,0777,true);
			}catch(ErrorException $e){
				throw new InvalidArgumentException(sprintf('permission denied `%s`',$source));
			}
		}
	}
	/**
	 * 移動
	 * @param string $source 移動もとのファイルパス
	 * @param string $dest 移動後のファイルパス
	 */
	static public function mv($source,$dest){
		if(is_file($source) || is_dir($source)){
			self::mkdir(dirname($dest));
			return rename($source,$dest);
		}
		throw new InvalidArgumentException(sprintf('permission denied `%s`',$source));
	}
	/**
	 * 最終更新時間を取得
	 * @param string $filename ファイルパス
	 * @param boolean $clearstatcache ファイルのステータスのキャッシュをクリアするか
	 * @return integer
	 */
	static public function last_update($filename,$clearstatcache=false){
		if($clearstatcache) clearstatcache();
		if(is_dir($filename)){
			$last_update = null;
			foreach(self::ls($filename,true) as $file){
				if($last_update < $file->update()) $last_update = $file->update();
			}
			return $last_update;
		}
		return (is_readable($filename) && is_file($filename)) ? filemtime($filename) : null;
	}
	/**
	 * 削除
	 * $sourceがフォルダで$inc_selfがfalseの場合は$sourceフォルダ以下のみ削除
	 * @param string $source 削除するパス
	 * @param boolean $inc_self $sourceも削除するか
	 * @return boolean
	 */
	static public function rm($source,$inc_self=true){
		if(!is_dir($source) && !is_file($source)) return true;
		if(!$inc_self){
			foreach(self::dir($source) as $d) self::rm($d);
			foreach(self::ls($source) as $f) self::rm($f);
			return true;
		}
		if(is_writable($source)){
			if(is_dir($source)){
				if($handle = opendir($source)){
					$list = array();
					while($pointer = readdir($handle)){
						if($pointer != '.' && $pointer != '..') $list[] = sprintf('%s/%s',$source,$pointer);
					}
					closedir($handle);
					foreach($list as $path){
						if(!self::rm($path)) return false;
					}
				}
				if(rmdir($source)){
					clearstatcache();
					return true;
				}
			}else if(is_file($source) && unlink($source)){
				clearstatcache();
				return true;
			}
		}
		throw new InvalidArgumentException(sprintf('permission denied `%s`',$source));
	}
	/**
	 * コピー
	 * $sourceがフォルダの場合はそれ以下もコピーする
	 * @param string $source コピー元のファイルパス
	 * @param string $dest コピー先のファイルパス
	 */
	static public function copy($source,$dest){
		if(!is_dir($source) && !is_file($source)) throw new InvalidArgumentException(sprintf('permission denied `%s`',$source));
		if(is_dir($source)){
			$bool = true;
			if($handle = opendir($source)){
				while($pointer = readdir($handle)){
					if($pointer != '.' && $pointer != '..'){
						$srcname = sprintf('%s/%s',$source,$pointer);
						$destname = sprintf('%s/%s',$dest,$pointer);
						if(false === ($bool = self::copy($srcname,$destname))) break;
					}
				}
				closedir($handle);
			}
			return $bool;
		}else{
			$dest = (is_dir($dest))	? $dest.$filename : $dest;
			if(is_writable(dirname($dest))){
				copy($source,$dest);
			}
			return is_file($dest);
		}
	}
	/**
	 * ファイルから取得する
	 * @param string $filename ファイルパス
	 * @return string
	 */
	static public function read($filename){
		if(!is_readable($filename) || !is_file($filename)) throw new InvalidArgumentException(sprintf('permission denied `%s`',$filename));
		return file_get_contents($filename);
	}
	/**
	 * ファイルに書き出す
	 * @param string $filename ファイルパス
	 * @param string $src 内容
	 */
	static public function write($filename,$src=null,$lock=true){
		if(empty($filename)) throw new InvalidArgumentException(sprintf('permission denied `%s`',$filename));
		self::mkdir(dirname($filename));
		if(false === file_put_contents($filename,(string)$src,($lock ? LOCK_EX : 0))) throw new InvalidArgumentException(sprintf('permission denied `%s`',$filename));
	}
	/**
	 * ファイルに追記する
	 * @param string $filename ファイルパス
	 * @param string $src 追加する内容
	 * @param integer $dir_permission モード　8進数(0644)
	 */
	static public function append($filename,$src=null,$lock=true){
		self::mkdir(dirname($filename));
		if(false === file_put_contents($filename,(string)$src,FILE_APPEND|(($lock) ? LOCK_EX : 0))) throw new InvalidArgumentException(sprintf('permission denied `%s`',$filename));
	}
	static private function parse_filename($filename){
		$filename = preg_replace("/[\/]+/",'/',str_replace("\\",'/',trim($filename)));
		return (substr($filename,-1) == '/') ? substr($filename,0,-1) : $filename;
	}
	/**
	 * フォルダ名の配列を取得
	 * @param string $directory  検索対象のファイルパス
	 * @param boolean $recursive 階層を潜って取得するか
	 * @param boolean $a 隠しファイルも参照するか
	 * @return string[]
	 */
	static public function dir($directory,$recursive=false,$a=false){
		$directory = self::parse_filename($directory);
		if(is_file($directory)) $directory = dirname($directory);
		if(is_readable($directory) && is_dir($directory)) return new FileIterator($directory,0,$recursive,$a);
		throw new InvalidArgumentException(sprintf('permission denied `%s`',$directory));
	}
	/**
	 * 指定された$directory内のファイル情報をFileとして配列で取得
	 * @param string $directory  検索対象のファイルパス 
	 * @param boolean $recursive 階層を潜って取得するか
	 * @param boolean $a 隠しファイルも参照するか
	 * @return File[]
	 */
	static public function ls($directory,$recursive=false,$a=false){
		$directory = self::parse_filename($directory);
		if(is_file($directory)) $directory = dirname($directory);
		if(is_readable($directory) && is_dir($directory)){
			return new FileIterator($directory,1,$recursive,$a);
		}
		throw new InvalidArgumentException(sprintf('permission denied `%s`',$directory));
	}
	/**
	 * ファイルパスからディレクトリ名部分を取得
	 * @param string $path ファイルパス
	 * @return string
	 */
	static public function dirname($path){
		$dir_name = dirname(str_replace("\\",'/',$path));
		$len = strlen($dir_name);
		return ($len === 1 || ($len === 2 && $dir_name[1] === ':')) ? null : $dir_name;
	}
	/**
	 * フルパスからファイル名部分を取得
	 * @param string $path ファイルパス
	 * @return string
	 */
	static public function basename($path){
		$basename = basename($path);
		$len = strlen($basename);
		return ($len === 1 || ($len === 2 && $basename[1] === ':')) ? null : $basename;
	}
	/**
	 * ディレクトリでユニークなファイル名を返す
	 * @param $dir
	 * @param $prefix
	 * @return string
	 */
	static public function temp_path($dir,$prefix=null){
		if(is_dir($dir)){
			if(substr(str_replace("\\",'/',$dir),-1) != '/') $dir .= '/';
			while(is_file($dir.($path = uniqid($prefix,true))));
			return $path;
		}
		return uniqid($prefix,true);
	}
}
/**
 * Fileイテレータ
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FileIterator implements Iterator{
	private $pointer;
	private $hierarchy = 0;
	private $resource = array();
	private $path = array();
	private $next = false;

	private $type;
	private $recursive;
	private $a;

	public function __construct($directory,$type,$recursive,$a){
		$this->resource[0] = opendir($directory);
		$this->path[0] = $directory;
		$this->type = $type;
		$this->recursive = $recursive;
		$this->a = $a;
	}
	/**
	 * @see Iterator
	 */
	public function rewind(){
	}
	/**
	 * @see Iterator
	 */
	public function next(){
	}
	/**
	 * @see Iterator
	 */
	public function key(){
		return $this->path[$this->hierarchy];
	}
	/**
	 * @see Iterator
	 */
	public function current(){
		return ($this->type === 0) ? $this->pointer : new File($this->pointer);
	}
	/**
	 * @see Iterator
	 */
	public function valid(){
		if($this->next !== false){
			$this->hierarchy++;
			$this->resource[$this->hierarchy] = $this->next;
			$this->path[$this->hierarchy] = $this->pointer;
			$this->next = false;
			return $this->valid();
		}
		$pointer = readdir($this->resource[$this->hierarchy]);
		if($pointer === "." || $pointer === ".." || (!$this->a && $pointer[0] === ".")) return $this->valid();

		if($pointer === false){
			closedir($this->resource[$this->hierarchy]);
			if($this->hierarchy === 0) return false;
			$this->hierarchy--;
			return $this->valid();
		}
		$this->pointer = $this->path[$this->hierarchy]."/".$pointer;
		if($this->recursive && is_dir($this->pointer)) $this->next = opendir($this->pointer);
		if(($this->type === 0 && !is_dir($this->pointer)) || ($this->type === 1 && !is_file($this->pointer))) return $this->valid();
		return true;
	}
}