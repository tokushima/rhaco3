<?php
namespace org\rhaco\Xml;

class XmlIterator implements \Iterator{
	private $name = null;
	private $plain = null;
	private $tag = null;
	private $offset = 0;
	private $length = 0;
	private $count = 0;

	public function __construct($tag_name,$value,$offset,$length){
		$this->name = $tag_name;
		$this->plain = $value;
		$this->offset = $offset;
		$this->length = $length;
		$this->count = 0;
	}
	public function key(){
		$this->tag->name();
	}
	public function current(){
		$this->plain = substr($this->plain,0,$this->tag->cur()).substr($this->plain,$this->tag->cur() + strlen($this->tag->plain()));
		$this->count++;
		return $this->tag;
	}
	public function valid(){
		if($this->length > 0 && ($this->offset + $this->length) <= $this->count) return false;
		if(is_array($this->name)){
			$tags = array();
			foreach($this->name as $name){
				if(\org\rhaco\Xml::set($get_tag,$this->plain,$name)) $tags[$get_tag->cur()] = $get_tag;
			}
			if(empty($tags)) return false;
			ksort($tags,SORT_NUMERIC);
			foreach($tags as $this->tag) return true;
		}
		return \org\rhaco\Xml::set($this->tag,$this->plain,$this->name);
	}
	public function next(){}
	public function rewind(){
		for($i=0;$i<$this->offset;$i++){
			$this->valid();
			$this->current();
		}
	}
}