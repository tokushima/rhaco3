<?php
namespace org\rhaco\flow\parts;
/**
 * HTTPステータスヘッダ出力して終了する
 * @author tokushima
 *
 */
class HttpStatus{
	/**
	 * 400 bad request
	 */
	public function bad_request(){
		\org\rhaco\net\http\Header::send_status(400);
		exit;
	}
	/**
	 * 403 forbidden
	 */
	public function forbidden(){
		\org\rhaco\net\http\Header::send_status(403);
		exit;
	}
	/**
	 * 404 not found
	 */
	public function not_found(){
		\org\rhaco\net\http\Header::send_status(404);
		exit;
	}
	/**
	 * 405 method not allowed
	 */
	public function method_not_allowed(){
		\org\rhaco\net\http\Header::send_status(405);
		exit;
	}
	/**
	 * 406 not acceptable
	 */
	public function not_acceptable(){
		\org\rhaco\net\http\Header::send_status(406);
		exit;
	}
	/**
	 * 409 conflict
	 */
	public function conflict(){
		\org\rhaco\net\http\Header::send_status(409);
		exit;
	}
	/**
	 * 410 gone
	 */
	public function gone(){
		\org\rhaco\net\http\Header::send_status(410);
		exit;
	}
	/**
	 * 415 unsupported media type
	 */
	public function unsupported_media_type(){
		\org\rhaco\net\http\Header::send_status(415);
		exit;
	}
	/**
	 * 500 internal server error
	 */
	public function internal_server_error(){
		\org\rhaco\net\http\Header::send_status(500);
		exit;
	}
	/**
	 * 503 service unavailable
	 */
	public function service_unavailable(){
		\org\rhaco\net\http\Header::send_status(503);
		exit;
	}	
}
