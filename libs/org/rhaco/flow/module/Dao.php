<?php
namespace org\rhaco\flow\module;
/**
 * Daoを制御するモジュール
 * @author tokushima
 */
class Dao{
	/**
	 * @module org.rhaco.Flow
	 * @param \Exception $exception
	 */
	public function flow_handle_exception(\Exception $exception){
		\org\rhaco\store\db\Dao::rollback_all();
	}
}