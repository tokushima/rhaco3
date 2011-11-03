<?php
namespace test;

class CoreTestRedirectMapA extends \org\rhaco\flow\parts\RequestFlow{
	public function redirect_by_map_method_a(){
		$this->redirect_by_map("redirect_by_map_method_call_a");
	}
	public function redirect_by_map_method_b(){
		$this->redirect_by_map("redirect_by_map_method_call_b");
	}
	public function redirect_by_map_method_c(){
		$this->redirect_by_map("redirect_by_map_method_call_c");
	}
}
