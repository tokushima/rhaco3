<?php
/**
 * HTMLを書き出す
 * @param string $path
 */
if(!isset($path)) $path = getcwd().'/contents/';

foreach(\Pagen::publish($path) as $p){
	\cmdman\Std::println('Written '.$p);
}