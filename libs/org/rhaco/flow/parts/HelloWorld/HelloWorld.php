<?php
namespace org\rhaco\flow\parts;
/**
 * Hello world
 * @author tokushima
 *
 */
class HelloWorld extends \org\rhaco\flow\parts\RequestFlow{
	public function sample(){
		$this->vars('message','hello world');
	}	
}