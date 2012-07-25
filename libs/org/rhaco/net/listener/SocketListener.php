<?php
namespace org\rhaco\net\listener;
/**
 * ソケット上で接続待ちをする
 * @author tokushima
 */
class SocketListener extends \org\rhaco\Object{
	private $gsocket;
	private $socket;
	/**
	 * 接続待ちを開始する
	 */
	public function start($address='localhost',$port=8888,$backlog=0){
		$req = new \org\rhaco\Request();
		if($req->is_vars('address')) $address = $req->in_vars('address',$address);
		if($req->is_vars('port')) $port = $req->in_vars('port',$port);
		
		@set_time_limit(0);
		$start_time = time();
		while(true){
			try{
				$this->gsocket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
				if($this->gsocket === false){
					throw new \org\rhaco\net\listener\exception\ConnectException(socket_strerror(socket_last_error()));
				}
				if(false === socket_bind($this->gsocket,$address,$port)){
					throw new \org\rhaco\net\listener\exception\ConnectException(socket_strerror(socket_last_error()));
				}
				if(false === socket_listen($this->gsocket,$backlog)){
					throw new \org\rhaco\net\listener\exception\ConnectException(socket_strerror(socket_last_error()));			
				}
				/**
				 * listen
				 * @param string $address
				 * @param integer $port
				 */
				$this->object_module('listen',$address,$port);
				
				while(true){
					$this->socket = socket_accept($this->gsocket);
					if($this->socket === false) throw new \org\rhaco\net\listener\exception\ConnectException(socket_strerror(socket_last_error()));
					$channel = new \org\rhaco\net\listener\Channel($this->socket);
	
					try{
						/**
						 * 接続時の処理
						 * @param org.rhaco.net.listener.Channel $channel
						 */
						$this->object_module('instruction',$channel);
						/**
						 * 接続後の処理
						 * @param org.rhaco.net.listener.Channel $channel
						 */
						$this->object_module('connect',$channel);
						$this->socket_close($this->socket);
					}catch(\org\rhaco\net\listener\exception\ShutdownException $e){
						unset($channel);
						$this->socket_close($this->socket);					
						break;
					}
					unset($channel);
				}
				$this->socket_close($this->gsocket);
			}catch(\org\rhaco\net\listener\exception\ConnectException $e){
				$this->socket_close($this->gsocket);
				if(($start_time + 30) < time()) throw new \org\rhaco\net\listener\exception\ErrorException($e->getMessage());
				sleep(1);
			}catch(\Exception $e){
				$this->socket_close($this->gsocket);
				throw new \org\rhaco\net\listener\exception\ErrorException($e->getMessage());
			}
		}
	}
	private function socket_close(&$sock){
		if(is_resource($sock)){
			socket_close($sock);
			$sock = null;
		}
	}
	protected function __del__(){
		$this->socket_close($this->gsocket);
		$this->socket_close($this->socket);
	}
}
