<?php
namespace test\flow\module;

class CoreTestModule extends \org\rhaco\flow\parts\RequestFlow{
	public function before_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars('before_flow_handle','BEFORE_FLOW_HANDLE');
	}
	public function after_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars('after_flow_handle','AFTER_FLOW_HANDLE');	
	}	
	public function exception_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars('exception_flow_handle','EXCEPTION_FLOW_HANDLE');
	}
	
	public function init_template(&$src){
		$src = $src.'INIT_TEMPLATE'.PHP_EOL;
	}
	public function before_template(&$src){
		$src = $src.'BEFORE_TEMPLATE'.PHP_EOL;
	}
	public function after_template(&$src){
		$src = $src.'AFTER_TEMPLATE'.PHP_EOL;
	}
	public function before_flow_print_template(&$src){
		$src = $src.'BEFORE_FLOW_PRINT_TEMPLATE'.PHP_EOL;
	}
	public function before_exec_template(&$src){
		$src = $src.'BEFORE_EXEC_TEMPLATE'.PHP_EOL;
	}
	public function after_exec_template(&$src){
		$src = $src.'AFTER_EXEC_TEMPLATE'.PHP_EOL;
	}
}