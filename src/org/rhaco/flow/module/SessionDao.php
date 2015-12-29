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
	 * @module org.rhaco.net.Session
	 * @param string $id
	 * @return string
	 */
	public function session_read($id){
		try{
			$obj = static::find_get(Q::eq('id',$id));
			return $obj->data();
		}catch(\Exception $e){
		}
		return '';
	}
	/**
	 * @module org.rhaco.net.Session
	 * @param string $id
	 * @param string $sess_data
	 */
	public function session_write($id,$sess_data){
		try{
			$obj = new self();
			$obj->id($id);
			$obj->data($sess_data);
			$obj->save();
		}catch(\Exception $e){
		}
	}
	/**
	 * @module org.rhaco.net.Session
	 * @param string $id
	 * @return boolean
	 */
	public function session_destroy($id){
		try{
			static::find_delete(Q::eq('id',$id));			
			return true;
		}catch(\Exception $e){
		}
		return false;
	}
	/**
	 * @module org.rhaco.net.Session
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
