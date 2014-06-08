<?php
namespace org\rhaco\net\xml\atom;
/**
 * Atomのentryモデル
 * @author tokushima
 * @var timestamp $published
 * @var timestamp $updated
 * @var timestamp $issued
 * @var Content $content
 * @var Summary $summary
 * @var org.rhaco.net.xml.atom.Link[] $link
 * @var org.rhaco.net.xml.atom.Author[] $author
 */
class Entry extends \org\rhaco\net\xml\atom\Object{
	protected $id;
	protected $title;
	protected $published;
	protected $updated;
	protected $issued;
	protected $xmlns;

	protected $content;
	protected $summary;
	protected $link;
	protected $author;

	public function xml(){
		$bool = false;
		$result = new \org\rhaco\Xml('entry');
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				$bool = true;
				switch($name){
					case 'xmlns':
						$result->attr('xmlns',$value);
						break;
					case 'id':
					case 'title':
						$result->add(new \org\rhaco\Xml($name,$value));
						break;
					case 'published':
					case 'updated':
					case 'issued':
						$result->add(new \org\rhaco\Xml($name,\org\rhaco\lang\Date::format_atom($value)));
						break;
					default:
						if(is_array($value)){
							foreach($value as $v){
								try{
									$result->add(($v instanceof \org\rhaco\net\xml\atom\Object) ? $v->xml() : (string)$v);
								}catch(\org\rhaco\net\xml\atom\NotfoundException $e){}
							}
						}else if(is_object($value)){
							try{
								$result->add(($value instanceof \org\rhaco\net\xml\atom\Object) ? $value->xml() : $value);
							}catch(\org\rhaco\net\xml\atom\NotfoundException $e){}
						}else{
							$result->add(new \org\rhaco\Xml($name,$value));
							break;
						}
				}
			}
		}
		if(!$bool) throw new \org\rhaco\net\xml\atom\NotfoundException();
		return $result;
	}
	protected function __init__(){
		$this->published = time();
		$this->updated = time();
		$this->issued = time();
	}
	public function get($enc=false){
		$value = sprintf('%s',$this);
		return (($enc) ? (sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n",mb_detect_encoding($value))) : '').$value;
	}
	protected function __str__(){
		try{
			return $this->xml()->get();
		}catch(\org\rhaco\net\xml\atom\NotfoundException $e){}
		return '';
	}
	public function first_href(){
		return (!empty($this->link)) ? current($this->link)->href() : null;
	}
	protected function __fm_content__(){
		return (isset($this->content)) ? $this->content->value() : null;
	}
	static public function parse(&$src){
		$args = func_get_args();
		array_shift($args);

		$result = array();
		\org\rhaco\Xml::set($x,'<:>'.$src.'</:>');
		foreach($x->in('entry') as $in){
			$o = new self();
			$o->id($in->f('id.value()'));
			$o->title($in->f('title.value()'));
			$o->published($in->f('published.value()'));
			$o->updated($in->f('updated.value()'));
			$o->issued($in->f('issued.value()'));

			$value = $in->value();
			$o->content = Content::parse($value);
			$o->summary = Summary::parse($value);
			$o->link = Link::parse($value);
			$o->author = Author::parse($value);

			$result[] = $o;
			$src = str_replace($in->plain(),'',$src);
		}
		return $result;
	}
}