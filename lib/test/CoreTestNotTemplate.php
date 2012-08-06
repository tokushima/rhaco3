<?php
namespace test;

class CoreTestNotTemplate extends \org\rhaco\flow\parts\RequestFlow{
	public function aaa(){
		$this->vars("abc","ABC");
		$this->vars("newtag",new \org\rhaco\Xml("hoge","HOGE"));
	}
}