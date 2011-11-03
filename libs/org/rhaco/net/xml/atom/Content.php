<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのcontentモデル
 * @author tokushima
 */
class Content extends \org\rhaco\Object{
	protected $type;
	protected $mode;
	protected $lang;
	protected $base;
	protected $value;

	protected function __str__(){
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
		return ($bool) ? $result->get() : '';
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