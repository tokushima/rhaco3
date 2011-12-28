<?php
namespace org\rhaco\flow\module;
/**
 * Jsonで出力する
 * @author tokushima
 */
class OutputJson{
	private $mode;
	private $varname;

	public function __construct($mode='json',$varname='callback'){
		$this->mode = strtolower($mode);
		$this->varname = $varname;
	}
	public function flow_output($obj){
		if(\org\rhaco\Exceptions::has()){
			$this->flow_exception_output($obj,new \org\rhaco\Exceptions());
		}else{
			\org\rhaco\Log::disable_display();
			\org\rhaco\net\http\Header::send('Content-Type',(($this->mode == 'jsonp') ? 'text/javascript' : 'application/json'));
			$json = \org\rhaco\lang\Json::encode(array('result'=>$obj));
			print(($this->mode == 'jsonp') ? $this->varname.'('.$json.')' : $json);
		}
	}
	public function flow_exception_output($obj,\Exception $exception){
		\org\rhaco\Log::disable_display();
		\org\rhaco\net\http\Header::send('Content-Type',(($this->mode == 'jsonp') ? 'text/javascript' : 'application/json'));
		$error = array('error'=>array());
		
		if($exception instanceof \org\rhaco\Exceptions){
			foreach(\org\rhaco\Exceptions::groups() as $g){
				foreach(\org\rhaco\Exceptions::gets($g) as $e){
					$error['error'][] = array('message'=>$e->getMessage(),'group'=>$g,$type=>basename(str_replace("\\",'/',get_class($e))));
				}
			}
		}else{
			$error['error'][] = array('message'=>$exception->getMessage(),'group'=>'',$type=>basename(str_replace("\\",'/',get_class($exception))));
		}
		$json = \org\rhaco\lang\Json::encode($error);
		print(($this->mode == 'jsonp') ? $this->varname.'('.$json.')' : $json);
	}
}