<?php
namespace org\rhaco\flow\module;
/**
 * CSRFトークン
 * @author tokushima
 *
 */
class Csrf{
	private $no;
	
	public function before_flow_action($req){
		if($req->is_post() && ($req->in_vars('csrftoken') == '' || $req->in_sessions('csrftoken') !== $req->in_vars('csrftoken'))){
			\org\rhaco\net\http\Header::send_status(403);
			throw new \RuntimeException('CSRF verification failed');
		}
		$this->no = md5(rand(1000,10000).time());
		$req->sessions('csrftoken',$this->no);
		$req->vars('csrftoken',$this->no);
	}
	public function after_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		
		\org\rhaco\Xml::set($tag,'<:>'.$src.'</:>');

		foreach($tag->in('form') as $form){
			if($form->in_attr('action') == '' || strpos($form->in_attr('action'),'$t.map_url') !== false){
				$form->escape(false);
				$form->value(sprintf('<input type="hidden" name="csrftoken" value="%s" />',$this->no).$form->value());
				$src = str_replace($form->plain(),$form->get(),$src);
			}
		}
		$obj->set($src);

	}
}