<?php
include('/Users/tokushima/Documents/workspace/rhaco3/rhaco3.php');


class DaemonChild{
	private $abc;
	
	public function __construct($abc){
		$this->abc = $abc;
		\org\rhaco\Log::error('NEW: '.$this->abc.' '.date('H:i:s'));
	}
	public function __destruct(){
		\org\rhaco\Log::error('DEL: '.$this->abc.' '.date('H:i:s'));
	}
	public function run(){
		\org\rhaco\Log::error('RUN: '.$this->abc.' '.date('H:i:s'));
		sleep(1);
	}
}
function start(){
	$obj = new DaemonChild(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : -1);
	$obj->run();
}
start();


