<?php
namespace test\model;
/**
 * org.rhaco.Objectのテスト用
 * @var integer $aaa
 * @var boolean $bbb
 * @var integer $ccc
 * @var timestamp $ddd
 *
 */
class ObjectFm extends \org\rhaco\Object{
	protected $aaa;
	protected $bbb;
	protected $ccc;
	protected $ddd;
	
	protected function __get_ccc__(){
		$this->ddd(time());
		return 2;
	}	
}