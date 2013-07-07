<?php
namespace org\rhaco\store\db;
use org\rhaco\store\db\Daq;
use \org\rhaco\Conf;
use \org\rhaco\Paginator;
use \org\rhaco\store\db\Q;
use \org\rhaco\store\db\exception\DaoException;
use \org\rhaco\store\db\exception\LengthDaoException;
use \org\rhaco\store\db\exception\NotfoundDaoException;
use \org\rhaco\store\db\exception\RequiredDaoException;
use \org\rhaco\store\db\exception\UniqueDaoException;
use \org\rhaco\store\db\exception\DaoBadMethodCallException;
use \org\rhaco\store\db\exception\DaoConnectionException;
use \org\rhaco\store\db\exception\InvalidArgumentException;
use \org\rhaco\store\db\exception\NoRowsAffectedException;
/**
 * O/R Mapper
 * @author tokushima
 * @conf string $future_date auto_future_addで定義される日付、未定義の場合は`2038/01/01 00:00:00`
 * @conf mixed{} $connection 接続設定
 */
abstract class Dao extends \org\rhaco\Object{
	static private $_dao_ = array();
	static private $_cnt_ = 0;	

	private $_has_hierarchy_ = 1;	
	private $_class_id_;
	private $_hierarchy_;

	static private $_co_anon_ = array();
	static private $_connections_ = array();

	/**
	 * コネクション一覧
	 * @return org.rhaco.store.db.Dbc[]
	 */
	final static public function connections(){
		$connections = array();
		foreach(self::$_connections_ as $n => $con){
			$connections[$n] = $con;
		}
		return $connections;
	}
	final static private function connection($class){
		if(!isset(self::$_connections_[self::$_co_anon_[$class][0]])){
			throw new DaoException('unable to connect to '.$class);
		}
		return self::$_connections_[self::$_co_anon_[$class][0]];
	}
	/**
	 * すべての接続でロールバックする
	 */
	final static public function rollback_all(){
		foreach(self::connections() as $con) $con->rollback();
	}
	/**
	 * すべての接続でコミットする
	 */
	final static public function commit_all(){
		foreach(self::connections() as $con) $con->commit();
	}
	final static private function get_con($database,$class){
		$def = Conf::get('connection');
		if(!isset(self::$_connections_[$database])){
			try{
				if(is_array($def[$database])){
					if(isset($def[$database]['con'])){
						self::get_con($def[$database]['con'],$class);
						self::$_connections_[$database] = self::$_connections_[$def[$database]['con']];
						return $def[$database];
					}
					self::$_connections_[$database] = new Dbc($def[$database]);
				}
			}catch(\Exception $e){
				throw new DaoException($class.'('.$database.'): '.$e->getMessage());
			}
		}
		return $def[$database];
	}
	final protected function __new__(){
		if(func_num_args() == 1){
			foreach(func_get_arg(0) as $n => $v){
				switch($n){
					case '_has_hierarchy_':
					case '_class_id_':
					case '_hierarchy_':
						$this->{$n} = $v;
						break;
					default:
				}
			}
		}
		$p = get_class($this);
		if(!isset($this->_class_id_)) $this->_class_id_ = $p;		
		if(isset(self::$_dao_[$this->_class_id_])){
			foreach(self::$_dao_[$this->_class_id_]->_has_dao_ as $name => $dao) $this->{$name}($dao);
			return;
		}
		if(!isset(self::$_co_anon_[$p])){
			$anon = array(static::anon('database')
							,static::anon('table')
							,(static::anon('create',true) === true)
							,(static::anon('update',true) === true)
							,(static::anon('delete',true) === true)
							,null,false,false
						);
			if(empty($anon[0])){
				$conf = explode("\\",$p);
				$def = Conf::get('connection');
				while(!isset($def[implode('.',$conf)]) && !empty($conf)) array_pop($conf);
				if(empty($conf) && !isset($def['*'])) throw new DaoConnectionException('could not find the connection settings `'.$p.'`');
				$anon[0] = empty($conf) ? '*' : implode('.',$conf);
			}
			if(empty($anon[1])){
				$table_class = $p;
				$parent_class = get_parent_class($p);
				$ref = new \ReflectionClass($parent_class);
				while(true){
					$ref = new \ReflectionClass($parent_class);
					if(__CLASS__ == $parent_class || $ref->isAbstract()) break;
					$table_class = $parent_class;
					$parent_class = get_parent_class($parent_class);
				}
				$table_class = preg_replace("/^.*\\\\(.+)$/","\\1",$table_class);
				$anon[1] = strtolower($table_class[0]);
				for($i=1;$i<strlen($table_class);$i++) $anon[1] .= (ctype_lower($table_class[$i])) ? $table_class[$i] : '_'.strtolower($table_class[$i]);
			}
			$config = self::get_con($anon[0],$p);
			if(!isset(self::$_connections_[$anon[0]])) throw new \RuntimeException('connection fail '.str_replace("\\",'.',get_class($this)));
			static::set_module(self::$_connections_[$anon[0]]->connection_module());
			$anon[5] = isset($config['prefix']) ? $config['prefix'] : '';
			$anon[6] = (isset($config['upper']) && $config['upper'] === true);
			$anon[7] = (isset($config['lower']) && $config['lower'] === true);
			self::$_co_anon_[$p] = $anon;
			self::$_co_anon_[$p][1] = self::set_table_name(self::$_co_anon_[$p][1],$p);
		}		
		$has_hierarchy = (isset($this->_hierarchy_)) ? $this->_hierarchy_ - 1 : $this->_has_hierarchy_;
		$root_table_alias = 't'.self::$_cnt_++;
		$_columns_ = $_self_columns_ = $_where_columns_ = $_conds_ = $_join_conds_ = $_alias_ = $_has_many_conds_ = $_has_dao_ = array();

		foreach(array_keys(get_object_vars($this)) as $name){
			if($name[0] != '_' && $this->prop_anon($name,'extra') !== true){
				$anon_cond = $this->prop_anon($name,'cond');
				$column_type = $this->prop_anon($name,'type');

				$column = new Column();
				$column->name($name);
				$column->column($this->prop_anon($name,'column',$name));
				$column->column_alias('c'.self::$_cnt_++);

				if($anon_cond === null){
					if(ctype_upper($column_type[0]) && class_exists($column_type) && is_subclass_of($column_type,__CLASS__)) throw new DaoException("undef ".$name." annotation 'cond'");
					$column->table($this->table());
					$column->table_alias($root_table_alias);
					$column->primary($this->prop_anon($name,'primary',false));
					$column->auto($column_type === 'serial');
					$_columns_[] = $column;
					$_self_columns_[$name] = $column;
					$_alias_[$column->column_alias()] = $name;
				}else if(false !== strpos($anon_cond,'(')){
					$is_has = (class_exists($column_type) && is_subclass_of($column_type,__CLASS__));
					$is_has_many = ($is_has && $this->prop_anon($name,'attr') === 'a');
					if((!$is_has || $has_hierarchy > 0) && preg_match("/^(.+)\((.*)\)(.*)$/",$anon_cond,$match)){
						list(,$self_var,$conds_string,$has_var) = $match;
						$conds = array();
						$ref_table = $ref_table_alias = null;
						if(!empty($conds_string)){
							foreach(explode(',',$conds_string) as $key => $cond){
								$tcc = explode('.',$cond,3);
								switch(sizeof($tcc)){
									case 1:
										$conds[] = Column::cond_instance($tcc[0],'c'.self::$_cnt_++,$this->table(),$root_table_alias);
										break;
									case 2:
										list($t,$c1) = $tcc;
										$ref_table = self::set_table_name($t,$p);
										$ref_table_alias = 't'.self::$_cnt_++;
										$conds[] = Column::cond_instance($c1,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
										break;
									case 3:
										list($t,$c1,$c2) = $tcc;
										$ref_table = self::set_table_name($t,$p);
										$ref_table_alias = 't'.self::$_cnt_++;
										$conds[] = Column::cond_instance($c1,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
										$conds[] = Column::cond_instance($c2,'c'.self::$_cnt_++,$ref_table,$ref_table_alias);
										break;
									default:
										throw new \LogicException('annotation error : `'.$name.'`');
								}
							}
						}
						if($is_has_many){
							if(empty($has_var)) throw new \LogicException('annotation error : `'.$name.'`');
							$dao = new $column_type(array('_class_id_'=>$p.'___'.self::$_cnt_++));
							$_has_many_conds_[$name] = array($dao,$has_var,$self_var);
						}else{
							$self_db = true;
							if($is_has){
								if(empty($has_var)) throw new \LogicException('annotation error : `'.$name.'`');
								$dao = new $column_type(array('_class_id_'=>($p.'___'.self::$_cnt_++),'_hierarchy_'=>$has_hierarchy));
								$this->{$name}($dao);
								if($dao->table() == $this->table()){
									$_has_dao_[$name] = $dao;
									$_columns_ = array_merge($_columns_,$dao->columns());
									$_conds_ = array_merge($_conds_,$dao->conds());
									$this->prop_anon($name,'has',true,true);
									foreach($dao->columns() as $column) $_alias_[$column->column_alias()] = $name;
									$has_column = $dao->base_column($dao->columns(),$has_var);
									$conds[] = Column::cond_instance($has_column->column(),'c'.self::$_cnt_++,$has_column->table(),$has_column->table_alias());
								}else{
									$_has_many_conds_[$name] = array($dao,$has_var,$self_var);
									$self_db = false;
								}
							}else{
								$column->table($ref_table);
								$column->table_alias($ref_table_alias);
								if(!$this->prop_anon($name,'join',false)) $_columns_[] = $column;
								$_where_columns_[$name] = $column;
								$_alias_[$column->column_alias()] = $name;
							}
							if($self_db){
								array_unshift($conds,Column::cond_instance($self_var,'c'.self::$_cnt_++,$this->table(),$root_table_alias));
								if(sizeof($conds) % 2 != 0) throw new DaoException($name.'['.$column_type.'] is illegal condition');
								if($this->prop_anon($name,'join',false)){
									$this->prop_anon($name,'get',false,true);
									$this->prop_anon($name,'set',false,true);
									for($i=0;$i<sizeof($conds);$i+=2) $_join_conds_[$name][] = array($conds[$i],$conds[$i+1]);
								}else{
									for($i=0;$i<sizeof($conds);$i+=2) $_conds_[] = array($conds[$i],$conds[$i+1]);
								}
							}
						}
					}
				}else if($anon_cond[0] === '@'){
					$c = $this->base_column($_columns_,substr($anon_cond,1));
					$column->table($c->table());
					$column->table_alias($c->table_alias());
					$_columns_[] = $column;
					$_where_columns_[$name] = $column;
					$_alias_[$column->column_alias()] = $name;
				}
			}
		}
		self::$_dao_[$this->_class_id_] = (object)array(
														'_columns_'=>$_columns_,
														'_self_columns_'=>$_self_columns_,
														'_where_columns_'=>$_where_columns_,
														'_conds_'=>$_conds_,
														'_join_conds_'=>$_join_conds_,
														'_alias_'=>$_alias_,
														'_has_dao_'=>$_has_dao_,
														'_has_many_conds_'=>$_has_many_conds_
														);
		
	}
	final static private function set_table_name($name,$class){
		$name = self::$_co_anon_[$class][5].$name;
		if(self::$_co_anon_[$class][6]) $name = strtoupper($name);
		if(self::$_co_anon_[$class][7]) $name = strtolower($name);
		return $name;
	}
	final private function base_column($_columns_,$name){
		foreach($_columns_ as $c){
			if($c->is_base() && $c->name() === $name) return $c;
		}
		throw new DaoException('undef var `'.$name.'`');
	}
	/**
	 * 全てのColumnの一覧を取得する
	 * @return Column[]
	 */
	final public function columns(){
		return self::$_dao_[$this->_class_id_]->_columns_;
	}
	/**
	 * 主のColumnの一覧を取得する
	 * @return Column[]
	 */
	final public function self_columns($all=false){
		if($all) return array_merge(self::$_dao_[$this->_class_id_]->_where_columns_,self::$_dao_[$this->_class_id_]->_self_columns_);
		return self::$_dao_[$this->_class_id_]->_self_columns_;
	}
	/**
	 * primaryのColumnの一覧を取得する
	 * @return Column[]
	 */
	final public function primary_columns(){
		$result = array();
		foreach(self::$_dao_[$this->_class_id_]->_self_columns_ as $column){
			if($column->primary()) $result[$column->name()] = $column;
		}
		return $result;
	}
	/**
	 * 必須の条件を取得する
	 * @return array array(Column,Column)
	 */
	final public function conds(){
		return self::$_dao_[$this->_class_id_]->_conds_;
	}
	/**
	 * join時の条件を取得する
	 * @return array array(Column,Column)
	 */
	final public function join_conds($name){
		return (isset(self::$_dao_[$this->_class_id_]->_join_conds_[$name])) ? self::$_dao_[$this->_class_id_]->_join_conds_[$name] : array();
	}
	/**
	 * 結果配列から値を自身にセットする
	 * @param $resultset array
	 * @return integer
	 */
	final public function parse_resultset($resultset){
		foreach($resultset as $alias => $value){
			if(isset(self::$_dao_[$this->_class_id_]->_alias_[$alias])){
				if(self::$_dao_[$this->_class_id_]->_alias_[$alias] == 'ref1') $this->prop_anon(self::$_dao_[$this->_class_id_]->_alias_[$alias],'has',true);

				if($this->prop_anon(self::$_dao_[$this->_class_id_]->_alias_[$alias],'has') === true){
					$this->{self::$_dao_[$this->_class_id_]->_alias_[$alias]}()->parse_resultset(array($alias=>$value));
				}else{
					$this->{self::$_dao_[$this->_class_id_]->_alias_[$alias]}($value);
				}
			}
		}
		if(!empty(self::$_dao_[$this->_class_id_]->_has_many_conds_)){
			foreach(self::$_dao_[$this->_class_id_]->_has_many_conds_ as $name => $conds){
				foreach($conds[0]::find(Q::eq($conds[1],$this->{$conds[2]}())) as $dao) $this->{$name}($dao);
			}
		}
	}
	/**
	 * テーブル名を取得
	 * @return string
	 */
	final public function table(){
		return self::$_co_anon_[get_class($this)][1];
	}
	protected function __find_conds__(){
		return Q::b();
	}
	protected function __save_verify__(){}
	protected function __create_verify__(){}
	protected function __update_verify__(){}
	protected function __delete_verify__(){}
	protected function __before_save__(){}
	protected function __after_save__(){}
	protected function __before_create__(){}
	protected function __after_create__(){}
	protected function __before_update__(){}
	protected function __after_update__(){}
	protected function __after_delete__(){}
	protected function __before_delete__(){}

	static private $recording_query = false;
	static private $record_query = array();
	/**
	 * 発行したSQLの記録を開始する
	 */
	final static public function start_record(){
		$query = self::$record_query;
		self::$recording_query = true;
		self::$record_query = array();
		return $query;
	}
	/**
	 * 発行したSQLの記録を終了する
	 */
	final static public function stop_record(){
		self::$recording_query = false;
		return self::$record_query;	
	}
	/**
	 * 記録したSQLを取得する
	 * @return array
	 */
	final static public function recorded_query(){
		return self::$record_query;
	}
	final private function query(Daq $daq){
		if(self::$recording_query) self::$record_query[] = array($daq->sql(),$daq->ar_vars());
		$statement = self::connection(get_class($this))->prepare($daq->sql());
		if($statement === false) throw new DaoException('prepare fail: '.$daq->sql());
		$statement->execute($daq->ar_vars());
		return $statement;
	}
	final private function update_query(Daq $daq){
		$statement = $this->query($daq);
		$errors = $statement->errorInfo();
		if(isset($errors[1])){
			static::rollback();
			throw new DaoException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').PHP_EOL.'( '.$daq->sql().' )');
		}
		return $statement->rowCount();
	}
	final private function func_query(Daq $daq,$is_list=false){
		$statement = $this->query($daq);
		$errors = $statement->errorInfo();
		if(isset($errors[1])){
			throw new DaoException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').PHP_EOL.'( '.$daq->sql().' )');
		}
		if($statement->columnCount() == 0) return ($is_list) ? array() : null;
		return ($is_list) ? $statement->fetchAll(\PDO::FETCH_ASSOC) : $statement->fetchAll(\PDO::FETCH_COLUMN,0);
	}
	final private function save_verify_primary_unique(){
		$q = new Q();
		$primary = false;
		foreach($this->primary_columns() as $column){
			$value = $this->{$column->name()}();
			if($this->prop_anon($column->name(),'type') === 'serial'){
				$primary = false;
				break;
			}
			$q->add(Q::eq($column->name(),$value));
			$primary = true;
		}
		if($primary && static::find_count($q) > 0){
			throw new UniqueDaoException('duplicate entry',$this);
		}
	}
	/**
	 * 値の妥当性チェックを行う
	 */
	final public function validate(){
		$err = array();
		foreach($this->self_columns() as $name => $column){
			$value = $this->{$name}();
			$label = $this->prop_anon($name,'label',$name);
			$e_require = false;

			if($this->prop_anon($name,'require') === true && ($value === '' || $value === null)){
				$err[] = new RequiredDaoException($label.' required',$name);
				$e_require = true;
			}
			$unique_together = $this->prop_anon($name,'unique_together');
			if($value !== '' && $value !== null && ($this->prop_anon($name,'unique') === true || !empty($unique_together))){
				$unique = $this->prop_anon($name,'unique');
				$uvalue = $value;
				$q = array(Q::eq($name,$uvalue));
				if(!empty($unique_together)){
					foreach((is_array($unique_together) ? $unique_together : array($unique_together)) as $c){
						$q[] = Q::eq($c,$this->{$c}());
					}
				}
				foreach($this->primary_columns() as $column){
					if(null !== ($pv = $this->{$column->name()})) $q[] = Q::neq($column->name(),$this->{$column->name()});
				}
				if(0 < call_user_func_array(array(get_class($this),'find_count'),$q)) $err[] = new UniqueDaoException($label.' unique',$name);
			}
			$master = $this->prop_anon($name,'master');
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				if($master[0] !== "\\") $master = "\\".$master;
				try{
					$r = new \ReflectionClass($master);
				}catch(\ReflectionException $e){
					$self = new \ReflectionClass(get_class($this));
					$r = new \ReflectionClass("\\".$self->getNamespaceName().$master);
				}
				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				if(empty($primarys) || 0 === call_user_func_array(array($mo,'find_count'),array(Q::eq(key($primarys),$this->{$name})))) $err[] = new NotfoundDaoException($label.' master not found',$name);
			}
			if(!$e_require && $value !== null){
				switch($this->prop_anon($name,'type')){
					case 'number':
					case 'integer':
						if($this->prop_anon($name,'min') !== null && (float)$this->prop_anon($name,'min') > $value) $err[] = new LengthDaoException($label.' less than minimum',$name);
						if($this->prop_anon($name,'max') !== null && (float)$this->prop_anon($name,'max') < $value) $err[] = new LengthDaoException($label.' exceeds maximum',$name);
						break;
					case 'text':
					case 'string':
					case 'alnum':
						if($this->prop_anon($name,'min') !== null && (int)$this->prop_anon($name,'min') > mb_strlen($value)) $err[] = new LengthDaoException($label.' less than minimum',$name);
						if($this->prop_anon($name,'max') !== null && (int)$this->prop_anon($name,'max') < mb_strlen($value)) $err[] = new LengthDaoException($label.' exceeds maximum',$name);
						break;
				}
			}
			if($this->{'verify_'.$column->name()}() === false){
				$err[] = new DaoException($this->prop_anon($column->name(),'label').' verify fail',$column->name());
			}
		}
		if(!empty($err)){
			$msg = count($err).' exceptions: ';
			foreach($err as $e){
				$msg .= PHP_EOL.' '.$e->getMessage();
				\org\rhaco\Exceptions::add($e);
			}
			throw new \org\rhaco\store\db\exception\DaoExceptions($msg);
		}
	}
	final private function which_aggregator($exe,array $args,$is_list=false){
		$target_name = $gorup_name = array();
		if(isset($args[0]) && is_string($args[0])){
			$target_name = array_shift($args);
			if(isset($args[0]) && is_string($args[0])) $gorup_name = array_shift($args);
		}
		$query = new Q();
		if(!empty($args)) call_user_func_array(array($query,'add'),$args);
		$daq = static::module($exe.'_sql',$this,$target_name,$gorup_name,$query);
		return $this->func_query($daq,$is_list);
	}
	final static private function exec_aggregator_result_cast($dao,$target_name,$value,$cast){
		switch($cast){
			case 'float': return (float)$value;
			case 'integer': return (int)$value;
		}
		$dao->{$target_name}($value);
		return $dao->{$target_name}();
	}
	final static private function exec_aggregator($exec,$target_name,$args,$cast=null){
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$result = $dao->which_aggregator($exec,$args);
		return static::exec_aggregator_result_cast($dao,$target_name,current($result),$cast);
	}
	final static private function exec_aggregator_by($exec,$target_name,$gorup_name,$args,$cast=null){
		if(empty($target_name) || !is_string($target_name)) throw new DaoException('undef target_name');
		if(empty($gorup_name) || !is_string($gorup_name)) throw new DaoException('undef group_name');
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$results = array();
		foreach($dao->which_aggregator($exec,$args,true) as $key => $value){
			$dao->{$gorup_name}($value['key_column']);
			$results[$dao->{$gorup_name}()] = static::exec_aggregator_result_cast($dao,$target_name,$value['target_column'],$cast);
		}
		ksort($results);
		return $results;
	}
	/**
	 * カウントを取得する
	 * @paaram string $target_name 対象となるプロパティ
	 * @return integer
	 */
	final static public function find_count($target_name=null){
		$args = func_get_args();
		return (int)static::exec_aggregator('count',$target_name,$args,'integer');
	}
	/**
	 * グルーピングしてカウントを取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * @return integer{}
	 */
	final static public function find_count_by($target_name,$gorup_name){
		$args = func_get_args();
		return static::exec_aggregator_by('count',$target_name,$gorup_name,$args);
	}
	/**
	 * 合計を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @return number
	 */
	final static public function find_sum($target_name){
		$args = func_get_args();
		return static::exec_aggregator('sum',$target_name,$args);
	}
	/**
	 * グルーピングした合計を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * @return integer{}
	 */
	final static public function find_sum_by($target_name,$gorup_name){
		$args = func_get_args();
		return static::exec_aggregator_by('sum',$target_name,$gorup_name,$args);
	}
	/**
	 * 最大値を取得する
	 *
	 * @param string $target_name 対象となるプロパティ
	 * @return number
	 */
	final static public function find_max($target_name){
		$args = func_get_args();
		return static::exec_aggregator('max',$target_name,$args);
	}
	/**
	 * グルーピングして最大値を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * @return number
	 */
	final static public function find_max_by($target_name,$gorup_name){
		$args = func_get_args();
		return static::exec_aggregator_by('max',$target_name,$gorup_name,$args);
	}
	/**
	 * 最小値を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * @return number
	 */
	final static public function find_min($target_name){
		$args = func_get_args();
		return static::exec_aggregator('min',$target_name,$args);
	}
	/**
	 * グルーピングして最小値を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * return integer{}
	 */
	final static public function find_min_by($target_name,$gorup_name){
		$args = func_get_args();
		return static::exec_aggregator_by('min',$target_name,$gorup_name,$args);
	}
	/**
	 * 平均を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @return number
	 */
	final static public function find_avg($target_name){
		$args = func_get_args();
		return static::exec_aggregator('avg',$target_name,$args,'float');
	}
	/**
	 * グルーピングして平均を取得する
	 * @param string $target_name 対象となるプロパティ
	 * @param string $gorup_name グルーピングするプロパティ名
	 * @return number{}
	 */
	final static public function find_avg_by($target_name,$gorup_name){
		$args = func_get_args();
		return static::exec_aggregator_by('avg',$target_name,$gorup_name,$args,'float');
	}
	/**
	 * distinctした一覧を取得する
	 *
	 * @param string $target_name 対象となるプロパティ
	 * @return mixed[]
	 */
	final static public function find_distinct($target_name){
		$args = func_get_args();
		$dao = new static();
		$args[] = $dao->__find_conds__();
		$results = $dao->which_aggregator('distinct',$args);
		return $results;
	}
	/**
	 * 検索結果をひとつ取得する
	 * @return $this
	 */
	final static public function find_get(){
		$args = func_get_args();
		$dao = new static();
		$query = new Q();
		$query->add($dao->__find_conds__());
		$query->add(new Paginator(1,1));
		if(!empty($args)) call_user_func_array(array($query,'add'),$args);
		foreach(self::get_statement_iterator($dao,$query) as $d) return $d;
		throw new NotfoundDaoException('{S} not found',$dao);
	}
	/**
	 * サブクエリを取得する
	 * @param $name 対象のプロパティ
	 * @return Daq
	 */
	final static public function find_sub($name){
		$args = func_get_args();
		array_shift($args);
		$dao = new static();
		$query = new Q();
		$query->add($dao->__find_conds__());

		if(!empty($args)) call_user_func_array(array($query,'add'),$args);
		if(!$query->is_order_by()) $query->order($name);
		$paginator = $query->paginator();
		if($paginator instanceof Paginator){
			if($query->is_order_by()) $paginator->order($query->in_order_by(0)->ar_arg1(),$query->in_order_by(0)->type() == Q::ORDER_ASC);
			$paginator->total(call_user_func_array(array(get_called_class(),'find_count'),$args));
			if($paginator->total() == 0) return array();
		}
		/**
		 * SELECT文の生成
		 * @param self $dao
		 * @param org.rhaco.store.db.Q $query
		 * @param org.rhaco.Paginator $paginator
		 * @param string $name
		 * @return org.rhaco.store.db.Daq
		 */
		return static::module('select_sql',$dao,$query,$paginator,$name);
	}
	final static private function get_statement_iterator($dao,$query){
		if(!$query->is_order_by()){
			foreach($dao->primary_columns() as $column) $query->order($column->name());
		}
		/**
		 * SELECT文の生成
		 * @param self $dao
		 * @param org.rhaco.store.db.Q $query
		 * @param org.rhaco.Paginator $paginator
		 * @param string $name
		 * @return org.rhaco.store.db.Daq
		 */
		$daq = static::module('select_sql',$dao,$query,$query->paginator());
		$statement = $dao->query($daq);
		$errors = $statement->errorInfo();
		if(isset($errors[1])){
			throw new DaoException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : ''));
		}
		return new StatementIterator($dao,$statement);
	}
	/**
	 * 検索を実行する
	 * @return StatementIterator
	 */
	final static public function find(){
		$args = func_get_args();
		$dao = new static();
		$query = new Q();
		$query->add($dao->__find_conds__());
		if(!empty($args)) call_user_func_array(array($query,'add'),$args);
		
		$paginator = $query->paginator();
		if($paginator instanceof Paginator){
			if($query->is_order_by()) $paginator->order($query->in_order_by(0)->ar_arg1(),$query->in_order_by(0)->type() == Q::ORDER_ASC);
			$paginator->total(call_user_func_array(array(get_called_class(),'find_count'),$args));
			if($paginator->total() == 0) return array();
		}
		return static::get_statement_iterator($dao,$query);
	}
	/**
	 * 検索結果をすべて取得する
	 * @return self[]
	 */
	final static public function find_all(){
		$args = func_get_args();
		$result = array();
		foreach(call_user_func_array(array(get_called_class(),'find'),$args) as $p) $result[] = $p;
		return $result;
	}
	/**
	 * コミットする
	 */
	final static public function commit(){
		self::connection(get_called_class())->commit();
	}
	/**
	 * ロールバックする
	 */
	final static public function rollback(){
		self::connection(get_called_class())->rollback();
	}
	/**
	 * 条件により削除する
	 * before/after/verifyは実行されない
	 * @return integer 実行した件数
	 */
	final static public function find_delete(){
		$args = func_get_args();
		$dao = new static();
		if(!self::$_co_anon_[get_class($dao)][4]) throw new DaoBadMethodCallException('delete is not permitted');
		$query = new Q();
		if(!empty($args)) call_user_func_array(array($query,'add'),$args);
		/**
		 * delete文の生成
		 * @param self $this
		 */
		$daq = static::module('find_delete_sql',$dao,$query);
		return $dao->update_query($daq);
	}
	/**
	 * DBから削除する
	 */
	final public function delete(){
		if(!self::$_co_anon_[get_class($this)][4]) throw new DaoBadMethodCallException('delete is not permitted');
		$this->__before_delete__();
		$this->__delete_verify__();
		/**
		 * delete文の生成
		 * @param self $this
		 */
		$daq = static::module('delete_sql',$this);
		if($this->update_query($daq) == 0) throw new NotfoundDaoException('delete failed');
		$this->__after_delete__();
	}
	/**
	 * 指定のプロパティにユニークコードをセットする
	 * @param string $prop_name
	 * @param integer $size
	 * @return string 生成されたユニークコード
	 */
	final public function set_unique_code($prop_name,$size=null){
		$code = '';
		$max = (!empty($size)) ? $size : $this->prop_anon($prop_name,'max',32);
		$ctype = $this->prop_anon($prop_name,'ctype','alnum');
		if($ctype != 'alnum' && $ctype != 'alpha' && $ctype != 'digit') throw new \LogicException('unexpected ctype');
		$char = '';
		if($ctype == 'alnum' || $ctype == 'digit') $char .= '0123456789';
		if($ctype != 'digit'){
		 	if($this->prop_anon($prop_name,'upper',false) === true) $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		 	if($this->prop_anon($prop_name,'lower',true) === true) $char .= 'abcdefghijklmnopqrstuvwxyz';
		}
		$charl = strlen($char) - 1;
		$ignores = $this->prop_anon($prop_name,'ignore_auto_code');
		if(!is_array($ignores)) $ignores = array($ignores);
		while($code == '' || static::find_count(Q::eq($prop_name,$code)) > 0){
			for($code='',$i=0;$i<$max;$i++) $code .= $char[mt_rand(0,$charl)];
			if(!empty($ignores)){
				foreach($ignores as $ignore){
					if(preg_match('/^'.$ignore.'$/',$code)) $code = '';
				}
			}
		}
		$this->{$prop_name}($code);
		return $code;
	}
	/**
	 * DBへ保存する
	 */
	final public function save(){
		$q = new Q();
		$new = false;
		foreach($this->primary_columns() as $column){
			$value = $this->{$column->name()}();
			if($this->prop_anon($column->name(),'type') === 'serial' && empty($value)){
				$new = true;
				break;
			}
			$q->add(Q::eq($column->name(),$value));
		}
		$self = get_class($this);
		if(!$new && $self::find_count($q) === 0) $new = true;
		foreach($this->self_columns() as $column){
			if($this->prop_anon($column->name(),'auto_now') === true){
				switch($this->prop_anon($column->name(),'type')){
					case 'timestamp':
					case 'date': $this->{$column->name()}(time()); break;
					case 'intdate': $this->{$column->name()}(date('Ymd')); break;
				}
			}else if($new && ($this->{$column->name()}() === null || $this->{$column->name()}() === '')){
				if($this->prop_anon($column->name(),'type') == 'string' && $this->prop_anon($column->name(),'auto_code_add') === true){
					$this->set_unique_code($column->name());
				}else if($this->prop_anon($column->name(),'auto_now_add') === true){
					switch($this->prop_anon($column->name(),'type')){
						case 'timestamp':
						case 'date': $this->{$column->name()}(time()); break;
						case 'intdate': $this->{$column->name()}(date('Ymd')); break;
					}
				}else if($this->prop_anon($column->name(),'auto_future_add') === true){
					$future = Conf::get('future_date','2038/01/01 00:00:00');
					$time = strtotime($future);
					switch($this->prop_anon($column->name(),'type')){
						case 'timestamp':
						case 'date':
							$this->{$column->name()}($time);
							break;
						case 'intdate': $this->{$column->name()}(date('Ymd',$time)); break;
					}
				}
			}
		}
		if($new){
			if(!self::$_co_anon_[$self][2]) throw new DaoBadMethodCallException('create save is not permitted');
			$this->__before_save__();
			$this->__before_create__();
			$this->save_verify_primary_unique();
			$this->__create_verify__();
			$this->__save_verify__();
			$this->validate();
			/**
			 * createを実行するSQL文の生成
			 * @param self $this
			 * @return org.rhaco.store.db.Daq
			 */
			$daq = $self::module('create_sql',$this);
			if($this->update_query($daq) == 0) throw new DaoException('create failed');
			if($daq->is_id()){
				/**
				 * AUTOINCREMENTの値を取得するSQL文の生成
				 * @param self $this
				 * @return integer
				 */
				$result = $this->func_query(static::module('last_insert_id_sql',$this));
				if(empty($result)) throw new DaoException('create failed');
				$this->{$daq->id()}($result[0]);
			}
			$this->__after_create__();
			$this->__after_save__();
		}else{
			if(!self::$_co_anon_[$self][3]) throw new DaoBadMethodCallException('update save is not permitted');
			$this->__before_save__();
			$this->__before_update__();
			$this->__update_verify__();
			$this->__save_verify__();
			$this->validate();
			$args = func_get_args();
			$query = new Q();
			if(!empty($args)) call_user_func_array(array($query,'add'),$args);
			/**
			 * updateを実行するSQL文の生成
			 * @param self $this
			 * @return Daq
			 */
			$daq = $self::module('update_sql',$this,$query);
			$affected_rows = $this->update_query($daq);
			if($affected_rows === 0 && !empty($args)) throw new NoRowsAffectedException();
			$this->__after_update__();
			$this->__after_save__();
		}
		return $this;
	}
	/**
	 * DBの値と同じにする
	 * @return $this
	 */
	final public function sync(){
		$query = new Q();
		$query->add(new Paginator(1,1));
		foreach($this->primary_columns() as $column) $query->add(Q::eq($column->name(),$this->{$column->name()}()));
		foreach(self::get_statement_iterator($this,$query) as $dao){
			foreach(get_object_vars($dao) as $k => $v){
				if($k[0] != '_') $this->{$k}($v);
			}
			return $this;
		}
		throw new NotfoundDaoException('{S} synchronization failed',$this);
	}
	/**
	 * (non-PHPdoc)
	 * @see org\rhaco.Object::set_prop()
	 */
	protected function set_prop($name,$type,$value){
		try{
			return parent::set_prop($name,$type,$value);
		}catch(\InvalidArgumentException $e){
			throw new InvalidArgumentException($e->getMessage(),$name);
		}
	}
	/**
	 * 配列からプロパティに値をセットする
	 * @param mixed{} $arg
	 * @return $this
	 */
	public function set_props($arg){
		if(isset($arg) && (is_array($arg) || (is_object($arg) && ($arg instanceof \Traversable)))){
			$vars = get_object_vars($this);
			$err = array();
			foreach($arg as $name => $value){
				if($name[0] != '_' && array_key_exists($name,$vars)){
					try{
						$this->{$name}($value);
					}catch(\Exception $e){
						$err[] = array($e,$name);
					}
				}
			}
			if(!empty($err)){
				foreach($err as $e) \org\rhaco\Exceptions::add($e[0],$e[1]);
				\org\rhaco\Exceptions::throw_over();
			}
		}
		return $this;
	}
	protected function ___verify___(){
		return true;
	}
	/**
	 * テーブルの作成
	 * @throws RuntimeException
	 */
	static public function create_table(){
		$dao = new static();
		$daq = new \org\rhaco\store\db\Daq(static::module('exists_table_sql',$dao));
		$count = current($dao->func_query($daq));
		if($count == 0){
			$daq = new \org\rhaco\store\db\Daq(static::module('create_table_sql',$dao));
			$dao->func_query($daq);
		}
	}
}
