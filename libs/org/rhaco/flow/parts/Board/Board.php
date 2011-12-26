<?php
namespace org\rhaco\flow\parts;
/**
 * 掲示板
 * create table board(id integer primary key autoincrement,name text,comment text,created_at text);
 * @author tokushima
 *
 */
class Board extends \org\rhaco\flow\parts\RequestFlow{
	public function index(){
		if($this->is_post()){
			$obj = new Board\Model();
			$obj->name($this->in_vars("name"));
			$obj->comment($this->in_vars("comment"));
		
			try{
				$obj->save();
			}catch(\org\rhaco\Exceptions $e){}
		}
		$paginator = new \org\rhaco\Paginator(5,$this->in_vars("page",1));
		$this->vars('object_list',Board\Model::find_all(
					$paginator
					,\org\rhaco\store\db\Q::order('-id')
		));
		$this->vars("paginator",$paginator);
	}
}

