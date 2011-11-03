<?php
namespace org\rhaco\store\db\module;
/**
 * Mysqlモジュール
 * @author tokushima
 */
class Mysql extends Base{
	public function connect($name,$host,$port,$user,$password,$sock){
		if(!extension_loaded('pdo_mysql')) throw new \RuntimeException('pdo_mysql not supported');
		$con = null;
		if(empty($host)) $host = 'localhost';
		if(empty($name)) throw new \InvalidArgumentException('undef connection name');
		$dsn = empty($sock) ?
					sprintf('mysql:dbname=%s;host=%s;port=%d',$name,$host,((empty($port) ? 3306 : $port))) :
					sprintf('mysql:dbname=%s;unix_socket=%s',$name,$sock);
		try{
			$con = new \PDO($dsn,$user,$password);
			if(!empty($this->encode)) $this->prepare_execute($con,'set names '.$this->encode);
			$this->prepare_execute($con,'set autocommit=0');
			$this->prepare_execute($con,'set session transaction isolation level read committed');
		}catch(\PDOException $e){
			throw new \org\rhaco\store\db\exception\DaoException((strpos($e->getMessage(),'SQLSTATE[HY000]') === false) ? $e->getMessage() : 'not supported '.__CLASS__);
		}
		return $con;
	}
	private function prepare_execute($con,$sql){
		$st = $con->prepare($sql);
		$st->execute();
		$error = $st->errorInfo();
		if((int)$error[0] !== 0) throw new \InvalidArgumentException($error[2]);
	}
	public function last_insert_id_sql(){
		return \org\rhaco\store\db\Daq::get('select last_insert_id() as last_insert_id;');
	}
}