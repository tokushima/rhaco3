<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\store\db\Q;
/**
 * Blog
 * @author tokushima
 * @class @['maps'=>['index','detail','tag','atom']]
 */
class Blog extends \org\rhaco\flow\parts\RequestFlow{
	protected function __init__(){
		$names = Blog\model\Tag::find_distinct('name');
		sort($names);
		$this->vars('tag_name_list',$names);
		$this->vars('query',$this->in_vars('query'));
	}
	/**
	 * 記事一覧
	 * @arg integer $paginate_by
	 * @request integer $page
	 * @rewuest string $query
	 * @context BlogEntry[] $object_list
	 * @context Paginator $paginator
	 */
	public function index(){
		$paginator = new \org\rhaco\Paginator($this->map_arg('paginate_by',1),$this->in_vars('page',1));
		$object_list = array();
		foreach(Blog\model\Entry::find(Q::match($this->in_vars('query'),Q::IGNORE),$paginator) as $obj){
			$object_list[] = $obj->set_object_module($this);
		}
		$this->vars('object_list',$object_list);
		$this->vars('paginator',$paginator->cp(array('query'=>$this->in_vars('query'))));
	}
	/**
	 * タグによる絞り込んだ記事一覧
	 * @param string $tag
	 * @arg integer $paginate_by
	 * @arg string $tag
	 * @request integer $page
	 * @rewuest string $query
	 * @context Entry[] $object_list
	 * @context Paginator $paginator
	 */
	public function tag($tag=null){
		$paginator = new \org\rhaco\Paginator($this->map_arg('paginate_by',1),$this->in_vars('page',1));
		$tag = $this->map_arg('tag',$tag);
		$object_list = array();
		foreach(Blog\model\Entry::find(
					Q::match($this->in_vars('query'),Q::IGNORE)
					,Q::contains('tag',' '.$tag.' ')
					,$paginator
					,Q::order('-id')
				) as $obj){
			$object_list[] = $obj->set_object_module($this);
		}
		$this->vars('object_list',$object_list);
		$this->vars('paginator',$paginator->cp(array('query',$this->in_vars('query'))));
	}
	/**
	 * Atom1.0での出力
	 * @conf string[] $atom string title,string base url
	 */
	public function atom(){
		$object_list = array();
		foreach(Blog\model\Entry::find(
					new \org\rhaco\Paginator(20)
					,Q::order('-id')
					,Q::gt("create_date",\org\rhaco\lang\Date::add_day(-14))
				) as $obj){
			$object_list[] = $obj->set_object_module($this);
		}
		list($title,$url) = \org\rhaco\Conf::get('atom',null,array('title','url'));
		\org\rhaco\net\xml\Atom::convert($title,$url,$object_list)->output();
	}
	/**
	 * 指定の記事
	 * @param string $name
	 * @context BlogEntry $object
	 */
	public function detail($name){
		$this->vars('object_list',array(Blog\model\Entry::find_get(Q::eq('name',$name))->set_object_module($this)));
	}
	/**
	 * 整形された $srcを返す
	 * @param string $src
	 * @return string
	 */
	public function format($src){
		if($this->has_object_module('format')) return $this->object_module('format',$src);
		return $src;
	}
}
