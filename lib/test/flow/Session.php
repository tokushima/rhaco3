<?php
namespace test\flow;
/**
 * テストクラス
 * @author tokushima
 *
 */
class Session extends \org\rhaco\flow\parts\RequestFlow{
	public function set_session(){
		$this->sessions('abc',$this->in_vars('abc'));
		if($this->is_vars('redirect')){
			\org\rhaco\net\http\Header::redirect($this->in_vars('redirect'));
		}
	}
	public function get_session(){
		$this->vars('abc',$this->in_sessions('abc'));
	}
}