<?php
namespace org\rhaco;
/**
 * 例外
 * @author tokushima
 * @var string $group
 */
class Exception extends \Exception{
	protected $group;
	
	public function __construct($message=null,$group='exceptions'){
		if(is_object($group)){
			$class_name = is_object($group) ? get_class($group) : $group;
			$l = str_replace("\\",'.',$class_name);
			$s = basename(str_replace("\\",'/',$class_name));
			if(isset($message)) $this->message = str_replace(array('{L}','{S}'),array($l,$s),$message);
			$this->group = $l;
		}else{
			if(isset($message)) $this->message = $message;
			$this->group = $group;
		}
	}
	final public function getGroup(){
		return $this->group;
	}
}