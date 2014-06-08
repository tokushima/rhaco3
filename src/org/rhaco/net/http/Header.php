<?php
namespace org\rhaco\net\http;
/**
 * HTTPヘッダを制御する
 * @author tokushima
 */
class Header{
	static private $header = array();
	static private $send_status;

	/**
	 * statusを出力する
	 * @param integer $code
	 */
	static public function send_status($code){
		if(!isset(self::$send_status)){
			self::$send_status = $code;
			header('HTTP/1.1 '.self::status_string($code));
		}
	}
	/**
	 * headerを送信する
	 * @param string $value 
	 */
	static public function send($key,$value){
		if(!isset(self::$header[$key])){
			header($key.': '.$value);
			self::$header[$key] = $value;
		}
	}	
	/**
	 * HTTPステータスを返す
	 * @param integer $statuscode 出力したいステータスコード
	 */
	static public function status_string($statuscode){
		switch($statuscode){
			case 100: return '100 Continue';
			case 101: return '101 Switching Protocols';
			case 200: return '200 OK';
			case 201: return '201 Created';
			case 202: return '202 Accepted';
			case 203: return '203 Non-Authoritative Information';
			case 204: return '204 No Content';
			case 205: return '205 Reset Content';
			case 206: return '206 Partial Content';
			case 300: return '300 Multiple Choices';
			case 301: return '301 MovedPermanently';
			case 302: return '302 Found';
			case 303: return '303 See Other';
			case 304: return '304 Not Modified';
			case 305: return '305 Use Proxy';
			case 307: return '307 Temporary Redirect';
			case 400: return '400 Bad Request';
			case 401: return '401 Unauthorized';
			case 403: return '403 Forbidden';
			case 404: return '404 Not Found';
			case 405: return '405 Method Not Allowed';
			case 406: return '406 Not Acceptable';
			case 407: return '407 Proxy Authentication Required';
			case 408: return '408 Request Timeout';
			case 409: return '409 Conflict';
			case 410: return '410 Gone';
			case 411: return '411 Length Required';
			case 412: return '412 Precondition Failed';
			case 413: return '413 Request Entity Too Large';
			case 414: return '414 Request-Uri Too Long';
			case 415: return '415 Unsupported Media Type';
			case 416: return '416 Requested Range Not Satisfiable';
			case 417: return '417 Expectation Failed';
			case 500: return '500 Internal Server Error';
			case 501: return '501 Not Implemented';
			case 502: return '502 Bad Gateway';
			case 503: return '503 Service Unavailable';
			case 504: return '504 Gateway Timeout';
			case 505: return '505 Http Version Not Supported';
			default: return '403 Forbidden ('.$statuscode.')';
		}
	}
	/**
	 * リダイレクトする
	 * @param string $url リダイレクトするURL
	 * @param mixed{} $vars query文字列として渡す変数
	 */
	static public function redirect($url,array $vars=array()){
		if(!empty($vars)){
			$requestString = \org\rhaco\net\Query::get($vars);
			if(substr($requestString,0,1) == "?") $requestString = substr($requestString,1);
			$url = sprintf("%s?%s",$url,$requestString);
		}
		self::send_status(302);
		self::send('Location',$url);
		exit;
	}
	/**
	 * リファラを取得する
	 *
	 * @return string
	 */
	static public function referer(){
		return (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'://') !== false) ? $_SERVER['HTTP_REFERER'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
	}
	/**
	 * リファラにリダイレクトする
	 */
	static public function redirect_referer(){
		self::redirect(self::referer());
	}
	/**
	 * rawdataを取得する
	 * @return string
	 */
	static public function rawdata(){
		return file_get_contents('php://input');
	}
	
}