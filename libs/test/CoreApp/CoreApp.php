<?php
namespace test;

class CoreApp extends \org\rhaco\flow\parts\RequestFlow{
	protected function __init__(){
		$this->vars('init_var','INIT');
	}
	/***
		# __setup__
		
		eq(true,true);
	 */
	
	public function under_var(){
		$this->vars('_hoge','hogehoge');
		$this->vars('hoge','ABC');
		/***
			# test
			eq(true,true);
		 */
	}
	public function raise(){
		throw new \Exception('hoge');
	}
	public function add_exceptions(){
		\org\rhaco\Exceptions::add(new \Exception('hoge'));
	}
	
	/***
		# __teardown__		
		eq(true,true);
		
	 */
	public function exception_test(){
		/***
			# a
			try{
				\org\rhaco\Exceptions::add(new \Exception());
				\org\rhaco\Exceptions::throw_over();
				fail();
			}catch(\org\rhaco\Exceptions $e){
				success();
			}
		 */
		/***
			# b
			try{
				\org\rhaco\Exceptions::throw_over();
				success();
			}catch(\org\rhaco\Exceptions $e){
				fail();
			}
		 */
	}
}