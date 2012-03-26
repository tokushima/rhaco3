<?php
namespace org\rhaco\flow\module;
use \org\rhaco\store\db\Q;
/**
 * Daoでセッションを扱うモジュール
 * @author tokushima
 * @var string $id @['primary'=>true]
 * @var text $data
 * @var number $expires
 */
class SessionDao extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $data;
	protected $expires;

	protected function __init__(){
		$this->expires = time();
	}
	protected function __before_update__(){
		$this->expires = time();
	}
	protected function __set_data__($value){
		$this->data = ($value === null) ? '' : $value;
	}
	/**
	 * @param string $session_name
	 * @param string $id
	 * @param string $save_path
	 * @return boolean
	 */
	public function session_verify($session_name,$id,$save_path){
		try{
			return (static::find_count(Q::eq('id',$id)) === 1);
		}catch(\Exception $e){}
		return false;
	}
	/**
	 * @param string $id
	 * @return string
	 */
	public function session_read($id){
		try{
			$obj = static::find_get(Q::eq('id',$id));
			return $obj->data();
		}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
		}catch(\Exception $e){
		}
		return '';
	}
	/**
	 * @param string $id
	 * @param string $sess_data
	 * @return boolean
	 */
	public function session_write($id,$sess_data){
		try{
			$obj = new self();
			$obj->id($id);
			$obj->data($sess_data);

			try{
				$obj->save();
			}catch(\org\rhaco\store\db\exception\DaoBadMethodCallException $r){}
			return true;
		}catch(\Exception $e){
		}
		return false;
	}
	/**
	 * @param string $id
	 * @return boolean
	 */
	public function session_destroy($id){
		try{
			$obj = new self();
			$obj->id($id);
			try{
				$obj->delete(true);
			}catch(\org\rhaco\store\db\exception\DaoBadMethodCallException $r){}
			return true;
		}catch(\Exception $e){
		}
		return false;
	}
	/**
	 * @param int $maxlifetime
	 * @return boolean
	 */
	public function session_gc($maxlifetime){
		try{
			static::find_delete(Q::lt('expires',time() - $maxlifetime));
			static::commit();
			return true;
		}catch(\Exception $e){
		}
		return false;
	}
}
