<?php
namespace test;
/**
 * テストクラス
 * @author tokushima
 *
 */
class CoreApp extends \org\rhaco\flow\parts\RequestFlow{
	protected function __init__(){
		$this->vars('init_var','INIT');
	}	
	public function under_var(){
		$this->vars('_hoge','hogehoge');
		$this->vars('hoge','ABC');
	}
	public function raise(){
		throw new \Exception('hoge');
	}
	public function add_exceptions(){
		\org\rhaco\Exceptions::add(new \Exception('hoge'));
	}
	public function exception_test(){
	}
}