<?php
namespace org\rhaco\lang;

class String{
	private $value;

	public function __construct($v){
		$this->value = $v;
	}
	public function get(){
		return $this->value;
	}
	public function set($v){
		$this->value = $v;
	}
	public function __toString(){
		return $this->value;
	}
	static public function ref(&$obj,$src){
		$obj = new self($src);
		return $obj;
	}
}