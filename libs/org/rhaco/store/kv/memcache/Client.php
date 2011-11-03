<?php
namespace org\rhaco\store\kv\memcache;
/**
 * 単一memcachedを利用する
 * @author tokushima kazutaka
 */
class Client{
	private $resources = array();
	private $resource;

	/**
	 * サーバを指定して接続する
	 * @param $server server_name:port
	 */
	public function __construct($server){
		$port = 11211;
		if(strpos($server,":") !== false) list($server,$port) = explode(":",$server,2);
		$this->resource = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		$this->servers[$server.":".$port] = true;
		if(!socket_connect($this->resource,$server,$port)) throw new \RuntimeException("connection fail ",$server.":".$port);
	}
	/**
	 * 接続を閉じる
	 */
	public function close(){
		if($this->resource !== null){
			socket_close($this->resource);
			$this->resource = null;
		}
	}
	/**
	 * 統計情報を返す
	 * @return array
	 */
	public function stats_slabs(){
		$results = array();
		$this->socket_write("stats slabs");
		$stat = $this->socket_read("END");
		$stat = substr($stat,0,-7);
		if($stat === false) throw new \RuntimeException("");
		if(preg_match_all("/STAT (\d):(.+) (\d+)/",$stat,$matchs)){
			foreach($matchs[0] as $key => $value) $results[$matchs[1][$key]][$matchs[2][$key]] = $matchs[3][$key];
		}
		return $results;
	}
	/**
	 * キーの一覧を消す
	 * @return array
	 */
	public function stats_items(){
		$results = array();
		foreach(array_keys($this->stats_slabs()) as $key){
			$this->socket_write("stats cachedump ".$key." 10000");
			$stat = $this->socket_read("END");
			$stat = substr($stat,0,-7);
			if($stat !== false && preg_match_all("/ITEM ([^\s]+) /",$stat,$matchs)) $results = array_merge($results,$matchs[1]);
		}
		return $results;
	}
	/**
	 * すべて削除する
	 */
	public function clear(){
		foreach($this->stats_items() as $key) $this->delete($key);
	}
	/**
	 * 読みとる
	 * @param $name
	 * @return unknown_type
	 */
	public function read($name){
		$this->socket_write("get ".$name);
		$result = $this->socket_read("END");
		list($head,$result) = explode("\r\n",$result,2);
		if($head === "END") throw new \RuntimeException("not found key ".$name);
		list(,,$flag,$length) = explode(" ",$head);
		$result = substr($result,0,-7);
		if($flag == 1) $result = unserialize(gzuncompress($result));
		return $result;
	}
	/**
	 * 書き込む
	 * @param $name
	 * @param $value
	 * @param $expiry_time
	 * @return boolean
	 */
	public function write($name,$value,$expiry_time=0){
		$flag = 0;
		if(!is_scalar($value)){
			$flag = 1;
			$value = gzcompress(serialize($value));
		}
		$this->socket_write(sprintf("set %s %d %d %d",$name,$flag,$expiry_time,strlen($value)),$value);
		return ("STORED" === trim($this->socket_read("STORED")));
	}
	/**
	 * 削除する
	 * @param $name
	 * @return boolean
	 */
	public function delete($name){
		$this->socket_write("delete ".$name);
		try{
			return ("DELETED" === trim($this->socket_read("DELETED")));
		}catch(\RuntimeException $e){}
		return false;
	}
	private function socket_write($cmd){
		if($this->resource === null) throw new \RuntimeException("connection fail");
		$args = func_get_args();
		$cmd = implode("\r\n",$args)."\r\n";
		$cmd_len = strlen($cmd);
		$offset = 0;

		while($offset < $cmd_len){
			$result = socket_write($this->resource,substr($cmd,$offset,1024),1024);
			if($result === false) throw new \RuntimeException("write socket fail");
			$offset += $result;
		}
	}
	private function socket_read($end){
		if($this->resource === null) throw new \RuntimeException("connection fail");
		$args = func_get_args();
		array_shift($args);
		$results = null;
		$end = $end."\r\n";
		$end_m = "\r\n".$end;
		$end_m_len = strlen($end_m) * -1;

		while($line = socket_read($this->resource,1024,PHP_BINARY_READ)){
			if($line === false) throw new \RuntimeException("Failed to read from socket");
			$results .= $line;
			if($results === "NOT_FOUND\r\n") throw new \RuntimeException("not found");			
			if($results === "ERROR\r\n") throw new \RuntimeException("error");
			if($results === $end || substr($results,$end_m_len) === $end_m) break;
		}
		return $results;
	}
	public function __destruct(){
		$this->close();
	}
}
