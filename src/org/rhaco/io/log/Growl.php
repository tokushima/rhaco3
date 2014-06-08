<?php
namespace org\rhaco\io\log;
/**
 * ログをGrowlする
 * @author tokushima
 * @conf string $growl コンストラクタへ渡すdict
 */
class Growl{
	private $glowl;
	public function __construct($type='udp',$address='localhost',$app_name='growl_notify',$password=null,$port=null){
		$this->growl = new \org\rhaco\net\Growl($type,$address,$app_name,$password,$port);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function info(\org\rhaco\Log $log,$id){
		$this->growl->normal($this->value($log),$this->title($log));
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function warn(\org\rhaco\Log $log,$id){
		$this->growl->high($this->value($log),$this->title($log));
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function error(\org\rhaco\Log $log,$id){
		$this->growl->emergency($this->value($log),$this->title($log),true);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function trace(\org\rhaco\Log $log,$id){
		$this->growl->emergency($this->value($log),$this->title($log),true);
	}
	private function value(\org\rhaco\Log $log){
		$lines = 3;
		$ln = array();
		$l = explode("\n",$log->fm_value());
		for($i=0;$i<$lines&&$i<sizeof($l);$i++) $ln[] = $l[$i];
		return $value = implode("\n",$ln);
	}
	private function title(\org\rhaco\Log $log){
		return $log->time().' '.$log->file().':'.$log->line();
	}
}
