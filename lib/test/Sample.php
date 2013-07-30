<?php
namespace test;

class Sample extends \org\rhaco\flow\parts\RequestFlow{
	public function after_redirect(){
		$this->vars('next_var_A','ABC');
		$this->vars('next_var_B','DEF');
	}
	public function after_to($a=null,$b=null){
		$this->vars('after_to_a',$a);
		$this->vars('after_to_b',$b);
	}
}
