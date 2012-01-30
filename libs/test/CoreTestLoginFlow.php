<?php
namespace test;
/**
 * 
 * @login ログイン @['require'=>true,'type'=>'org.rhaco.Object']
 */
class CoreTestLoginFlow extends \org\rhaco\flow\parts\RequestFlow{
	public function aaa(){
		$this->vars('user',$this->user());
	}
}