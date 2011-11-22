<?php
namespace org\rhaco\store\queue\module;
use \org\rhaco\store\db\Q;
/**
 * キューのモジュール
 * @author tokushima
 *
 */
class Dao{
	/**
	 * 挿入
	 * @param \org\rhaco\store\queue\Model $obj
	 */
	public function insert(\org\rhaco\store\queue\Model $obj){
		$dao = new \org\rhaco\store\queue\module\Dao\QueueDao();
		$dao->set($obj);
		$dao->save();
		\org\rhaco\store\queue\module\Dao\QueueDao::commit();
	}
	/**
	 * 削除
	 * @param string $id
	 */
	public function delete($id){
		try{
			$obj = \org\rhaco\store\queue\module\Dao\QueueDao::find_get(Q::eq('id',$id));
			$obj->delete();
			\org\rhaco\store\queue\module\Dao\QueueDao::commit();
			return true;
		}catch(\Exception $e){
			return false;
		}
	}
	/**
	 * 終了
	 * @param string $id
	 */
	public function finish($id){
		try{
			$obj = \org\rhaco\store\queue\module\Dao\QueueDao::find_get(Q::eq('id',$id));
			$obj->fin(time());
			$obj->save();
			\org\rhaco\store\queue\module\Dao\QueueDao::commit();
			return true;
		}catch(\Exception $e){
			return false;
		}
	}
	/**
	 * 取得
	 * @param string $type
	 * @param integer $priority
	 * @throws \org\rhaco\store\db\exception\DaoException
	 * @throws \org\rhaco\store\queue\exception\NotfoundException
	 */
	public function get($type,$priority){
		while(true){
			try{
				$object = \org\rhaco\store\queue\module\Dao\QueueDao::find_get(
							Q::gte('priority',$priority)
							,Q::eq('type',$type)
							,Q::eq('fin',null)
							,Q::eq('lock',null)
							,Q::order('priority,id')
						);
				$object->lock(microtime(true));
				$object->save(Q::eq('lock',null));
				\org\rhaco\store\queue\module\Dao\QueueDao::commit();
				return $object->get();
			}catch(\org\rhaco\store\db\exception\DaoBadMethodCallException $e){
			}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
				throw new \org\rhaco\store\queue\exception\NotfoundException($type.' not found');
			}
		}
	}
	
	/**
	 * リセット
	 * @param string $type
	 * @param integer $priority
	 * @throws \org\rhaco\store\db\exception\DaoException
	 * @throws \org\rhaco\store\queue\exception\NotfoundException
	 */
	public function reset($type,$lock_time){
		$result = array();
		foreach(\org\rhaco\store\queue\module\Dao\QueueDao::find(
				Q::eq('fin',null)
				,Q::eq('type',$type)
				,Q::neq('lock',null)
				,Q::lte('lock',$lock_time)
			) as $obj){
			try{
				$obj->lock(null);
				$obj->save(Q::eq('fin',null),Q::eq('id',$obj->id()));
				\org\rhaco\store\queue\module\Dao\QueueDao::commit();
				$result[] = $obj->get();
			}catch(\BadMethodCallException $e){
			}
		}
		return $result;
	}
	/**
	 * 一覧
	 * @param string $type
	 * @param \org\rhaco\Paginator $paginator
	 * @param string $sorter
	 * @return \org\rhaco\store\queue\Model[]
	 */
	public function view($type,\org\rhaco\Paginator $paginator,$sorter){
		$q = new Q();
		$q->add(Q::eq('fin',null));
		if(!empty($type)) $q->add(Q::eq('type',$type));
		$result = array();
		foreach(\org\rhaco\store\queue\module\Dao\QueueDao::find($q,$paginator,Q::order($sorter)) as $m){
			$result[] = $m->get();
		}
		return $result;
	}
	/**
	 * 終了したものを削除する
	 * @param string $type
	 * @param timestamp $fin
	 */
	public function clean($type,$fin){
		foreach(\org\rhaco\store\queue\module\Dao\QueueDao::find(Q::eq('type',$type),Q::neq('fin',null),Q::lte('fin',$fin)) as $obj){
			try{
				$obj->delete();
			}catch(\BadMethodCallException $e){}
		}
		\org\rhaco\store\queue\module\Dao\QueueDao::commit();
	}
}