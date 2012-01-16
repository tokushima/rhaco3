<?php
namespace org\rhaco\flow\module;
/**
 * do_login以外の場合にログインしていなければ例外をだす
 * @author tokushima
 *
 */
class LoginRequiredAlways{
	public function before_login_required(\org\rhaco\flow\parts\RequestFlow $flow){
		if(!$flow->is_login()){
			\org\rhaco\net\http\Header::send_status(401);
			if(!\org\rhaco\Exceptions::has()) \org\rhaco\Exceptions::add(new \LogicException('Unauthorized'),'do_login');
			\org\rhaco\Exceptions::throw_over();
		}
	}
}