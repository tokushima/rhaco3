<?php
namespace test;

class WebTest{
	static public function get_url($url=null){
		return \org\rhaco\Conf::get('base_url').$url;
	}
}