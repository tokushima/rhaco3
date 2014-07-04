<?php
namespace org\rhaco\store\db\module;
use \org\rhaco\store\db\Dao;
/**
 * SQLiteモジュール
 * @author tokushima
 */
class Sqlite extends Base{
	protected $order_random_str = 'random()';

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
		if(!extension_loaded('pdo_sqlite')) throw new \RuntimeException('pdo_sqlite not supported');
		$con = $path = null;
		
		if(empty($host)){
			$host = \org\rhaco\Conf::get('host');
			if(empty($host)){
				$host = empty($name) ? ':memory:' : getcwd();
			}
		}
		if($host != ':memory:'){
			$host = str_replace('\\','/',$host);
			if(substr($host,-1) != '/') $host = $host.'/';
			$path = \org\rhaco\net\Path::absolute($host,$name);
			\org\rhaco\io\File::mkdir(dirname($path));
		}
		try{
			$con = new \PDO(sprintf('sqlite:%s',($host == ':memory:') ? ':memory:' : $host.$name));
		}catch(\PDOException $e){
			throw new \org\rhaco\store\db\exception\ConnectionException($e->getMessage());
		}
		return $con;
	}
	/**
	 * @module org.rhaco.store.db.Dbc
	 * (non-PHPdoc)
	 * @see org\rhaco\store\db\module.Base::last_insert_id_sql()
	 */
	public function last_insert_id_sql(){
		return new \org\rhaco\store\db\Daq('select last_insert_rowid() as last_insert_id;');
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
				case 'alnum':
				case 'text':
					return $quote($name).' TEXT';
				case 'number':
					return $quote($name).' REAL';
				case 'serial': return $quote($name).' INTEGER PRIMARY KEY AUTOINCREMENT';
				case 'boolean':
				case 'timestamp':
				case 'date':
				case 'time':
				case 'intdate': 
				case 'integer': return $quote($name).' INTEGER';
				case 'email':
				case 'choice': return $quote($name).' TEXT';
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
		$sql .= ' );'.PHP_EOL;
		return $sql;
	}
	public function exists_table_sql(\org\rhaco\store\db\Dao $dao){
		return sprintf('select count(*) from sqlite_master where type=\'table\' and name=\'%s\'',$dao->table());
	}
	protected function create_table_prop_cond(\org\rhaco\store\db\Dao $dao,$prop_name){
		return ($dao->prop_anon($prop_name,'extra') !== true && $dao->prop_anon($prop_name,'cond') === null);
	}
	protected function column_value(Dao $dao,$name,$value){
		if($value === null) return null;
		try{
			switch($dao->prop_anon($name,'type')){
				case 'boolean': return (int)$value;
			}
		}catch(\Exception $e){}
		return parent::column_value($dao, $name, $value);
	}
}
