<?php
namespace test\model;
/**
 * org.rhaco.Objectのテスト用
 * @author tokushima
 *
 */
class ObjectProps extends \org\rhaco\Object{
	public $aaa = 1;
	protected $bbb = 2;
	private $ccc = 3;
	protected $ddd = 4;
	protected $_eee = 5;
	protected $fff;
	
 	protected function __get_fff__(){
		return 6;
	}
}
