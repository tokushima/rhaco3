<?php
namespace org\rhaco\flow\module;
/**
 * Jsonで出力する
 * @author tokushima
 */
class OutputJson{
	public function flow_output($obj){
		print(\org\rhaco\lang\Json::encode($obj));
	}
}