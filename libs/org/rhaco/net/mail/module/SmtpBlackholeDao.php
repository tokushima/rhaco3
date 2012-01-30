<?php
namespace org\rhaco\net\mail\module;
/**
 * 送信するメールをDBに保存して実際にメールを送信しない
 * @author tokushima
 * @var serial $id
 * @var text $from
 * @var text $to
 * @var text $cc
 * @var string $subject
 * @var text $message
 * @var text $manuscript
 * @var timestamp $create_date @['auto_now_add'=>true]
 */
class SmtpBlackholeDao extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $from;
	protected $to;
	protected $cc;
	protected $bcc;
	protected $subject;
	protected $message;
	protected $manuscript;
	protected $create_date;
	
	public function send_mail(\org\rhaco\net\mail\Mail $mail){
		$self = new self();		
		$self->from($mail->from());
		$self->to(implode("\n",array_keys($mail->to())));
		$self->cc(implode("\n",array_keys($mail->cc())));
		$self->bcc(implode("\n",array_keys($mail->bcc())));
		$self->subject(mb_convert_encoding(base64_decode(preg_replace('/^=\?ISO-2022-JP\?B\?(.+)\?=$/','\\1',$mail->subject())),'UTF-8','JIS'));
		$self->message(mb_convert_encoding($mail->message(),'UTF-8','JIS'));
		$self->manuscript($mail->manuscript());
		$self->save();
	}
}