<?php
namespace test\flow\module;

class CoreTestModule extends \org\rhaco\flow\parts\RequestFlow{
	public function before_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars('before_flow_handle','BEFORE_FLOW_HANDLE');
	}
	public function after_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars('after_flow_handle','AFTER_FLOW_HANDLE');	
	}	
	public function init_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'INIT_TEMPLATE'.PHP_EOL);
	}
	public function before_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'BEFORE_TEMPLATE'.PHP_EOL);
	}
	public function after_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'AFTER_TEMPLATE'.PHP_EOL);
	}
	public function before_flow_print_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'BEFORE_FLOW_PRINT_TEMPLATE'.PHP_EOL);
	}
	public function before_exec_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'BEFORE_EXEC_TEMPLATE'.PHP_EOL);
	}
	public function after_exec_template(\org\rhaco\lang\String $obj){
		$obj->set($obj->get().'AFTER_EXEC_TEMPLATE'.PHP_EOL);
	}
}