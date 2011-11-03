<?php
namespace org\rhaco\net;
/**
 * Growlに通知する
 * Growlの設定-ネットワークで「リモートアプリケーション登録を許可」にチェックする必要がある
 * @see http://growl.info/documentation/developer/protocol.php
 * @author tokushima
 */
class Growl{
	const PROTOCOL_VERSION = 1;
	const TYPE_REGISTRATION = 0;
	const TYPE_NOTIFICATION = 1;

	private $con;
	private $type = "udp";
	private $address = "localhost";
	private $port;
	private $app_name = "growl_notify";
	private $password;
	private $notifications = array();
	private $notification = "default";

	public function __construct($type='udp',$address='localhost',$app_name='growl_notify',$password=null,$port=null){
		$this->type = $type;
		$this->address = $address;
		$this->app_name = $app_name;
		$this->password = $password;
		$this->port = $port;
		
		if(empty($this->port)){
			switch($this->type){
				case "udp": $this->port = 9887; break;
				case "tcp": $this->port = 23052; break;
			}
		}
		$this->connect();
		$this->notification($this->notification);
	}
	/**
	 * 通知する
	 * @param string $description
	 * @param string $title
	 * @param boolean $sticky
	 */
	public function low($description,$title=null,$sticky=false,$icon=null){
		return $this->talk($title,$description,$sticky,-2,$icon);
	}
	/**
	 * 通知する
	 * @param string $description
	 * @param string $title
	 * @param boolean $sticky
	 */
	public function moderate($description,$title=null,$sticky=false,$icon=null){
		return $this->talk($title,$description,$sticky,-1,$icon);
	}
	/**
	 * 通知する
	 * @param string $description
	 * @param string $title
	 * @param boolean $sticky
	 */
	public function normal($description,$title=null,$sticky=false,$icon=null){
		return $this->talk($title,$description,$sticky,0,$icon);
	}
	/**
	 * 通知する
	 * @param string $description
	 * @param string $title
	 * @param boolean $sticky
	 */
	public function high($description,$title=null,$sticky=false,$icon=null){
		return $this->talk($title,$description,$sticky,1,$icon);
	}
	/**
	 * 通知する
	 * @param string $description
	 * @param string $title
	 * @param boolean $sticky
	 */
	public function emergency($description,$title=null,$sticky=false,$icon=null){
		return $this->talk($title,$description,$sticky,2,$icon);
	}
	/**
	 * 通知先を設定する
	 * @param string $name 通知先名
	 */	
	public function notification($name){
		$this->set_notification($name);
	}
	
	private function talk($title,$description,$sticky,$priority,$icon){
		switch($this->type){
			case "udp":
				$this->udp_talk($title,$description,$sticky,$priority);
				break;
			case "tcp":
				$this->tcp_talk($title,$description,$sticky,$priority,$icon);
				break;
		}
	}
	public function __destruct(){
		unset($this->con);
	}
	private function connect(){
		switch($this->type){
			case "udp":
				$this->con = new \org\rhaco\net\Udp($this->address,$this->port);
				break;
			case "tcp":
				$this->con = new \org\rhaco\net\Tcp($this->address,$this->port);
				break;
		}
	}
	private function set_notification($name){
		switch($this->type){
			case "udp":
				$this->udp_notification($name);
				break;
			case "tcp":
				$this->tcp_notification($name);
				break;
		}
	}
	private function udp_notification($name){
		if(!isset($this->notifications[$name])){
			$this->notifications[$name] = true;
			$e = $d = "";
			$c = $n = 0;
	
			foreach($this->notifications as $nn => $b){
				$e .= pack('n',strlen($nn)).$nn;
				$c++;
	
				if($b){
					$d .= pack( "c", $c-1 );
					$n++;
				}
			}
			$data = pack('c2nc2',self::PROTOCOL_VERSION,self::TYPE_REGISTRATION,strlen($this->app_name),$c,$n).$this->app_name.$e.$d;
			$data .= pack('H32',md5(isset($this->password) ? $data.$this->password : $data));
			$this->con->send($data);
		}
		$this->notification = $name;
		return $this;
	}
	private function udp_talk($title,$description,$sticky,$priority){
		$flag = ($priority & 7) * 2;
		if($priority < 0) $flag |= 8;
		if($sticky) $flag |= 1;

		$data = pack('c2n5',self::PROTOCOL_VERSION,self::TYPE_NOTIFICATION,$flag
					,strlen($this->notification),strlen($title),strlen($description),strlen($this->app_name))
					.$this->notification.$title.$description.$this->app_name;
		$data .= pack('H32',md5(isset($this->password) ? $data.$this->password : $data));
		$this->con->send($data);
		return $this;
	}
	private function tcp_notification($name){		
		if(!isset($this->notifications[$name])){
			$this->notifications[$name] = true;
			$this->con->send("GNTP/1.0 REGISTER NONE\r\n");
			$this->con->send(sprintf("Application-Name: %s\r\n",$this->app_name));
			$this->con->send("Notifications-Count: 1\r\n");
			$this->con->send("\r\n");
			$this->con->send("Notification-Name: My Notify\r\n");
			$this->con->send("Notification-Display-Name: My Notify\r\n");
			$this->con->send("Notification-Enabled: True\r\n");
			$this->con->send("\r\n");
		}
		$this->notification = $name;
		return $this;
	}
	private function tcp_talk($title,$description,$sticky,$priority,$icon){
		$this->con->send("GNTP/1.0 NOTIFY NONE\r\n");
		$this->con->send(sprintf("Application-Name: %s\r\n",$this->app_name));
		$this->con->send(sprintf("Notification-Name: %s\r\n",$this->notification));
		$this->con->send(sprintf("Notification-Title: %s\r\n",$title));
		$this->con->send(sprintf("Notification-Text: %s\r\n",$description));
		if(!empty($icon)) $this->con->send(sprintf("Notification-Icon: %s\r\n",$icon_path));
		$this->con->send("\r\n");
		return $this;
	}
}
