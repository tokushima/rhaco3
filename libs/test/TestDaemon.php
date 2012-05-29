<?php
namespace test;

class TestDaemon extends \org\rhaco\Daemon{
	static public function main(){
		\org\rhaco\Log::info('main');
		
		$obj = new \test\model\TestModelLog();
		$obj->run();
		
		$dao = new \test\model\TestModel();
		$dao->number(1);
		$dao->save();
		
		foreach(\test\model\TestModel::find(new \org\rhaco\Paginator(1),\org\rhaco\store\db\Q::order('-id')) as $o){
			\org\rhaco\Log::info($o->id());
		}
	}
}