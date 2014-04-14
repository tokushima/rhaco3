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
		/**
		 * キューの挿入
		 * @param \org\rhaco\store\queue\Model $obj
		 */
		static::module('insert',$obj);
	}
	/**
	 * 取得
	 * @param string $type
	 * @param integer $priority
	 */
	static public function get($type,$priority=1){
		/**
		 * キューの取得
		 * @param string $type キューの種類
		 * @param integer $priority 優先度
		 */
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
				if($limit <= 0) break;
				$result[] = self::get($type,$priority);
				$limit--;
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
		/**
		 * キューを削除
		 * @param \org\rhaco\store\queue\Model $key
		 */
		static::module('delete',$key);
	}
	/**
	 * 終了とする
	 * @param string $key
	 */	
	static public function finish($key){
		if($key instanceof Model) $key = $key->id();
		/**
		 * キューを終了にする
		 * @param \org\rhaco\store\queue\Model $key
		 */
		static::module('finish',$key);
	}
	/**
	 * 終了していないものをリセットする
	 * @param string $type キューの種類
	 * @param integer $sec
	 * @return org.rhaco.store.queue.Model[]
	 */	
	static public function reset($type,$sec=86400){
		$time = microtime(true) - (float)$sec;
		/**
		 * 終了していないキューをリセットする
		 * @param string $type キューの種類
		 * @param integer $time この時間以降のものを対象とする
		 */
		return static::module('reset',$type,$time);
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
			/**
			 * キューの一覧
			 * @param string $type キューの種類
			 * @param \org\rhaco\Paginator $paginator
			 * @param string $sorter 順序のキー
			 */
			$list = static::module('view',$type,$paginator,$sorter);
		}
		$paginator->cp(array('type'=>$type,'order'=>$sorter));
		return array($list,$paginator,$sorter);
	}
	/**
	 * 終了したものを削除する
	 * @param string $type キューの種類
	 * @param timestamp $fin 終了時間の秒
	 * @param integer $paginate_by 一度に削除する数
	 */
	static public function clean($type,$fin=null,$paginate_by=100){
		if(empty($fin)) $fin = time();
		$paginator = new \org\rhaco\Paginator($paginate_by);
		/**
		 * 終了したものを削除する
		 * @param string  $type キューの種類
		 * @param integer $fin この時間未満のものを対象とする
		 */
		static::module('clean',$type,$fin,$paginator);
	}
}
