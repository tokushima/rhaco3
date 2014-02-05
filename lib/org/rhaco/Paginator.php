<?php
namespace org\rhaco;
use \org\rhaco\net\Query;
/**
 * ページを管理するモデル
 * @author tokushima
 */
class Paginator implements \IteratorAggregate{
	private $query_name = 'page';
	private $vars = array();
	private $current;
	private $offset;
	private $limit;
	private $order;
	private $total;
	private $first;
	private $last;
	private $contents = array();
	private $dynamic = false;
	private $tmp = array(null,null,array(),null,false);

	public function getIterator(){
		return new \ArrayIterator(array(
						'current'=>$this->current()
						,'limit'=>$this->limit()
						,'offset'=>$this->offset()
						,'total'=>$this->total()
						,'order'=>$this->order()
				));
	}
	/**
	 * pageを表すクエリの名前
	 * @param string $name
	 * @return string
	 */
	public function query_name($name=null){
		if(isset($name)) $this->query_name = $name;
		return (empty($this->query_name)) ? 'page' : $this->query_name;
	}
	/**
	 * query文字列とする値をセットする
	 * @param string $key
	 * @param string $value
	 */
	public function vars($key,$value){
		$this->vars[$key] = $value;
	}
	/**
	 * 現在位置
	 * @param integer $value
	 * @return mixed
	 */
	public function current($value=null){
		if(isset($value) && !$this->dynamic){
			$value = intval($value);
			$this->current = ($value === 0) ? 1 : $value;
			$this->offset = $this->limit * round(abs($this->current - 1));
		}
		return $this->current;
	}
	/**
	 * 終了位置
	 * @param integer $value
	 * @return integer
	 */
	public function limit($value=null){
		if(isset($value)) $this->limit = $value;
		return $this->limit;
	}
	/**
	 * 開始位置
	 * @param integer $value
	 * @return integer
	 */
	public function offset($value=null){
		if(isset($value)) $this->offset = $value;
		return $this->offset;
	}
	/**
	 * 最後のソートキー
	 * @param string $value
	 * @param boolean $asc
	 * return string
	 */
	public function order($value=null,$asc=true){
		if(isset($value)) $this->order = ($asc ? '' :'-').(string)(is_array($value) ? array_shift($value) : $value);
		return $this->order;
	}
	/**
	 * 合計
	 * @param integer $value
	 * @return integer
	 */
	public function total($value=null){
		if(isset($value) && !$this->dynamic){
			$this->total = intval($value);
			$this->first = 1;
			$this->last = ($this->total == 0 || $this->limit == 0) ? 0 : intval(ceil($this->total / $this->limit));
		}
		return $this->total;
	}
	/**
	 * 最初のページ番号
	 * @return integer
	 */
	public function first(){
		return $this->first;
	}
	/**
	 * 最後のページ番号
	 * @return integer
	 */
	public function last(){
		return $this->last;
	}
	/**
	 * 指定のページ番号が最初のページか
	 * @param integer $page
	 * @return boolean
	 */
	public function is_first($page){
		return ((int)$this->which_first($page) !== (int)$this->first);
	}
	/**
	 * 指定のページ番号が最後のページか
	 * @param integer $page
	 * @return boolean
	 */
	public function is_last($page){
		return ($this->which_last($page) !== $this->last());
	}
	/**
	 * 動的コンテンツのPaginaterか
	 * @return boolean
	 */
	public function is_dynamic(){
		return $this->dynamic;
	}
	/**
	 * コンテンツ
	 * @param mixed $mixed
	 * @return array
	 */
	public function contents($mixed=null){
		if(isset($mixed)){
			if($this->dynamic){
				if(!$this->tmp[4] && $this->current == (isset($this->tmp[3]) ? (isset($mixed[$this->tmp[3]]) ? $mixed[$this->tmp[3]] : null) : $mixed)) $this->tmp[4] = true;
				if($this->tmp[4]){
					if($this->tmp[0] === null && ($size=sizeof($this->contents)) <= $this->limit){
						if(($size+1) > $this->limit){
							$this->tmp[0] = $mixed;
						}else{
							$this->contents[] = $mixed;
						}
					}
				}else{
					if(sizeof($this->tmp[2]) >= $this->limit) array_shift($this->tmp[2]);
					$this->tmp[2][] = $mixed;
				}
			}else{
				$this->total($this->total+1);
				if($this->page_first() <= $this->total && $this->total <= ($this->offset + $this->limit)){
					$this->contents[] = $mixed;
				}
			}
		}
		return $this->contents;
	}
	/**
	 * 動的コンテンツのPaginater
	 * @param integer $paginate_by １ページの要素数
	 * @param string $marker 基点となる値
	 * @param string $key 対象とするキー
	 * @return self
	 */
	static public function dynamic_contents($paginate_by=20,$marker=null,$key=null){
		$self = new self($paginate_by);
		$self->dynamic = true;
		$self->tmp[3] = $key;
		$self->current = $marker;
		$self->total = $self->first = $self->last = null;
		return $self;
	}
	public function __construct($paginate_by=20,$current=1,$total=0){
		$this->limit($paginate_by);
		$this->total($total);
		$this->current($current);
	}
	/**
	 * 
	 * 配列をvarsにセットする
	 * @param string[] $array
	 * @return self $this
	 */
	public function cp(array $array){
		foreach($array as $name => $value){
			if(ctype_alpha($name[0])) $this->vars[$name] = (string)$value;
		}
		return $this;
	}
	/**
	 * 次のページ番号
	 * @return integer
	 */
	public function next(){
		if($this->dynamic) return $this->tmp[0];
		return $this->current + 1;
	}
	/**
	 * 前のページ番号
	 * @return integer
	 */
	public function prev(){
		if($this->dynamic){
			if(!isset($this->tmp[1]) && sizeof($this->tmp[2]) > 0) $this->tmp[1] = array_shift($this->tmp[2]);
			return $this->tmp[1];
		}
		return $this->current - 1;
	}
	/**
	 * 次のページがあるか
	 * @return boolean
	 */
	public function is_next(){
		if($this->dynamic) return isset($this->tmp[0]);
		return ($this->last > $this->current);
	}
	/**
	 * 前のページがあるか
	 * @return boolean
	 */
	public function is_prev(){
		if($this->dynamic) return ($this->prev() !== null);
		return ($this->current > 1);
	}
	/**
	 * 前のページを表すクエリ
	 * @return string
	 */
	public function query_prev(){
		$prev = $this->prev();
		$vars = array_merge($this->vars,array($this->query_name()=>($this->dynamic && isset($this->tmp[3]) ? (isset($prev[$this->tmp[3]]) ? $prev[$this->tmp[3]] : null) : $prev)));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	/**
	 * 次のページを表すクエリ
	 * @return string
	 */
	public function query_next(){
		$vars = array_merge($this->vars,array($this->query_name()=>(($this->dynamic) ? $this->tmp[0] : $this->next())));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	/**
	 * orderを変更するクエリ
	 * @param string $order
	 * @param string $pre_order
	 * @return string
	 */
	public function query_order($order){
		if(isset($this->vars['order'])){
			$this->order = $this->vars['order'];
			unset($this->vars['order']);
		}
		return Query::get(array_merge(
							$this->vars
							,array('order'=>$order,'porder'=>$this->order())
						));
	}
	/**
	 * 指定のページを表すクエリ
	 * @param integer $current 現在のページ番号
	 * @return string
	 */
	public function query($current){
		$vars = array_merge($this->vars,array($this->query_name()=>$current));
		if(isset($this->order)) $vars['order'] = $this->order;
		return Query::get($vars);
	}
	
	/**
	 * コンテンツを追加する
	 * @param mixed $mixed
	 * @return boolean
	 */
	public function add($mixed){
		$this->contents($mixed);
		return (sizeof($this->contents) <= $this->limit);
	}
	/**
	 * 現在のページの最初の位置
	 * @return integer
	 */
	public function page_first(){
		if($this->dynamic) return null;
		return $this->offset + 1;
	}
	/**
	 * 現在のページの最後の位置
	 * @return integer
	 */
	public function page_last(){
		if($this->dynamic) return null;
		return (($this->offset + $this->limit) < $this->total) ? ($this->offset + $this->limit) : $this->total;
	}
	/**
	 * ページの最初の位置を返す
	 * @param integer $paginate
	 * @return integer
	 */
	public function which_first($paginate=null){
		if($this->dynamic) return null;
		if($paginate === null) return $this->first;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		$last = ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
		return (($last - $paginate) > 0) ? ($last - $paginate) : $first;
	}
	/**
	 * ページの最後の位置を返す
	 * @param integer $paginate
	 * @return integer
	 */
	public function which_last($paginate=null){
		if($this->dynamic) return null;
		if($paginate === null) return $this->last;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		return ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
	}
	/**
	 * ページとして有効な範囲のページ番号を有する配列を作成する
	 * @param integer $counter ページ数
	 * @return integer[]
	 */
	public function range($counter=10){
		if($this->dynamic) return array();
		if($this->which_last($counter) > 0) return range((int)$this->which_first($counter),(int)$this->which_last($counter));
		return array(1);
	}
	/**
	 * rangeが存在するか
	 * @return boolean
	 */
	public function has_range(){
		return (!$this->dynamic && $this->last > 1);
	}
}