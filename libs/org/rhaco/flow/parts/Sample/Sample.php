<?php
namespace org\rhaco\flow\parts;
/**
 * Sample
 * @author tokushima
 */
class Sample extends \org\rhaco\flow\parts\RequestFlow{
	/**
	 * @automap
	 */
	public function index(){
		
	}
	/**
	 * @automap
	 */
	public function user_info(){
		$this->vars('user',$this->user());
	}
	public function post(){
		if($this->is_post()){
			$this->vars('abc',123);
		}
	}
	public function get(){
		if(!$this->is_post()){
			$this->vars('abc',123);
		}
	}
}