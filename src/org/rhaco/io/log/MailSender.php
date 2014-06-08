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
class MailSender{
	private $template_base;

	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function debug(\org\rhaco\Log $log){
		$this->send('debug',$log);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function info(\org\rhaco\Log $log){
		$this->send('info',$log);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function warn(\org\rhaco\Log $log){
		$this->send('warn',$log);
	}
	/**
	 * @module org.rhaco.Log
	 * @param \org\org.rhaco.Log\Log $log
	 * @param string $id
	 */
	public function error(\org\rhaco\Log $log){
		$this->send('error',$log);
	}
	protected function send($level,\org\rhaco\Log $log){
		if(empty($this->template_base)) $this->template_base = \org\rhaco\Conf::get('template_base',\org\rhaco\io\File::resource_path('log_mail'));
		$template = \org\rhaco\net\Path::absolute($this->template_base,$level.'_log.xml');
		if(is_file($template)){
			$mail = new \org\rhaco\net\mail\Mail();
			$mail->send_template($template,array('log'=>$log,'env'=>new \org\rhaco\lang\Env()));
		}
	}
}
