<?php
namespace org\rhaco\flow\module;
/**
 * ログインを必須とさせる
 * @author tokushima
 *
 */
class LoginRequired{
	/**
	 * @module org.rhaco.flow.parts.RequestFlow
	 * @param org.rhaco.flow.parts.RequestFlow $flow
	 */	
	public function before_flow_action(\org\rhaco\flow\parts\RequestFlow $flow){
		$flow->login_required();
	}
}