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
	/**
	 * create table
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
				case 'serial': return $quote($name).' INTEGER PRIMARY KEY';
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
		foreach($dao->props() as $prop_name => $v){
			if($this->create_table_prop_cond($dao,$prop_name)){
				$column_str = '  '.$to_column_type($dao,$dao->prop_anon($prop_name,'type'),$prop_name);
				$column_str .= (($dao->prop_anon($prop_name,'require') === true) ? ' not' : '').' null ';
				
				$columndef[] = $column_str;
				if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial') $primary[] = $quote($prop_name);
			}
		}
		$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
		$sql .= ' );'.PHP_EOL;
		return $sql;
	}
}
