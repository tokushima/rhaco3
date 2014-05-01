<?php
$vars = array('abc'=>'ABC');
$mail = new \org\rhaco\net\mail\Mail();
$mail->to("test@rhaco.org");
$mail->send_template('send.xml',$vars);
$xml = \org\rhaco\Dt::find_mail('test@rhaco.org');
eq('ボディーテストABC'.PHP_EOL,$xml->message());
eq('テストサブジェクト',$xml->subject());


$vars = array('abc'=>'ABC');
$mail = new \org\rhaco\net\mail\Mail();
$mail->to("test@rhaco.org");
$mail->send_template('send_html.xml',$vars);
$xml = \org\rhaco\Dt::find_mail('test@rhaco.org');
eq('ボディーテストABC'.PHP_EOL,$xml->message());
eq('テストサブジェクト',$xml->subject());
meq('Content-Type: text/html;',$xml->manuscript());
meq('<p class="abc">ピーボディー</p>',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
meq('send_html.css',mb_convert_encoding($xml->manuscript(),'UTF8','JIS'));
