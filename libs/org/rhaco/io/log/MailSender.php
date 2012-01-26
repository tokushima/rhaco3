<?php
namespace org\rhaco\io\log;
/**
 * ログをメール送信する
 *
 * 以下パスにテンプレートファイルがあれば送信
 * [template_path]/debug_mail.xml
 * [template_path]/info_mail.xml
 * [template_path]/warn_mail.xml
 * [template_path]/error_mail.xml
 *
 * @conf string $template_base string mailテンプレートのパス
 * @author tokushima
 *
 */
class LogMailSender{
	private $template_base;

	protected function __init__(){
		$this->template_base = \org\rhaco\Conf::get('template_base');
	}
	public function debug(\org\rhaco\Log $log){
		$this->send('debug',$log);
	}
	public function info(\org\rhaco\Log $log){
		$this->send('info',$log);
	}
	public function warn(\org\rhaco\Log $log){
		$this->send('warn',$log);
	}
	public function error(\org\rhaco\Log $log){
		$this->send('error',$log);
	}
	protected function send($level,\org\rhaco\Log $log){
		$template = \org\rhaco\net\Path::absolute($this->template_base,$level.'_log.xml');
		if(is_file($template)){
			$mail = new \org\rhaco\net\mail\Mail();
			$mail->to($member->email());
			$mail->send_template($template,array('log'=>$log,'env'=>new \org\rhaco\lang\Env()));
		}
	}
}
