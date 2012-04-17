<?php
namespace org\rhaco;
/**
 * 例外
 * @author tokushima
 * @var string $group
 */
class Exception extends \Exception{
	protected $group;
	
	public function __construct($message=null,$group=null){
		$this->message = $message;
		$this->group = $group;
	}
	final public function getGroup(){
		return $this->group;
	}
}