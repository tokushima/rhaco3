<?php
namespace test;

class FlowVar extends \org\rhaco\Object{	
	protected $aaa = 'AAA';
	public function bbb(){
		return 'BBB';
	}
	static public function ccc(){
		return 'CCC';
	}
}