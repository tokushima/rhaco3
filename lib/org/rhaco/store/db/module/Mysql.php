<?php
namespace org\rhaco\store\db\module;
/**
 * Mysqlモジュール
 * @author tokushima
 */
class Mysql extends Base{	
	/**
	 * @module org.rhaco.store.db.Dbc
	 * @param string $name
	 * @param string $host
	 * @param number $port
	 * @param string $user
	 * @param string $password
	 * @param string $sock
	 * @see org\rhaco\store\db\module.Base::connect()
	 */
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
			$this->prepare_execute($con,'set autocommit=0');
			$this->prepare_execute($con,'set session transaction isolation level read committed');
			if(!empty($this->encode)) $this->prepare_execute($con,'set names \''.$this->encode.'\'');
			if(!empty($this->timezone)) $this->prepare_execute($con,'set time_zone=\''.$this->timezone.'\'');
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
	/**
	 * @module org.rhaco.store.db.Dbc
	 * (non-PHPdoc)
	 * @see org\rhaco\store\db\module.Base::last_insert_id_sql()
	 */
	public function last_insert_id_sql(){
		return new \org\rhaco\store\db\Daq('select last_insert_id() as last_insert_id;');
	}
	/**
	 * create table
	 * @module org.rhaco.store.db.Dbc
	 * @param org.rhaco.store.db.Dao $dao
	 */
	public function create_table_sql(\org\rhaco\store\db\Dao $dao){
		$quote = function($name){
			return '`'.$name.'`';
		};
		$to_column_type = function($dao,$type,$name) use($quote){
			switch($type){
				case '':
				case 'mixed':
				case 'string':
					return $quote($name).' varchar('.$dao->prop_anon($name,'max',255).')';
				case 'alnum':
				case 'text':
					return $quote($name).(($dao->prop_anon($name,'max') !== null) ? ' varchar('.$dao->prop_anon($name,'max').')' : ' text');
				case 'number':
					return $quote($name).' '.(($dao->prop_anon($name,'decimal_places') !== null) ? sprintf('numeric(%d,%d)',26-$dao->prop_anon($name,'decimal_places'),$dao->prop_anon($name,'decimal_places')) : 'double');
				case 'serial': return $quote($name).' int auto_increment';
				case 'boolean': return $quote($name).' int(1)';
				case 'timestamp': return $quote($name).' timestamp';
				case 'date': return $quote($name).' date';
				case 'time': return $quote($name).' int';
				case 'intdate': 
				case 'integer': return $quote($name).' int';
				case 'email': return $quote($name).' varchar(255)';
				case 'choice': return $quote($name).' varchar(255)';
				default: throw new \InvalidArgumentException('undefined type `'.$type.'`');
			}
		};
		$columndef = $primary = array();
		$sql = 'create table '.$quote($dao->table()).'('.PHP_EOL;
		foreach($dao->props(false) as $prop_name => $v){
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$to_column_type($dao,$dao->prop_anon($prop_name,'type'),$prop_name).' null ';
				$columndef[] = $column_str;
				if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial') $primary[] = $quote($prop_name);
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		if(!empty($primary)) $sql .= ' ,primary key ( '.implode(',',$primary).' ) '.PHP_EOL;
		$sql .= ' ) engine = InnoDB character set utf8 collate utf8_general_ci;'.PHP_EOL;
		return $sql;
	}
	public function exists_table_sql(\org\rhaco\store\db\Dao $dao){
		$dbc = \org\rhaco\store\db\Dao::connection(get_class($dao));
		return sprintf('select count(*) from information_schema.tables where table_name=\'%s\' and table_schema=\'%s\'',$dao->table(),$dbc->name());
	}
}