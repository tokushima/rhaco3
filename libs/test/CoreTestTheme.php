<?php
namespace test;

class CoreTestTheme extends \org\rhaco\flow\parts\RequestFlow{
	public function index(){
		if($this->is_vars("hoge")){
			$this->theme($this->in_vars("hoge"));
		}
	}
}
