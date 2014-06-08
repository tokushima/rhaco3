<?php
namespace org\rhaco\net\mail;
use \org\rhaco\io\File;
/**
 * メール送信に関する情報を制御する
 *
 * @author tokushima
 * @author Kentaro YABE
 * @var string{} $to
 * @var string{} $cc
 * @var string{} $bcc
 * @var org.rhaco.io.File{} $attach
 * @var org.rhaco.io.File{} $media
 * @var choice $encode @['choices'=>['jis','utf8','sjis']]
 */
class Mail extends \org\rhaco\Object{
	protected $subject;
	protected $to;
	protected $cc;
	protected $bcc;
	protected $attach;
	protected $media;
	protected $message;
	protected $html;
	protected $from;
	protected $name;
	protected $return_path;
	protected $reply_to;
	protected $errors_to;
	
	protected $notification;
	protected $encode = "jis";

	private $eol = "\n";
	private $boundary = array('mixed'=>'mixed','alternative'=>'alternative','related'=>'related');
	private $resource_path;

	protected function __init__(){
		$this->boundary = array('mixed'=>'----=_Part_'.uniqid('mixed'),'alternative'=>'----=_Part_'.uniqid('alternative'),'related'=>'----=_Part_'.uniqid('related'));
	}
	protected function __set_from__($mail,$name=null){
		$this->from = $mail;
		$this->name = $name;
		$this->return_path = $mail;
	}
	protected function __set_to__($mail,$name=""){
		$this->to[$mail] = $this->address($mail,$name);
	}
	protected function __set_cc__($mail,$name=""){
		$this->cc[$mail] = $this->address($mail,$name);
	}
	protected function __set_bcc__($mail,$name=""){
		$this->bcc[$mail] = $this->address($mail,$name);
	}
	protected function __set_return_path__($mail){
		$this->return_path = $mail;
	}
	protected function __set_subject__($subject){
		$this->subject = str_replace("\n","",str_replace(array("\r\n","\r"),"\n",$subject));
	}
	protected function __get_subject__(){
		return $this->jis($this->subject);
	}
	protected function __set_attach__($filename,$src,$type="application/octet-stream"){
		$this->attach[] = array(new File(basename($filename),$src),$type);
	}
	protected function __set_media__($filename,$src,$type="application/octet-stream"){
		$this->media[$filename] = array(new File(basename($filename),$src),$type);
	}
	protected function __set_message__($message){
		$this->message = $this->encode($message);
	}
	protected function __set_html__($message){
		$this->html = $this->encode($message);
		if($this->message === null) $this->message(strip_tags($message));
	}
	protected function __fm_to__($glue=', '){
		return implode($glue,array_keys($this->to()));
	}
	protected function __fm_cc__($glue=', '){
		return implode($glue,array_keys($this->cc()));
	}
	protected function __fm_bcc__($glue=', '){
		return implode($glue,array_keys($this->bcc()));
	}
	protected function __fm_subject__(){
		return mb_convert_encoding(base64_decode(preg_replace('/^=\?ISO-2022-JP\?B\?(.+)\?=$/','\\1',$this->subject())),'UTF-8','JIS');
	}
	protected function __fm_message__(){
		return mb_convert_encoding($this->message(),'UTF-8','JIS');
	}
	public function resource_path($path){
		$this->resource_path = $path;
	}
	public function header(){
		$send = '';
		$send .= $this->line("MIME-Version: 1.0");
		$send .= $this->line("To: ".$this->implode_address($this->to));
		$send .= $this->line("From: ".$this->address($this->from,$this->name));
		if(!empty($this->cc)) $send .= $this->line("Cc: ".$this->implode_address($this->cc));
		if(!empty($this->bcc)) $send .= $this->line("Bcc: ".$this->implode_address($this->bcc));
		if(!empty($this->return_path)) $send .= $this->line("Return-Path: ".$this->return_path);
		if(!empty($this->reply_to)) $send .= $this->line("Reply-To: ".$this->reply_to);
		if(!empty($this->errors_to)) $send .= $this->line("Errors-To: ".$this->errors_to);
		if(!empty($this->notification)) $send .= $this->line("Disposition-Notification-To: ".$this->notification);
		$send .= $this->line("Date: ".date("D, d M Y H:i:s O",time()));
		$send .= $this->line("Subject: ".$this->subject());

		if(!empty($this->attach)){
			$send .= $this->line(sprintf("Content-Type: multipart/mixed; boundary=\"%s\"",$this->boundary["mixed"]));
		}else if(!empty($this->html)){
			$send .= $this->line(sprintf("Content-Type: multipart/alternative; boundary=\"%s\"",$this->boundary["alternative"]));
		}else{
			$send .= $this->meta("plain");
		}
		return $send;
	}
	protected function implode_address($list){
		return trim(implode(','.$this->eol.' ',is_array($list) ? $list : array($list)));
	}
	protected function body(){
		$send = "";
		$isattach = (!empty($this->attach));
		$ishtml = (!empty($this->html));

		if($isattach){
			$send .= $this->line("--".$this->boundary["mixed"]);

			if($ishtml){
				$send .= $this->line(sprintf("Content-Type: multipart/alternative; boundary=\"%s\"",$this->boundary["alternative"]));
				$send .= $this->line();
			}
		}
		$send .= (!$ishtml) ? (($isattach) ? $this->meta("plain").$this->line() : "").$this->line($this->message) : $this->alternative();
		if($isattach){
			foreach($this->attach as $attach){
				$send .= $this->line("--".$this->boundary["mixed"]);
				$send .= $this->attach_string($attach);
			}
			$send .= $this->line("--".$this->boundary["mixed"]."--");
		}
		return $send;
	}
	private function alternative(){
		$send = "";
		$send .= $this->line("--".$this->boundary["alternative"]);
		$send .= $this->meta("plain");
		$send .= $this->line();
		$send .= $this->line($this->encode($this->message));
		$send .= $this->line("--".$this->boundary["alternative"]);
		if(empty($this->media)) $send .= $this->meta("html");
		$send .= $this->line($this->encode((empty($this->media)) ? $this->line().$this->html : $this->related()));
		$send .= $this->line("--".$this->boundary["alternative"]."--");
		return $send;
	}
	private function related(){
		$send = $this->line().$this->html;
		$html = $this->html;
		foreach(array_keys($this->media) as $name){
			// tags
			$preg = '/(\s)(src|href)\s*=\s*(["\']?)' . preg_quote($name) . '\3/';
			$replace = sprintf('\1\2=\3cid:%s\3', md5($name));
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
			// css
			$preg = '/url\(\s*(["\']?)' . preg_quote($name) . '\1\s*\)/';
			$replace = sprintf('url(\1cid:%s\1)', md5($name));
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
		}
		if($html != $this->html){
			$send = "";
			$send .= $this->line(sprintf("Content-Type: multipart/related; boundary=\"%s\"",$this->boundary["related"]));
			$send .= $this->line();
			$send .= $this->line("--".$this->boundary["related"]);
			$send .= $this->meta("html");
			$send .= $this->line();
			$send .= $this->line($this->encode($html));

			foreach($this->media as $name => $media){
				$send .= $this->line("--".$this->boundary["related"]);
				$send .= $this->attach_string($media,md5($name));
			}
			$send .= $this->line("--".$this->boundary["related"]."--");
		}
		return $send;
	}
	private function jis($str){
		return sprintf("=?ISO-2022-JP?B?%s?=",base64_encode(mb_convert_encoding($str,"JIS",mb_detect_encoding($str))));
	}
	private function meta($type){
		switch(strtolower($type)){
			case "html": $type = "text/html"; break;
			default: $type = "text/plain";
		}
		switch($this->encode){
			case "utf8":
				return $this->line(sprintf("Content-Type: %s; charset=\"utf-8\"",$type)).
						$this->line("Content-Transfer-Encoding: 8bit");
			case "sjis":
				return $this->line(sprintf("Content-Type: %s; charset=\"iso-2022-jp\"",$type)).
						$this->line("Content-Transfer-Encoding: base64");
			default:
				return $this->line(sprintf("Content-Type: %s; charset=\"iso-2022-jp\"",$type)).
						$this->line("Content-Transfer-Encoding: 7bit");
		}
	}
	private function encode($message){
		switch($this->encode){
			case "utf8": return mb_convert_encoding($message,"UTF8",mb_detect_encoding($message));
			case "sjis": return mb_convert_encoding(base64_encode(mb_convert_encoding($message,"SJIS",mb_detect_encoding($message)),"JIS"));
			default: return mb_convert_encoding($message,"JIS",mb_detect_encoding($message));
		}
	}
	protected function line($value=""){
		return $value.$this->eol;
	}
	private function attach_string($list,$id=null){
		list($file,$type) = $list;
		$send = '';
		$send .= $this->line(sprintf("Content-Type: %s; name=\"%s\"",(empty($type) ? "application/octet-stream" : $type),$file->name()));
		$send .= $this->line(sprintf("Content-Transfer-Encoding: base64"));
		if(!empty($id)){
			$send .= $this->line(sprintf("Content-ID: <%s>",$id));
		}
		$send .= $this->line();
		$send .= $this->line(trim(chunk_split(base64_encode($file->get()),76,$this->eol)));
		return $send;
	}
	private function address($mail,$name){
		return '"'.(empty($name) ? $mail : $this->jis($name)).'" <'.$mail.'>';
	}

	/**
	 * 送信する内容
	 * @param boolean $eol
	 * @return string
	 */
	public function manuscript($eol=true){
		$pre = $this->eol;
		$this->eol = ($eol) ? "\r\n" : "\n";
		$bcc = $this->bcc;
		$this->bcc = array();
		$send = $this->header().$this->line().$this->body();
		$this->bcc = $bcc;
		$this->eol = $pre;
		return $send;
	}
	/**
	 * メールを送信する
	 * @param string $subject
	 * @param string $message
	 * @return boolean
	 */
	public function send($subject=null,$message=null){
		if($this->has_object_module('set_mail')){
			/**
			 * メールオブジェクトをセットする
			 * @param self $this
			 */
			$this->object_module('set_mail',$this);
		}else if(static::has_module('set_mail')){
			$this->object_module('set_mail',$this);
		}
		if($subject !== null) $this->subject($subject);
		if($message !== null) $this->message($message);

		if($this->has_object_module('send_mail')){
			/**
			 * メールオブジェクトを送信する
			 * @param self $this
			 */
			$this->object_module('send_mail',$this);
		}else if($this->has_module('send_mail')){
			static::module('send_mail',$this);
		}else{
			if(!$this->is_to()) throw new \RuntimeException('undefine to');
			if(!$this->is_from()) throw new \RuntimeException('undefine from');
			$header = $this->header();
			$header = preg_replace("/".$this->eol."Subject: .+".$this->eol."/","\n",$header);
			$header = preg_replace("/".$this->eol."To: .+".$this->eol."/","\n",$header);
			mail($this->implode_address($this->to),$this->subject(),$this->body(),trim($header),'-f'.$this->from());
		}
	}
	/**
	 * テンプレートから内容を取得しメールを送信する
	 * @param string　$template_path テンプレートファイルパス
	 * @param mixed{} $vars テンプレートへ渡す変数
	 * @return $this
	 */
	public function send_template($template_path,$vars=array()){
		return $this->set_template($template_path,$vars)->send();
	}
	/**
	 * テンプレートから内容を取得しセットする
	 * 
	 * テンプレートサンプル
	 * <mail>
	 * <from address="support@rhaco.org" name="tokushima" />
	 * <subject>メールのタイトル</subject>
	 * <body>
	 * メールの本文
	 * </body>
	 * </mail>
	 * 
	 * @param string　$template_path テンプレートファイルパス
	 * @param mixed{} $vars テンプレートへ渡す変数
	 * @return $this
	 */
	public function set_template($template_path,$vars=array()){
		$resource_path = empty($this->resource_path) ? \org\rhaco\Conf::get('resource_path',\org\rhaco\io\File::resource_path('mail')) : $this->resource_path;
		$template_path = \org\rhaco\net\Path::absolute($resource_path,$template_path);
		
		if(!is_file($template_path)){
			throw new \InvalidArgumentException($template_path.' not found');
		}
		if(\org\rhaco\Xml::set($xml,file_get_contents($template_path),'mail')){
			$from = $xml->f('from');
			if($from !== null){
				$this->from($from->in_attr('address'),$from->in_attr('name'));
			}
			foreach($xml->in('to') as $to){
				$this->to($to->in_attr('address'),$to->in_attr('name'));
			}
			$return_path = $xml->f('return_path');
			if($return_path !== null){
				$this->return_path($return_path->in_attr('address'));
			}
			$notification = $xml->f('notification');
			if($notification !== null){
				$this->notification($notification->in_attr('address'));
			}
			$reply_to = $xml->f('reply_to');
			if($reply_to !== null){
				$this->reply_to($reply_to->in_attr('address'));
			}
			$errors_to = $xml->f('errors_to');
			if($errors_to !== null){
				$this->errors_to($errors_to->in_attr('address'));
			}
			$subject = trim(str_replace(array("\r\n","\r","\n"),'',$xml->f('subject.value()')));
			
			$body = $xml->f('body.value()');
			$template = new \org\rhaco\Template();
			$template->cp($vars);
			$template->vars('t',new \org\rhaco\flow\module\Helper());
			
			$this->subject($template->get($subject));
			$this->message(\org\rhaco\lang\Text::plain("\n".$template->get($body)."\n"));
			
			$html = $xml->f('html');
			if($html !== null){
				$html_path = \org\rhaco\net\Path::absolute($resource_path,$html->in_attr('src'));
				
				foreach($html->in('media') as $media){
					$file = \org\rhaco\net\Path::absolute($resource_path,$media->in_attr('src'));
					if(!is_file($file)){
						throw new \InvalidArgumentException($media->in_attr('src').' invalid media');
					}
					$this->media($media->in_attr('src'),file_get_contents($file));
				}
				$template = new \org\rhaco\Template();
				$template->cp($vars);
				$template->vars('t',new \org\rhaco\flow\module\Helper());
				$this->html($template->read($html_path));
			}
			foreach($xml->in('attach') as $attach){
				$file = \org\rhaco\net\Path::absolute($resource_path,$attach->in_attr('src'));
				if(!is_file($file)){
					throw new \InvalidArgumentException($attach->in_attr('src').' invalid media');
				}
				$this->attach($attach->in_attr('name',$attach->in_attr('src')),file_get_contents($file));				
			}
			return $this;
		}
		throw new \InvalidArgumentException($template_path.' invalid data');
	}
}
