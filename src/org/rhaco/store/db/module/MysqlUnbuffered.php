<?php
namespace org\rhaco\store\db\module;
/**
 * Mysqlモジュール (Unbuffered)
 * @author tokushima
 */
class MysqlUnbuffered extends Mysql{	
	/**
	 * @module org.rhaco.store.db.Dbc
	 * @param string $name
	 * @param string $host
	 * @param number $port
	 * @param string $user
	 * @param string $password
	 * @param string $sock
	 * @param boolean $autocommit
	 * @see org\rhaco\store\db\module.Base::connect()
	 */
	public function connect($name,$host,$port,$user,$password,$sock,$autocommit){
		if(!extension_loaded('pdo_mysql')) throw new \RuntimeException('pdo_mysql not supported');
		$con = null;
		if(empty($name)){
			throw new \InvalidArgumentException('undef connection name');
		}
		if(empty($host)){
			$host = 'localhost';
		}
		if(!isset($user) && !isset($password)){
			$user = 'root';
			$password = 'root';
		}		
		$dsn = empty($sock) ?
					sprintf('mysql:dbname=%s;host=%s;port=%d',$name,$host,((empty($port) ? 3306 : $port))) :
					sprintf('mysql:dbname=%s;unix_socket=%s',$name,$sock);
		try{
			$con = new \PDO($dsn,$user,$password);
			$con->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,false);
			
			if(!$autocommit){
				$this->prepare_execute($con,'set autocommit=0');
				$this->prepare_execute($con,'set session transaction isolation level read committed');
			}
			if(!empty($this->encode)){
				$this->prepare_execute($con,'set names \''.$this->encode.'\'');
			}
			if(!empty($this->timezone)){
				$this->prepare_execute($con,'set time_zone=\''.$this->timezone.'\'');
			}
		}catch(\PDOException $e){
			throw new \org\rhaco\store\db\exception\ConnectionException((strpos($e->getMessage(),'SQLSTATE[HY000]') === false) ? $e->getMessage() : 'not supported '.__CLASS__);
		}
		return $con;
	}
}