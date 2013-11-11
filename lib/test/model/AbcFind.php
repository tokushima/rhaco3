<?php
namespace test\model;
use \org\rhaco\store\db\Q;

class AbcFind extends Find{
	protected function __find_conds__(){
		return Q::b(Q::eq("value1","abc"));
	}
}
