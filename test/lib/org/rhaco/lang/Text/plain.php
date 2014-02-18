<?php
$text = \org\rhaco\lang\Text::plain('
		aaa
		bbb
');
eq("aaa\nbbb",$text);

$text = \org\rhaco\lang\Text::plain("hoge\nhoge");
eq("hoge\nhoge",$text);

$text = \org\rhaco\lang\Text::plain("hoge\nhoge\nhoge\nhoge");
eq("hoge\nhoge\nhoge\nhoge",$text);
