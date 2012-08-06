<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのlinkモデル
 * @author tokushima
 */
class Link extends \org\rhaco\net\xml\atom\Object{
	protected $rel;
	protected $type;
	protected $href;

	public function xml(){
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