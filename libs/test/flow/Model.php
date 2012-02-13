<?php
namespace test\flow;
class Model{
	public function insert(){
		$model = new \test\model\TestModel();
		$model->id(100);
		$model->string('abcdefg');
		$model->save();
	}
	public function update(){
		$model = \test\model\TestModel::find_get(\org\rhaco\store\db\Q::eq('string','abcdefg'));
		$model->text('xyz');
		$model->save();
	}
	public function get(){
		$model = \test\model\TestModel::find_get(\org\rhaco\store\db\Q::eq('string','abcdefg'));
		return array('model'=>$model);
	}
	public function delete(){
		$model = \test\model\TestModel::find_get(\org\rhaco\store\db\Q::eq('string','abcdefg'));
		$model->delete();
	}
}