<?php
namespace org\rhaco\net\listener;
/**
 * リスナーで利用するチャンネル
 * @author tokushima
 *
 */
class Channel{
	private $socket;

	public function __construct($socket){
		$this->socket = $socket;
	}
	public function read(){
		while(true){
			$buffer = socket_read($this->socket,4096,PHP_BINARY_READ);
			if($buffer === false){
				\org\rhaco\Log::warn('Interrupted');
				return;
			}
			if($buffer === "\n") continue;
			return $buffer;
		}
	}
	public function write($message){
		try{
			socket_write($this->socket,$message,strlen($message));
		}catch(\Exception $e){
			\org\rhaco\Log::warn('Interrupted');
		}
	}
	public function shutdown(){
		throw new \org\rhaco\net\listener\exception\ShutdownException();
	}
}