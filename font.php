<?php
include('rhaco3.php');

$text = 'hogehoge'.PHP_EOL.'ほげほげ'.PHP_EOL.'HOGEHOGE';
$font_path = '/System/Library/Fonts/ヒラギノ角ゴ ProN W3.otf';
$filename = '/Users/tokushima/Documents/workspace/_/lion.png';

list($width,$height,$char) = \org\rhaco\io\Image::text_size($font_path, 48, $text,100,100);

$img = new \org\rhaco\io\Image($width,$height+10,'#000000');

//$img = \org\rhaco\io\Image::load('/Users/tokushima/Documents/workspace/_/lion.png');
$img->set_text($text,0,10,$font_path,48,'#ff0000',100,100);
//$img->grayscale();
//$img->halftone(4);

$img->output('jpg');

