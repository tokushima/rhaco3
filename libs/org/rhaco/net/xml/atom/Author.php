<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのauthorモデル
 * @author tokushima
 */
class Author extends \org\rhaco\Object{
	protected $name;
	protected $url;
	protected $email;
	
	protected function __str__(){
		$bool = false;
		$result = new \org\rhaco\Xml('author');
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				$bool = true;
				switch($name){
					case 'name':
					case 'url':
					case 'email':
						$result->add(new \org\rhaco\Xml($name,$value));
						break;
				}
			}
		}
		return ($bool) ? $result->get() : '';
	}
	static public function parse(&$src){
		$result = array();
		\org\rhaco\Xml::set($x,'<:>'.$src.'</:>');
		foreach($x->in('author') as $in){
			$src = str_replace($in->plain(),'',$src);
			$o = new self();
			$o->name($in->f('name.value()'));
			$o->url($in->f('url.value()'));
			$o->email($in->f('email.value()'));
			if(!$o->is_url()) $o->url($in->f('uri.value()'));
			$result[] = $o;
		}
		return $result;
	}
}