<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのlinkモデル
 * @author tokushima
 */
class Link extends \org\rhaco\Object{
	protected $rel;
	protected $type;
	protected $href;

	protected function __str__(){
		$bool = false;
		$result = new \org\rhaco\Xml('link');
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				$bool = true;
				switch($name){
					case 'href':
					case 'rel':
					case 'type':
						$result->attr($name,$value);
						break;
				}
			}
		}
		return ($bool) ? $result->get() : '';
	}
	static public function parse(&$src){
		$result = array();
		\org\rhaco\Xml::set($x,'<:>'.$src.'</:>');
		foreach($x->in('link') as $in){
			$o = new self();
			$o->href($in->in_attr('href'));
			$o->rel($in->in_attr('rel'));
			$o->type($in->in_attr('type'));
			$result[] = $o;
			$src = str_replace($in->plain(),'',$src);
		}
		return $result;
	}
}