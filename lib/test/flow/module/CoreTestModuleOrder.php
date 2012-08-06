<?php
namespace test\flow\module;

class CoreTestModuleOrder extends \org\rhaco\flow\parts\RequestFlow{
	public function before_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars("order",$flow->in_vars("order")."3");		
	}
	public function after_flow_handle(\org\rhaco\flow\FlowInterface $flow){
		$flow->vars("order",$flow->in_vars("order")."4");		
	}
	public function init_template(&$src){
		$src = $src."5";
	}
	public function before_template(&$src){
		$src = $src."6";
	}
	public function after_template(&$src){
		$src = $src."7";
	}
	public function before_exec_template(&$src){
		$src = $src."8";
	}
	public function after_exec_template(&$src){
		$src = $src."9";
	}
	public function before_flow_print_template(&$src){
		$src = $src."10";
	}
}