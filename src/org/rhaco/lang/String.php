<?php
namespace org\rhaco\lang;
/**
 * 文字列を表します
 * @author tokushima
 *
 */
class String{
	private $value;

	public function __construct($v){
		$this->value = $v;
	}
	/**
	 * 値を取得
	 * @return string
	 */
	public function get(){
		return $this->value;
	}
	/**
	 * 値をセットする
	 * @param string $v
	 */
	public function set($v){
		$this->value = $v;
	}
	public function __toString(){
		return $this->value;
	}
	/**
	 * オブジェクトに値をセットして返す
	 * @param mixed $obj
	 * @param string $src
	 * @return self
	 */
	static public function ref(&$obj,$src){
		$obj = new self($src);
		return $obj;
	}
}