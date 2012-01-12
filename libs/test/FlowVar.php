<?php
namespace test;
/**
 * 
 * Enter description here ...
 * @author tokushima
 *
 */
class FlowVar extends \org\rhaco\Object{	
	protected $aaa = 'AAA';
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function bbb(){
		/**
		 * モジュールコメント
		 * @param string $n えぬ
		 * @param integer $m えむ
		 */
		$this->object_module('abcdefg',$n,$m);
		return 'BBB';
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	static public function ccc(){
		/**
		 * スタティックモジュールコメント
		 * @param string $n えぬ
		 * @param integer $m えむ
		 */
		self::module('xyz',$n,$m);
		return 'CCC';
	}
}