<?php
namespace org\rhaco\flow\module;
/**
 * XMLで出力する
 * @author tokushima
 */
class OutputXml{
	public function flow_output($obj){
		if(\org\rhaco\Exceptions::has()){
			$this->flow_exception_output($obj,new \org\rhaco\Exceptions());
		}else{
			\org\rhaco\Log::disable_display();
			$xml = new \org\rhaco\Xml('result',$obj);
			$xml->output();
		}
	}
	public function flow_exception_output($obj,\Exception $exception){
		\org\rhaco\Log::disable_display();
		$xml = new \org\rhaco\Xml('error');
			if($exception instanceof \org\rhaco\Exceptions){
				foreach(\org\rhaco\Exceptions::groups() as $g){
					foreach(\org\rhaco\Exceptions::gets($g) as $e){
						$message = new \org\rhaco\Xml('message',$e->getMessage());
						$message->add('group',$g);
						$message->add('type',basename(str_replace("\\",'/',get_class($e))));
						$xml->add($message);
					}
				}
			}else{
				$message = new \org\rhaco\Xml('message',$exception->getMessage());
				$message->add('group','exceptions');
				$message->add('type',basename(str_replace("\\",'/',get_class($exception))));
				$xml->add($message);
			}
		$xml->output();
	}
}
