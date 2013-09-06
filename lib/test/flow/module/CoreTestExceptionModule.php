<?php
namespace test\flow\module;

class CoreTestExceptionModule{
	public function before_flow_action($flow){
		throw new \LogicException('flow handle begin exception');
	}
}