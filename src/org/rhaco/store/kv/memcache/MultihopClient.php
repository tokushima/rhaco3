<?php
namespace org\rhaco\store\kv\memcache;
/**
 * 複数のmemcachedを利用する
 * @author tokushima
 */
class MultihopClient{
	static private $servers = array();
	static private $sockets = array();
	
	/**
	 * サーバを設定する
	 * @param $server
	 */
	static public function set_server($server){
		$args = func_get_args();
		foreach($args as $server){
			if(strpos($server,":") === false) $server = $server.":11211";
			if(!in_array($server,self::$servers)) self::$servers[] = $server;
		}
	}
	static private function connection($server){
		if(!isset(self::$sockets[$server])) self::$sockets[$server] = new Client($server);
		return self::$sockets[$server];
	}	
	/**
	 * 読み取る
	 * @param $key
	 * @return unknown_type
	 */
	static public function read($key){
		$servers = self::$servers;
		while(!empty($servers)){
			try{
				$server = array_rand($servers,1);
				unset($servers[$server]);
				return self::connection(self::$servers[$server])->read($key);
			}catch(\RuntimeException $e){}
		}
		throw new \RuntimeException("not found key ".$key);
	}
	/**
	 * 書き込む
	 * @param $key
	 * @param $value
	 * @param $expiry_time
	 * @return boolean
	 */
	static public function write($key,$value,$expiry_time=0){
		return self::connection(self::$servers[array_rand(self::$servers,1)])->write($key,$value,$expiry_time);
	}
	/**
	 * 削除する
	 * @param $key
	 */
	static public function delete($key){
		foreach(self::$servers as $server) self::connection($server)->delete($key);
	}
}
