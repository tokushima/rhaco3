<?php
$mail = new \org\rhaco\net\mail\Mail();
$mail->to("test1@rhaco.org","abc");
$mail->to("test2@rhaco.org");
$mail->to("test3@rhaco.org","ghi");
eq(array(
		'test1@rhaco.org' => '"=?ISO-2022-JP?B?YWJj?=" <test1@rhaco.org>',
		'test2@rhaco.org' => '"test2@rhaco.org" <test2@rhaco.org>',
		'test3@rhaco.org' => '"=?ISO-2022-JP?B?Z2hp?=" <test3@rhaco.org>',
),$mail->to());



$mail = new \org\rhaco\net\mail\Mail();
$mail->cc("test1@rhaco.org","abc");
$mail->cc("test2@rhaco.org");
$mail->cc("test3@rhaco.org","ghi");
eq(array(
		'test1@rhaco.org' => '"=?ISO-2022-JP?B?YWJj?=" <test1@rhaco.org>',
		'test2@rhaco.org' => '"test2@rhaco.org" <test2@rhaco.org>',
		'test3@rhaco.org' => '"=?ISO-2022-JP?B?Z2hp?=" <test3@rhaco.org>',
),$mail->cc());


$mail = new \org\rhaco\net\mail\Mail();
$mail->bcc("test1@rhaco.org","abc");
$mail->bcc("test2@rhaco.org");
$mail->bcc("test3@rhaco.org","ghi");
eq(array(
		'test1@rhaco.org'=>'"=?ISO-2022-JP?B?YWJj?=" <test1@rhaco.org>',
		'test2@rhaco.org'=>'"test2@rhaco.org" <test2@rhaco.org>',
		'test3@rhaco.org'=>'"=?ISO-2022-JP?B?Z2hp?=" <test3@rhaco.org>',
),$mail->bcc());


$mail = new \org\rhaco\net\mail\Mail();
$mail->return_path("test1@rhaco.org");
$mail->return_path("test2@rhaco.org");
eq("test2@rhaco.org",$mail->return_path());



$mail = new \org\rhaco\net\mail\Mail();
$mail->subject("改行は\r\n削除される");
eq("=?ISO-2022-JP?B?GyRCMn45VCRPOm89fCQ1JGwkaxsoQg==?=", $mail->subject());


