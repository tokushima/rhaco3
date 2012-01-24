<?php
namespace org\rhaco\flow\module;
/**
 * ログインを必須とさせる
 * @author tokushima
 *
 */
class LoginRequired{
	public function before_flow_handle(\org\rhaco\flow\parts\RequestFlow $flow){
		$flow->login_required();
	}
}