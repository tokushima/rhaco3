<?php
namespace test;

class SampleExceptionFlow{
	public function throw_method(){
		\org\rhaco\Log::disable_display();
		throw new \LogicException('error');
	}
	public function throw_method_package(){
		throw new \test\exception\SampleException('sample error');
	}
}