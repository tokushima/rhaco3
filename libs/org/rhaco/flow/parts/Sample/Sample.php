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
	public function auth(){
		$this->vars('user',var_dump($this->user(),true));
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