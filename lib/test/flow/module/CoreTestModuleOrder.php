<?php
namespace test\flow\module;

class CoreTestModuleOrder extends \org\rhaco\flow\parts\RequestFlow{
	public function before_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars("order",$flow->in_vars("order")."3");		
	}
	public function after_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars("order",$flow->in_vars("order")."4");		
	}
	public function init_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."5");
	}
	public function before_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."6");
	}
	public function after_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."7");
	}
	public function before_exec_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."8");
	}
	public function after_exec_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."9");
	}
	public function before_flow_print_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get()."10");
	}
}