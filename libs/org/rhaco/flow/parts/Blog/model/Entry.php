<?php
namespace org\rhaco\flow\parts\Blog\model;
use \org\rhaco\store\db\Q;
/**
 * ブログ用のDBモデル
 * @author tokushima
 * @var serial $id
 * @var alnum $name @{"max":50}
 * @var string $title @{"max":100,"require":true}
 * @var text $description @{"require":true}
 * @var timestamp $create_date@{"auto_now_add":true}
 * @conf string $atom_url_base Atom feedのベースURL
 */
class Entry extends \org\rhaco\store\db\Dao implements \org\rhaco\net\xml\atom\AtomInterface{
	protected $id;
	protected $name;
	protected $title;
	protected $description;
	protected $tag;
	protected $create_date;
	
	protected function __fm_description__(){
		if($this->has_object_module('format')) return $this->object_module('format',$this->description);
		return $this->description;
	}
	protected function __str__(){
		return $this->title();
	}
	protected function __before_save__(){
		if(!empty($this->tag)) $this->tag = ' '.trim($this->tag).' ';
	}
	protected function __after_save__(){
		if(empty($this->name)){
			$this->name = (int)$this->id;
			$this->save();
		}
		if(!empty($this->tag)){
			$tags = explode(' ',$this->tag);
			$btags = static::find_distinct('name',Q::in('name',$tags));
			
			foreach($tags as $tag){
				if($tag !== '' && !in_array($tag,$btags)){
					$t = new Tag();
					$t->name($tag);
					$t->save();
				}
			}
		}
	}
	protected function __find_conds__(){
		return Q::b(Q::order('-id'));
	}
	/**
	 * タグの一覧
	 * @return string[]
	 */
	public function tag_list(){
		$tags = array();
		if(!empty($this->tag)){
			foreach(explode(' ',$this->tag) as $tag){
				if(!empty($tag)) $tags[] = $tag;
			}
		}
		return $tags;
	}
	public function atom_id(){
		return $this->id();
	}
	public function atom_title(){
		return $this->title();
	}
	public function atom_published(){
		return $this->create_date();
	}
	public function atom_updated(){
		return $this->create_date();
	}
	public function atom_issued(){
		return $this->create_date();
	}
	public function atom_content(){
		return $this->fm_description();
	}
	public function atom_summary(){
		return $this->fm_description();
	}
	public function atom_href(){
		return \org\rhaco\Conf::get('atom_url_base');
	}
	public function atom_author(){}
}
