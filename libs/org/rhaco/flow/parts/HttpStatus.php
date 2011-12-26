<?php
namespace org\rhaco\flow\parts;
/**
 * HTTPステータスヘッダ出力して終了する
 * @author tokushima
 *
 */
class HttpStatus{
	public function bad_request(){
		\org\rhaco\net\http\Header::send_status(400);
		exit;
	}
	public function forbidden(){
		\org\rhaco\net\http\Header::send_status(403);
		exit;
	}
	public function not_found(){
		\org\rhaco\net\http\Header::send_status(404);
		exit;
	}
	public function method_not_allowed(){
		\org\rhaco\net\http\Header::send_status(405);
		exit;
	}
	public function not_acceptable(){
		\org\rhaco\net\http\Header::send_status(406);
		exit;
	}
	public function conflict(){
		\org\rhaco\net\http\Header::send_status(409);
		exit;
	}
	public function gone(){
		\org\rhaco\net\http\Header::send_status(410);
		exit;
	}
	public function unsupported_media_type(){
		\org\rhaco\net\http\Header::send_status(415);
		exit;
	}
	public function internal_server_error(){
		\org\rhaco\net\http\Header::send_status(500);
		exit;
	}
	public function service_unavailable(){
		\org\rhaco\net\http\Header::send_status(503);
		exit;
	}	
}
