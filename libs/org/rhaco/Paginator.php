<?php
namespace org\rhaco;
use \org\rhaco\net\Query;
/**
 * ページを管理するモデル
 * @author tokushima
 * @var integer $offset 開始位置
 * @var integer $limit 終了位置
 * @var integer $total 合計
 * @var integer $first 最初のページ番号 @['set'=>false]
 * @var integer $last 最後のページ番号 @['set'=>false]
 * @var mixed $current 現在位置
 * @var string $query_name pageを表すクエリの名前
 * @var mixed{} $vars query文字列とする値
 * @var mixed[] $contents １ページ分の内容
 * @var boolean $dynamic ダイナミックページネーションとするか @['set'=>false]
 * @var string $order 最後のソートキー
 */
class Paginator extends \org\rhaco\Object{
	protected $query_name = 'page';
	protected $vars = array();
	protected $current;
	protected $limit;	
	protected $order;
	protected $offset;
	protected $total;
	protected $first;
	protected $last;
	protected $contents = array();
	protected $dynamic = false;
	private $dynamic_vars = array(null,null,array(),null,false);

	/**
	 * 動的コンテンツのPaginater
	 * @param integer $paginate_by １ページの要素数
	 * @param string $marker 基点となる値
	 * @param string $prop 対象とするプロパティ名
	 * @return self
	 */
	static public function dynamic_contents($paginate_by=20,$marker=null,$prop=null){
		$self = new self($paginate_by);
		$self->dynamic = true;
		$self->dynamic_vars[3] = $prop;
		$self->current = $marker;
		$self->total = $self->first = $self->last = null;
		return $self;
		/***	
			$p = self::dynamic_contents(2,'C');
			$p->add('A');
			$p->add('B');
			$p->add('C');
			$p->add('D');
			$p->add('E');
			$p->add('F');
			$p->add('G');
			eq('A',$p->prev());
			eq('E',$p->next());
			eq('page=A',$p->query_prev());
			eq(array('C','D'),$p->contents());
			eq(null,$p->first());
			eq(null,$p->last());
		 */
	}
	protected function __new__($paginate_by=20,$current=1,$total=0){
		$this->limit($paginate_by);
		$this->total($total);
		$this->current($current);
		/***
			$p = new self(10);
			eq(10,$p->limit());
			eq(1,$p->first());
			$p->total(100);
			eq(100,$p->total());
			eq(10,$p->last());
			eq(1,$p->which_first(3));
			eq(3,$p->which_last(3));

			$p->current(3);
			eq(20,$p->offset());
			eq(true,$p->is_next());
			eq(true,$p->is_prev());
			eq(4,$p->next());
			eq(2,$p->prev());
			eq(1,$p->first());
			eq(10,$p->last());
			eq(2,$p->which_first(3));
			eq(4,$p->which_last(3));

			$p->current(1);
			eq(0,$p->offset());
			eq(true,$p->is_next());
			eq(false,$p->is_prev());

			$p->current(6);
			eq(5,$p->which_first(3));
			eq(7,$p->which_last(3));

			$p->current(10);
			eq(90,$p->offset());
			eq(false,$p->is_next());
			eq(true,$p->is_prev());
			eq(8,$p->which_first(3));
			eq(10,$p->which_last(3));
		 */
		/***
			$p = new self(3,2);
			$list = array(1,2,3,4,5,6,7,8,9);
			foreach($list as $v){
				$p->add($v);
			}
			eq(array(4,5,6),$p->contents());
			eq(2,$p->current());
			eq(1,$p->first());
			eq(3,$p->last());
			eq(9,$p->total());
		 */
		/***
			$p = new self(3,2);
			$list = array(1,2,3,4,5);
			foreach($list as $v){
				$p->add($v);
			}
			eq(array(4,5),$p->contents());
			eq(2,$p->current());
			eq(1,$p->first());
			eq(2,$p->last());
			eq(5,$p->total());
		 */
		/***
			$p = new self(3);
			$list = array(1,2);
			foreach($list as $v){
				$p->add($v);
			}
			eq(array(1,2),$p->contents());
			eq(1,$p->current());
			eq(1,$p->first());
			eq(1,$p->last());
			eq(2,$p->total());
		 */
	}
	protected function __get_query_name__(){
		return (empty($this->query_name)) ? 'page' : $this->query_name;
	}
	/**
	 * 
	 * 配列をvarsにセットする
	 * @param string[] $array
	 * @return self $this
	 */
	public function cp(array $array){
		foreach($obj as $name => $value){
			if(ctype_alpha($name[0])) $this->vars[$name] = (string)$value;
		}
		return $this;
	}
	/**
	 * 次のページ番号
	 * @return integer
	 */
	public function next(){
		if($this->dynamic) return $this->dynamic_vars[0];
		return $this->current + 1;
		/***
			$p = new self(10,1,100);
			eq(2,$p->next());
		*/
	}
	/**
	 * 前のページ番号
	 * @return integer
	 */
	public function prev(){
		if($this->dynamic){
			if(!isset($this->dynamic_vars[1]) && sizeof($this->dynamic_vars[2]) > 0) $this->dynamic_vars[1] = array_shift($this->dynamic_vars[2]);
			return $this->dynamic_vars[1];
		}
		return $this->current - 1;
		/***
			$p = new self(10,2,100);
			eq(1,$p->prev());
		*/
	}
	/**
	 * 次のページがあるか
	 * @return boolean
	 */
	public function is_next(){
		if($this->dynamic) return isset($this->dynamic_vars[0]);
		return ($this->last > $this->current);
		/***
			$p = new self(10,1,100);
			eq(true,$p->is_next());
			$p = new self(10,9,100);
			eq(true,$p->is_next());
			$p = new self(10,10,100);
			eq(false,$p->is_next());
		*/
	}
	/**
	 * 前のページがあるか
	 * @return boolean
	 */
	public function is_prev(){
		if($this->dynamic) return ($this->prev() !== null);
		return ($this->current > 1);
		/***
			$p = new self(10,1,100);
			eq(false,$p->is_prev());
			$p = new self(10,9,100);
			eq(true,$p->is_prev());
			$p = new self(10,10,100);
			eq(true,$p->is_prev());
		*/
	}
	/**
	 * 前のページを表すクエリ
	 * @return string
	 */
	public function query_prev(){
		return Query::get(array_merge(
							$this->ar_vars()
							,array($this->query_name()=>(($this->dynamic) ? $this->mn($this->prev()) : $this->prev()))
						));
		/***
			$p = new self(10,3,100);
			$p->query_name("page");
			$p->vars("abc","DEF");
			eq("abc=DEF&page=2",$p->query_prev());
		*/
	}
	/**
	 * 次のページを表すクエリ
	 * @return string
	 */
	public function query_next(){
		return Query::get(array_merge(
							$this->ar_vars()
							,array($this->query_name()=>(($this->dynamic) ? $this->dynamic_vars[0] : $this->next()))
						));
		/***
			$p = new self(10,3,100);
			$p->query_name("page");
			$p->vars("abc","DEF");
			eq("abc=DEF&page=4",$p->query_next());
		*/
	}
	/**
	 * orderを変更するクエリ
	 * @param string $order
	 * @param string $pre_order
	 * @return string
	 */
	public function query_order($order){
		if($this->is_vars('order')){
			$this->order = $this->in_vars('order');
			$this->rm_vars('order');
		}
		return Query::get(array_merge(
							$this->ar_vars()
							,array('order'=>$order,'porder'=>$this->order())
						));
		/***
			$p = new self(10,3,100);
			$p->query_name("page");
			$p->vars("abc","DEF");		
			$p->order("bbb");
			eq("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));
			
			$p = new self(10,3,100);
			$p->query_name("page");
			$p->vars("abc","DEF");
			$p->vars("order","bbb");
			eq("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));
		*/
	}
	/**
	 * 指定のページを表すクエリ
	 * @param integer $current 現在のページ番号
	 * @return string
	 */
	public function query($current){
		return Query::get(array_merge($this->ar_vars(),array($this->query_name()=>$current)));
		/***
			$p = new self(10,1,100);
			eq("page=3",$p->query(3));
		 */
	}
	protected function __set_current__($value){
		if(!$this->dynamic){
			$value = intval($value);
			$this->current = ($value === 0) ? 1 : $value;
			$this->offset = $this->limit * round(abs($this->current - 1));
		}
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
	private function mn($v){
		return isset($this->dynamic_vars[3]) ? 
				(is_array($v) ? $v[$this->dynamic_vars[3]] : (is_object($v) ? (($v instanceof Object) ? $v->{$this->dynamic_vars[3]}() : $v->{$this->dynamic_vars[3]}) : null)) :
				$v;
	}
	protected function __set_contents__($mixed){
		if($this->dynamic){
			if(!$this->dynamic_vars[4] && $this->current == $this->mn($mixed)) $this->dynamic_vars[4] = true;
			if($this->dynamic_vars[4]){
				if($this->dynamic_vars[0] === null && ($size=sizeof($this->contents)) <= $this->limit){
					if(($size+1) > $this->limit){
						$this->dynamic_vars[0] = $mixed;
					}else{
						$this->contents[] = $mixed;
					}
				}
			}else{
				if(sizeof($this->dynamic_vars[2]) >= $this->limit) array_shift($this->dynamic_vars[2]);
				$this->dynamic_vars[2][] = $mixed;
			}
		}else{
			$this->total($this->total+1);
			if($this->page_first() <= $this->total && $this->total <= ($this->offset + $this->limit)){
				$this->contents[] = $mixed;
			}
		}
	}
	protected function __set_total__($total){
		if(!$this->dynamic){
			$this->total = intval($total);
			$this->first = 1;
			$this->last = ($this->total == 0 || $this->limit == 0) ? 0 : intval(ceil($this->total / $this->limit));
		}
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
	protected function __is_first__($paginate){
		return ($this->which_first($paginate) !== $this->first);
	}
	protected function __is_last__($paginate){
		return ($this->which_last($paginate) !== $this->last());
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
		/***
			$p = new self(4,1,3);
			eq(1,$p->first());
			eq(1,$p->last());
			eq(false,$p->has_range());
			
			$p = new self(4,2,3);
			eq(1,$p->first());
			eq(1,$p->last());
			eq(false,$p->has_range());
			
			$p = new self(4,1,10);
			eq(1,$p->first());
			eq(3,$p->last());
			eq(true,$p->has_range());
			
			$p = new self(4,2,10);
			eq(1,$p->first());
			eq(3,$p->last());
			eq(true,$p->has_range());
		 */
	}
}