<?php
namespace org\rhaco\store\db\module;
use \org\rhaco\store\db\exception\DaoException;
use \org\rhaco\store\db\Daq;
use \org\rhaco\store\db\Dao;
use \org\rhaco\Paginator;
use \org\rhaco\store\db\Q;
use \org\rhaco\store\db\Column;
/**
 * DB操作モジュールの基底クラス
 * @author tokushima
 */
abstract class Base extends \org\rhaco\Object{
	protected $encode;
	protected $quotation = '`';
	protected $order_random_str = 'rand()';

	protected function __new__($encode=null){
		$this->encode = $encode;
	}
	/**
	 * DBに接続する
	 * @param string $name
	 * @param string $host
	 * @param number $port
	 * @param string $user
	 * @param string $password
	 * @param string $sock
	 * @return PDO
	 */
	public function connect($name,$host,$port,$user,$password,$sock){
		throw new \BadMethodCallException('undef');
	}
	/**
	 * 最後のインサートされたserial値を返す文を生成する
	 * @return Daq
	 */
	public function last_insert_id_sql(){
		throw new \BadMethodCallException('undef');
	}
	/**
	 * insert文を生成する
	 * @param Dao $dao
	 * @return Daq
	 */
	public function create_sql(Dao $dao){
		$insert = $vars = array();
		$autoid = null;
		foreach($dao->self_columns() as $column){
			if($column->auto()) $autoid = $column->name();
			$insert[] = $this->quotation($column->column());
			$vars[] = $this->update_value($dao,$column->name());
		}
		return Daq::get('insert into '.$this->quotation($column->table()).' ('.implode(',',$insert).') values ('.implode(',',array_fill(0,sizeof($insert),'?')).');'
					,$vars
					,$autoid
				);
	}
	/**
	 * update文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @return Daq
	 */
	public function update_sql(Dao $dao,Q $query){
		$where = $update = $wherevars = $updatevars = $from = array();
		foreach($dao->primary_columns() as $column){
			$where[] = $this->quotation($column->column()).' = ?';
			$wherevars[] = $this->update_value($dao,$column->name());
		}
		if(empty($where)) throw new \LogicException('primary not found');
		foreach($dao->self_columns() as $column){
			if(!$column->primary()){
				$update[] = $this->quotation($column->column()).' = ?';
				$updatevars[] = $this->update_value($dao,$column->name());
			}
		}
		$vars = array_merge($updatevars,$wherevars);
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->self_columns(),null,false);
		return Daq::get(
						'update '.$this->quotation($column->table()).' set '.implode(',',$update).' where '.implode(' and ',$where).(empty($where_sql) ? '' : ' and '.$where_sql)
						,array_merge($vars,$where_vars)
					);
	}
	/**
	 * delete文を生成する
	 * @param Dao $dao
	 * @return Daq
	 */
	public function delete_sql(Dao $dao){
		$where = $vars = array();
		foreach($dao->primary_columns() as $column){
			$where[] = $this->quotation($column->column()).' = ?';
			$vars[] = $dao->{$column->name()}();
		}
		if(empty($where)) throw new \LogicException('not primary');
		return Daq::get(
						'delete from '.$this->quotation($column->table()).' where '.implode(' and ',$where)
						,$vars
					);
	}
	/**
	 * delete文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @return Daq
	 */
	public function find_delete_sql(Dao $dao,Q $query){
		$from = array();
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->self_columns(),null,false);
		return Daq::get(
						'delete from '.$this->quotation($dao->table()).(empty($where_sql) ? '' : ' where '.$where_sql)
						,$where_vars
					);
	}
	/**
	 * select文を生成する
	 * @param Dao $dao
	 * @param Q $query
	 * @param Paginator $paginator
	 * @param string $name columnを指定する場合に対象の変数名
	 * @return Daq
	 */
	public function select_sql(Dao $dao,Q $query,$paginator,$name=null){
		$select = $from = array();
		$self_columns = $dao->self_columns();

		if(empty($name)){
			foreach($dao->columns() as $column){
				$select[] = $column->table_alias().'.'.$this->quotation($column->column()).' '.$column->column_alias();
				$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
			}
		}else{
			foreach($dao->columns() as $column){
				if($column->name() == $name){
					$select[] = $column->table_alias().'.'.$this->quotation($column->column()).' '.$column->column_alias();
					$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
					break;
				}
			}
		}
		if(empty($select)) throw new \LogicException('select invalid');
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->self_columns(true),$this->where_cond_columns($dao->conds(),$from));
		return Daq::get(('select '.implode(',',$select).' from '.implode(',',$from)
										.(empty($where_sql) ? '' : ' where '.$where_sql)
										.$this->select_option_sql($paginator,$this->select_order($query,$self_columns))
							)
							,$where_vars
				);
	}
	protected function select_order($query,array $self_columns){
		$order = array();
		if($query->is_order_by_rand()){
			$order[] = $this->order_random_str;
		}else{
			foreach($query->order_by() as $q){
				foreach($q->ar_arg1() as $column_str){
					$order[] = $this->get_column($column_str,$self_columns)->column_alias().(($q->type() == Q::ORDER_ASC) ? ' asc' : ' desc');
				}
			}
		}
		return $order;	
	}
	protected function select_option_sql($paginator,$order){
		return ' '
				.(empty($order) ? '' : ' order by '.implode(',',$order))
				.(($paginator instanceof Paginator) ? sprintf(" limit %d,%d ",$paginator->offset(),$paginator->limit()) : '')
				;
	}
	/**
	 * count文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function count_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('count',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * sum文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function sum_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('sum',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * max文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function max_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('max',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * min文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function min_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('min',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * avg文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function avg_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('avg',$dao,$target_column,$gorup_column,$query);
	}
	/**
	 * distinct文を生成する
	 * @param Dao $dao
	 * @param string $target_column
	 * @param string $gorup_column
	 * @param Q $query
	 * @return Daq
	 */
	public function distinct_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql('distinct',$dao,$target_column,$gorup_column,$query);
	}
	protected function which_aggregator_sql($exe,Dao $dao,$target_name,$gorup_name,Q $query){
		$select = $from = array();
		$target_column = $group_column = null;
		if(empty($target_name)){
			$self_columns = $dao->self_columns();
			$primary_columns = $dao->primary_columns();
			if(!empty($primary_columns)) $target_column = current($primary_columns);
			if(empty($target_column) && !empty($self_columns)) $target_column = current($self_columns);
		}else{
			$target_column = $this->get_column($target_name,$dao->columns());
		}
		if(empty($target_column)) throw new \LogicException('undef primary');
		if(!empty($gorup_name)){
			$group_column = $this->get_column($gorup_name,$dao->columns());
			$select[] = $group_column->table_alias().'.'.$this->quotation($group_column->column()).' key_column';
		}
		foreach($dao->columns() as $column){
			$from[$column->table_alias()] = $column->table().' '.$column->table_alias();
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$from,$query,$dao->self_columns(true),$this->where_cond_columns($dao->conds(),$from));
		return Daq::get(('select '.$exe.'('.$target_column->table_alias().'.'.$this->quotation($target_column->column()).') target_column'
										.(empty($select) ? '' : ','.implode(',',$select))
										.' from '.implode(',',$from)
										.(empty($where_sql) ? '' : ' where '.$where_sql)
										.(empty($group_column) ? '' : ' group by key_column')
									)
							,$where_vars
				);
	}
	protected function where_cond_columns(array $cond_columns,array &$from){
		$conds = array();
		foreach($cond_columns as $name => $columns){
			$conds[] = $columns[0]->table_alias().'.'.$this->quotation($columns[0]->column())
						.' = '
						.$columns[1]->table_alias().'.'.$this->quotation($columns[1]->column());
			$from[$columns[0]->table_alias()] = $columns[0]->table().' '.$columns[0]->table_alias();
			$from[$columns[1]->table_alias()] = $columns[1]->table().' '.$columns[1]->table_alias();
		}
		return (empty($conds)) ? '' : implode(' and ',$conds);
	}
	protected function where_sql(Dao $dao,&$from,Q $q,array $self_columns,$require_where=null,$alias=true){
		if($q->is_block()){
			$vars = $and_block_sql = $or_block_sql = array();
			$where_sql = '';

			foreach($q->ar_and_block() as $qa){
				list($where,$var) = $this->where_sql($dao,$from,$qa,$self_columns,null,$alias);
				if(!empty($where)){
					$and_block_sql[] = $where;
					$vars = array_merge($vars,$var);
				}
			}
			if(!empty($and_block_sql)) $where_sql .= ' ('.implode(' and ',$and_block_sql).') ';
			foreach($q->ar_or_block() as $or_block){
				list($where,$var) = $this->where_sql($dao,$from,$or_block,$self_columns,null,$alias);
				if(!empty($where)){
					$or_block_sql[] = $where;
					$vars = array_merge($vars,$var);
				}
			}
			if(!empty($or_block_sql)) $where_sql .= (empty($where_sql) ? '' : ' and ').' ('.implode(' or ',$or_block_sql).') ';

			if(empty($where_sql)){
				$where_sql = $require_where;
			}else if(!empty($require_where)){
				$where_sql = '('.$require_where.') and ('.$where_sql.')';
			}
			return array($where_sql,$vars);
		}
		if($q->type() == Q::MATCH){
			$query = new Q();
			foreach($q->ar_arg1() as $cond){
				if(strpos($cond,'=') !== false){
					list($column,$value) = explode('=',$cond);
					$not = (substr($value,0,1) == '!');
					$value = ($not) ? ((strlen($value) > 1) ? substr($value,1) : '') : $value;
					if($value === ''){
						$query->add(($not) ? Q::neq($column,'') : Q::eq($column,''));
					}else{
						$query->add(($not) ? Q::contains($column,$value,$q->param()|Q::NOT) : Q::contains($column,$value,$q->param()));
					}
				}else{
					$columns = array();
					foreach($self_columns as $column) $columns[] = $column->name();
					$query->add(Q::contains(implode(',',$columns),explode(' ',$cond),$q->param()));
				}
			}
			return $this->where_sql($dao,$from,$query,$self_columns,null,$alias);
		}
		$and = $vars = array();
		$arg2 = ($q->arg2() === null) ? array(null) : $q->ar_arg2();
		foreach($arg2 as $base_value){
			$or = array();
			foreach($q->ar_arg1() as $column_str){
				$value = $base_value;
				$column = $this->get_column($column_str,$self_columns);
				$column_alias = $this->column_alias_sql($dao,$column,$q,$alias);
				$is_add_value = true;

				switch($q->type()){
					case Q::EQ:
						if($value === null){
							$is_add_value = false;
							$column_alias .= ' is null'; break;
						}
						$column_alias .= ' = '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::NEQ:
						if($value === null){
							$is_add_value = false;
							$column_alias .= ' is not null'; break;
						}
						$column_alias .= ' <> '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::GT: $column_alias .= ' > '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::GTE: $column_alias .= ' >= '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::LT: $column_alias .= ' < '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::LTE: $column_alias .= ' <= '.(($value instanceof Daq) ? '('.$value->unique_sql().')' : '?'); break;
					case Q::CONTAINS:
					case Q::START_WITH:
					case Q::END_WITH:
						$column_alias = $this->format_column_alias_sql($dao,$column,$q,$alias);
						$column_alias .= ($q->not() ? ' not' : '').' like(?)';
						$value = (($q->type() == Q::CONTAINS || $q->type() == Q::END_WITH) ? '%' : '')
									.$value
									.(($q->type() == Q::CONTAINS || $q->type() == Q::START_WITH) ? '%' : '');
						break;
					case Q::IN:
						$column_alias .= ($q->not() ? ' not' : '')
											.(($value instanceof Daq) ?
												' in('.$value->unique_sql().')' :
												' in('.substr(str_repeat('?,',sizeof($value)),0,-1).')'
											);
						break;
				}
				if($value instanceof Daq){
					$is_add_value = false;
					$vars = array_merge($vars,$value->vars());
				}
				$add_join_conds = $dao->join_conds($column->name());
				if(!empty($add_join_conds)) $column_alias .= ' and '.$this->where_cond_columns($add_join_conds,$from);
				$or[] = $column_alias;

				if($is_add_value){
					if(is_array($value)){
						$values = array();
						foreach($value as $v) $values[] = ($q->ignore_case()) ? strtoupper($this->column_value($dao,$column->name(),$v)) : $this->column_value($dao,$column->name(),$v);
						$vars = array_merge($vars,$values);
					}else{
						$vars[] = ($q->ignore_case()) ? strtoupper($this->column_value($dao,$column->name(),$value)) : $this->column_value($dao,$column->name(),$value);
					}
				}
			}
			$and[] = ' ('.implode(' or ',$or).') ';
		}
		return array(implode(' and ',$and),$vars);
	}
	protected function column_value(Dao $dao,$name,$value){
		if($value === null) return null;
		try{
			switch($dao->prop_anon($name,'type')){
				case 'timestamp': return date('Y/m/d H:i:s',$value);
				case 'date': return date('Y/m/d',$value);
			}
		}catch(\Exception $e){}
		return $value;
	}
	protected function update_value(Dao $dao,$name){
		return $this->column_value($dao,$name,$dao->{$name}());
	}
	protected function get_column($column_str,array $self_columns){
		if(isset($self_columns[$column_str])) return $self_columns[$column_str];
		foreach($self_columns as $c){
			if($c->name() == $column_str) return $c;
		}
		throw new \LogicException('undef '.$column_str);
	}
	protected function column_alias_sql(Dao $dao,Column $column,Q $q,$alias=true){
		$column_str = ($alias) ? $column->table_alias().'.'.$this->quotation($column->column()) : $this->quotation($column->column());
		if($q->ignore_case()) return 'upper('.$column_str.')';
		return $column_str;
	}
	protected function format_column_alias_sql(Dao $dao,Column $column,Q $q,$alias=true){
		return $this->column_alias_sql($dao,$column,$q,$alias);
	}
	protected function quotation($name){
		return $this->quotation.$name.$this->quotation;
	}
	/**
	 * create table
	 * @param org.rhaco.store.db.Dao $dao
	 */
	public function create_table_sql(\org\rhaco\store\db\Dao $dao){
	}
	protected function create_table_prop_cond(\org\rhaco\store\db\Dao $dao,$prop_name){
		return ($dao->prop_anon($prop_name,'extra') !== true && $dao->prop_anon($prop_name,'cond') === null);
	}
}
