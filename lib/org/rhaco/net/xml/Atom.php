<?php
namespace org\rhaco\net\xml;
/**
 * Atom1.0を扱う
 * @author tokushima
 * @var timestamp $updated
 * @var atom.Link[] $link
 * @var atom.Entry[] $entry
 * @var atom.Author[] $author
 * @var string{} $xmlns
 */
class Atom extends \org\rhaco\net\xml\atom\Object{
	const XMLNS = 'http://www.w3.org/2005/Atom';
	protected $title;
	protected $subtitle;
	protected $id;
	protected $generator;
	protected $updated;
	protected $link;
	protected $entry;
	protected $author;
	protected $xmlns;

	protected function __init__(){
		$this->updated = time();
	}
	public function add($arg){
		if($arg instanceof \org\rhaco\net\xml\atom\Entry){
			$this->entry($arg);
		}else if($arg instanceof self){
			foreach($arg->ar_entry() as $entry) $this->entry[] = $entry;
		}else if($arg instanceof  \org\rhaco\net\xml\atom\AtomInterface){
			$entry = new \org\rhaco\net\xml\atom\Entry();
			$entry->id($arg->atom_id());
			$entry->title($arg->atom_title());
			$entry->published($arg->atom_published());
			$entry->updated($arg->atom_updated());
			$entry->issued($arg->atom_issued());

			$content = new \org\rhaco\net\xml\atom\Content();
			$content->value($arg->atom_content());
			$entry->content($content);

			$summary = new \org\rhaco\net\xml\atom\Summary();
			$summary->value($arg->atom_summary());
			$entry->summary($summary);

			$entry->link(new \org\rhaco\net\xml\atom\Link('href='.$arg->atom_href()));
			$entry->author(new \org\rhaco\net\xml\atom\Author('name='.$arg->atom_author()));
			$this->entry($entry);
		}
		return $this;
	}
	/**
	 * 文字列からAtomフィードを取得する
	 * @param string $src
	 * @return self
	 */
	static public function parse($src){
		$args = func_get_args();
		array_shift($args);

		if(\org\rhaco\Xml::set($tag,$src,'feed') && $tag->in_attr('xmlns') == self::XMLNS){
			$result = new self();
			$value = $tag->value();
			\org\rhaco\Xml::set($tag,'<:>'.$value.'</:>');
			$result->id($tag->f('id.value()'));
			$result->title($tag->f('title.value()'));
			$result->subtitle($tag->f('subtitle.value()'));
			$result->updated($tag->f('updated.value()'));
			$result->generator($tag->f('generator.value()'));

			$value = $tag->value();
			$result->entry = \org\rhaco\net\xml\atom\Entry::parse($value);
			$result->link = \org\rhaco\net\xml\atom\Link::parse($value);
			$result->author = \org\rhaco\net\xml\atom\Author::parse($value);
			return $result;
		}
		throw new \InvalidArgumentException('no atom');

	}
	protected function __fm_updated__(){
		return \org\rhaco\lang\Date::format_atom($this->updated);
	}
	protected function __str__(){
		$result = new \org\rhaco\Xml('feed');
		$result->attr('xmlns',self::XMLNS);
		foreach($this->ar_xmlns() as $ns => $url) $result->attr('xmlns:'.$ns,$url);
		foreach($this->props() as $name => $value){
			if(!empty($value)){
				switch($name){
					case 'title':
					case 'subtitle':
					case 'id':
					case 'generator':
						$result->add(new \org\rhaco\Xml($name,$value));
						break;
					case 'updated':
						$result->add(new \org\rhaco\Xml($name,\org\rhaco\lang\Date::format_atom($value)));
						break;
					default:
						if(is_array($value)){
							foreach($value as $v){
								try{
									$result->add(($v instanceof \org\rhaco\net\xml\atom\Object) ? $v->xml() : $v);
								}catch(\org\rhaco\net\xml\atom\NotfoundException $e){}
							}
						}
				}
			}
		}
		return $result->get();
	}
	/**
	 * 出力する
	 * @param string $name 出力する際のconent-typeの名前
	 */
	public function output($name=''){
		header(sprintf('Content-Type: application/atom+xml; name=%s',(empty($name)) ? uniqid('') : $name));
		print($this->get(true));
		exit;
	}
	/**
	 * 文字列に変換し取得
	 * @param boolean $enc encodingヘッダを付与するか
	 */
	public function get($enc=false){
		$value = (string)$this;
		return (($enc) ? (sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n",mb_detect_encoding($value))) : '').$value;
	}
	/**
	 * ソートする
	 * return $this
	 */
	public function sort(){
		/**
		 * ソート
		 * @param org.rhaco.net.xml.atom.Entry[] $entry
		 * @return org.rhaco.net.xml.atom.Entry[]
		 */
		if($this->has_object_module('sort')) $this->entry = $this->object_module('sort',$this->entry);
		return $this;
	}
	/**
	 * Atomに変換する
	 *
	 * @param string $title
	 * @param string $link
	 * @param iterator $entrys
	 * @return self
	 */
	static public function convert($title,$link,$entrys){
		$atom = new self();
		$atom->title($title);
		$atom->link(new \org\rhaco\net\xml\atom\Link('href='.$link));
		foreach($entrys as $entry) $atom->add($entry);
		return $atom;
	}
}
