<?php
/**
 * 開発ツール
 */


function template($super_html,$vars=array()){
	$template = new \org\rhaco\Template();
	$template->template_super($super_html);
	$template->set_object_module(new \org\rhaco\flow\parts\Developer\Replace());
	$template->set_object_module(new \org\rhaco\flow\parts\Developer\Formatter());
	foreach($vars as $k => $v) $template->vars($k,$v);
	$template->vars('t',new \org\rhaco\flow\module\Helper());
	$template->vars('f',new \org\rhaco\flow\parts\Developer\Helper());
	return $template;
}