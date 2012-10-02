<?php
class Dummy implements \IteratorAggregate
{
	private $vars = array();
	
	public function vars($k,$v){
		$this->vars[$k] = $v;
	}
	public function getIterator(){
		return new \ArrayIterator($this->vars);
	}	
}