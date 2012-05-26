<?php
include('rhaco3.php');

class Sample{
	private $parent_id;
	private $id;
	
	public function __construct($parent_id,$id){
		$this->parent_id = $parent_id;
		$this->id = $id;
		\org\rhaco\Log::info('NEW: '.$this->id.'/'.$this->parent_id.' '.date('H:i:s'));
	}
	public function __destruct(){
		\org\rhaco\Log::info('DEL: '.$this->id.'/'.$this->parent_id.' '.date('H:i:s'));
	}
	public function run(){
		\org\rhaco\Log::info('RUN: '.$this->id.'/'.$this->parent_id.' '.date('H:i:s'));
		sleep(1);
	}
}
function start(){
	$obj = new Sample(
				isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : -1,
				isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : -2
			);
	$obj->run();
}
start();


