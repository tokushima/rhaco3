<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのcontentモデル
 * @author tokushima
 */
class Content extends \org\rhaco\net\xml\atom\Object{
	protected $type;
	protected $mode;
	protected $lang;
	protected $base;
	protected $value;

	public function xml(){
		$bool = false;
		$result = new \org\rhaco\Xml('content');
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				$bool = true;
				switch($name){
					case 'type':
					case 'mode':
						$result->attr($name,$value);
						break;
					case 'lang':
					case 'base':
						$result->attr('xml:'.$name,$value);
						break;
					case 'value':
						$result->value($this->{$name}());
						break;
				}
			}
		}
		if(!$bool) throw new \org\rhaco\net\xml\atom\NotfoundException();
		return $result;
	}
	protected function __str__(){
		try{
			return $this->xml()->get();
		}catch(\org\rhaco\net\xml\atom\NotfoundException $e){}
		return '';
	}
	static public function parse(&$src){
		$result = null;
		if(\org\rhaco\Xml::set($tag,$src,'content')){
			$result = new self();
			$result->type($tag->in_attr('type'));
			$result->mode($tag->in_attr('mode'));
			$result->lang($tag->in_attr('xml:lang'));
			$result->base($tag->in_attr('xml:base'));
			$result->value($tag->value());
			$src = str_replace($tag->plain(),'',$src);
		}
		return $result;
	}
}