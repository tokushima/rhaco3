<?php
namespace org\rhaco\net;
/**
 * Socket
 * @author SHIGETA Takeshiro
 * @author yabeken
 */
class Socket{
	private $address;
	private $port;
	private $timeout = 30;	
	private $_resource_;
	
	public function __construct($address=null,$port=null,$timeout=30){
		$this->address = $address;
		$this->port = $port;
		$this->timeout = $timeout;
	}
	public function __destruct(){
		if($this->is_connected()) $this->close();
	}
	/**
	 * 接続する
	 * @param string $address
	 * @param integer $port
	 * @param integer $timeout
	 * @return boolean
	 */
	public function connect($address=null,$port=null,$timeout=null){
		if($this->is_connected()) throw new \RuntimeException(sprintf("socket has already connected to server [%s:%s]",$this->hotname,$this->port));
		if($address) $this->address = $address;
		if($port) $this->port = $port;
		if($timeout) $this->timeout = $timeout;
		try{
			$this->_resource_ = fsockopen($this->address,$this->port,$errno,$errstr,$this->timeout);
		}catch(\Exception $e){
			\org\rhaco\Log::error($e->getMessage());
		}
		if(!is_resource($this->_resource_)){
			\org\rhaco\Log::error(sprintf("failed to connect server [%s:%s]",$this->address,$this->port));
			return false;
		}
		return true;
	}
	/**
	 * 切断する
	 */
	public function close(){
		if($this->is_connected()) fclose($this->_resource_);
	}
	/**
	 * 接続状態を返す
	 * @return boolean
	 */
	public function is_connected(){
		return is_resource($this->_resource_);
	}
	/**
	 * 終端状態を返す
	 * @return boolean
	 */
	public function is_eof(){
		return feof($this->_resource_);
	}
	/**
	 * 切断状態を返す
	 * @return boolean
	 */
	public function is_closed(){
		return !$this->is_connected();
	}
	/**
	 * 書き込む
	 * @param mixed $data
	 * @return integer 書き込んだバイト数
	 */
	public function write($data){
		for($written = $len = 0; $written < strlen($data); $written += $len){
			$len = fwrite($this->_resource_,substr($data,$written));
			if($len === false) return $written;
		}
		return $written;
	}
	/**
	 * 読み込む
	 * @param integer $byte
	 * @return mixed
	 */
	public function read($length=null){
		return intval($length) > 0 ? fread($this->_resource_,intval($length)) : fread($this->_resource_,4096);
	}
	/**
	 * 一行読み込む
	 * @param integer $length
	 * @return mixed
	 */
	public function read_line($length=null){
		return intval($length) > 0 ? fgets($this->_resource_,intval($length)) : fgets($this->_resource_);
	}
}
