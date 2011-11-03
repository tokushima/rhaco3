<?php
namespace test;

class SampleMediaFlow extends \org\rhaco\flow\parts\RequestFlow{
	public function index(){
		if($this->is_vars('view')){
			$theme = 'default';
			switch($this->in_vars('view')){
				case 'red':
				case 'blue':
					$theme = $this->in_vars('view');
			}
			$this->theme($theme);
		}
	}
	public function hoge(){
	}
}

