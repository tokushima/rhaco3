<?php
namespace test;

class WebTest{
	public function get(){
		if(isset($_GET)){
			foreach($_GET as $k => $v){
				print($k.'=>'.$v.PHP_EOL);
			}
			exit;
		}
	}
}