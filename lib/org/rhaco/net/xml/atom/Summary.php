<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのsummaryモデル
 * @author tokushima
 */
class Summary extends \org\rhaco\net\xml\atom\Object{
	protected $type;
	protected $lang;
	protected $value;

	public function xml(){
		$bool = false;
		$result = new \org\rhaco\Xml('summary');
		$result->escape(true);
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				$bool = true;
				switch($name){
					case 'type':
						$result->attr($name,$value);
						break;
					case 'lang':
						$result->attr('xml:'.$name,$value);
						break;
					case 'value':
						$result->value($value);
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
		if(\org\rhaco\Xml::set($tag,$src,'summary')){
			$result = new self();
			$result->type($tag->in_attr('type','text'));
			$result->lang($tag->in_attr('xml:lang'));
			$result->value($tag->value());
			
			$src = str_replace($tag->plain(),'',$src);
		}
		return $result;
	}
}