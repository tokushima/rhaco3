<?php
namespace org\rhaco\store\db\module;
/**
 * SQLiteモジュール
 * @author tokushima
 */
class Sqlite extends Base{
	protected $order_random_str = 'random()';

	public function connect($name,$host,$port,$user,$password,$sock){
		if(!extension_loaded('pdo_sqlite')) throw new \RuntimeException('pdo_sqlite not supported');
		if(empty($host) && empty($name)) throw new \InvalidArgumentException('undef connection name');
		$con = null;
		if(empty($host)) $host = getcwd();
		$host = str_replace('\\','/',$host);
		if(substr($host,-1) != '/') $host = $host.'/';
		try{
			$con = new \PDO(sprintf('sqlite:%s',($host == ':memory:') ? ':memory:' : $host.$name));
		}catch(\PDOException $e){
			throw new \org\rhaco\store\db\exception\DaoException($e->getMessage());
		}
		return $con;
	}
	public function last_insert_id_sql(){
		return \org\rhaco\store\db\Daq::get('select last_insert_rowid() as last_insert_id;');
	}
}
