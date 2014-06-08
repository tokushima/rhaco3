<?php
namespace org\rhaco\store\db;
/**
 * DBデータイテレータ
 * @author tokushima
 */
class StatementIterator implements \Iterator{
	private $dao;
	private $statement;
	private $resultset;
	private $resultset_counter;

	public function __construct(Dao $dao,\PDOStatement $statement){
		$this->dao = $dao;
		$this->statement = $statement;
	}
	public function rewind(){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
		$this->resultset_counter = 0;
	}
	public function current(){
		$obj = clone($this->dao);
		$obj->parse_resultset($this->resultset);
		return $obj;
	}
	public function key(){
		return $this->resultset_counter++;
	}
	public function valid(){
		return ($this->resultset !== false);
	}
	public function next(){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
}
