<?php
namespace org\rhaco\store\queue;
/**
 * キューの制御
 * @author tokushima
 */
class Queue extends \org\rhaco\Object{
	/**
	 * 挿入
	 * @param string $type
	 * @param string $data
	 * @param integer $priority
	 */
	static public function insert($type,$data,$priority=3){
		$obj = new \org\rhaco\store\queue\Model();
		$obj->type($type);
		$obj->data($data);
		$obj->priority($priority);
		static::module('insert',$obj);
	}
	/**
	 * 取得
	 * @param string $type
	 * @param integer $priority
	 */
	static public function get($type,$priority=1){
		$obj = static::module('get',$type,$priority);
		if(!($obj instanceof \org\rhaco\store\queue\Model)) throw new \org\rhaco\store\queue\exception\IllegalDataTypeException('must be an of '.get_class($obj));
		return $obj;
	}
	/**
	 * 一覧で取得
	 * @param integer $limit
	 * @param string $type
	 * @param integer $priority
	 */
	static public function gets($limit,$type,$priority=1){
		$result = array();
		while(true){
			try{
				$result[] = self::get($type,$priority);
				$limit--;
				if($limit == 0) break;
			}catch(\org\rhaco\store\queue\exception\NotfoundException $e){
				break;
			}
		}
		return $result;
	}
	/**
	 * 削除
	 * @param string $key
	 */	
	static public function delete($key){
		if($key instanceof Model) $key = $key->id();
		static::module('delete',$key);
	}
	/**
	 * 終了とする
	 * @param string $key
	 */	
	static public function finish($key){
		if($key instanceof Model) $key = $key->id();
		static::module('finish',$key);
	}
	/**
	 * 終了していないものをリセットする
	 * @param string $type
	 * @param integer $sec
	 * @return org.rhaco.store.queue.Model[]
	 */	
	static public function reset($type,$sec=86400){
		return static::module('reset',$type,microtime(true) - ($sec * 100.0000));
	}
	/**
	 * 一覧を取得する
	 * @param string $type
	 * @param integer $page
	 * @param integer $paginate_by
	 * @param string $order
	 * @param string $pre_order
	 * @return mixed[] ($list,$paginator,$sorter)
	 */
	static public function view($type,$page=1,$paginate_by=30,$order=null,$pre_order=null){
		$paginator = new \org\rhaco\Paginator($paginate_by,$page);
		if(empty($order)) $order = 'id';
		$sorter = \org\rhaco\lang\Sorter::order($order,$pre_order);
		$list = array();
		if(static::has_module('view')){
			$list = static::module('view',$type,$paginator,$sorter);
		}
		$paginator->cp(array('type'=>$type,'order'=>$sorter));
		return array($list,$paginator,$sorter);
	}
	/**
	 * 終了したものを削除する
	 * @param string $type
	 * @param timestamp $fin
	 */
	static public function clean($type,$fin=null){
		static::module('clean',$type,$fin);
	}
}
