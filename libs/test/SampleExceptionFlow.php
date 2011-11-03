<?php
namespace test;

class SampleExceptionFlow{
	public function throw_method(){
		throw new \LogicException('error');
	}
}