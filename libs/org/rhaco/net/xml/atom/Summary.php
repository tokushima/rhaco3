<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのsummaryモデル
 * @author tokushima
 */
class Summary extends \org\rhaco\Object{
	protected $type;
	protected $lang;
	protected $value;

	protected function __str__(){
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
		return ($bool) ? $result->get() : '';
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