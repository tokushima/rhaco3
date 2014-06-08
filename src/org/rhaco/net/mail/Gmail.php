<?php
namespace org\rhaco\net\mail;
/**
 * gmailでメール送信を行う
 * tls://を利用にするためOpenSSL サポートを有効にしてある必要がある
 * @author tokushima
 */
class Gmail extends \org\rhaco\net\mail\Mail{
	protected $login;
	protected $password;
	private $resource;

	protected function __new__($login,$password){
		$this->login = $login;
		$this->password = $password;
		parent::__new__();
		$this->from($login);

		$this->resource = fsockopen('tls://smtp.gmail.com',465,$errno,$errstr,30);
		if($this->resource === false) throw new \Exception('connect fail');
	}
	protected function __del__(){
		fclose($this->resource);
	}
	/**
	 * メールを送信する
	 */
	public function send($subject='',$message=''){
		if(!empty($subject)) $this->subject($subject);
		if(!empty($message)) $this->message($message);
		if('' == fgets($this->resource,4096)) return false;

		$this->talk('HELO '.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'localhost'));
		$this->talk('AUTH LOGIN');
		$this->talk(base64_encode($this->login));
		$this->talk(base64_encode($this->password));
		$this->talk(sprintf('MAIL FROM: <%s>',$this->login));
		foreach(array_keys($this->to) as $to) $this->talk(sprintf('RCPT TO: <%s>',$to));
		$this->talk('DATA');
		$this->talk($this->manuscript().'.');
		$rtn = $this->talk('QUIT');
	}
	private function talk($message){
		fputs($this->resource,$message."\r\n");
		$message = fgets($this->resource,4096);
		list($code) = explode(' ',$message);
		switch($code){
			case 502:
			case 530:
			case 550:
			case 555:
				throw new \Exception($message);
			case 235:
			case 250: // OK
			case 334: // レスポンス待ち
			case 354: // 入力の開始
			case 221: // 転送チャンネルを閉じる
		}
		return true;
	}
}
