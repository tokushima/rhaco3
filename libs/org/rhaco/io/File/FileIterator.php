<?php
namespace org\rhaco\io\File;
/**
 * Fileイテレータ
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FileIterator implements \Iterator{
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
		return ($this->type === 0) ? $this->pointer : new \org\rhaco\io\File($this->pointer);
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