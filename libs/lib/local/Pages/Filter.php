<?php
namespace local\Pages;

class Filter{
	private $path;
	
	public function __construct($path=null){
		$this->path = $path;
	}
	public function link($name){
		if(!empty($this->path)){
			if($name == 'index.html'){
				return str_repeat('../',substr_count($this->path,'/')+(($this->path == 'index.html') ? 0 : 1)).$name;
			}
			return str_repeat('../',substr_count($this->path,'/')+(($this->path == 'index.html') ? 0 : 1)).'contents/'.$name;
		}
		return '?contents='.$name;
	}
}