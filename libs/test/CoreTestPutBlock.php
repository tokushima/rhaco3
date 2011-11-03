<?php
namespace test;

class CoreTestPutBlock extends \org\rhaco\flow\parts\RequestFlow{
	public function index(){
		if($this->is_vars("hoge")){
			$this->set_block("put_block_".$this->in_vars("hoge").".html");
		}
	}
}