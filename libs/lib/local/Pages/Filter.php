<?php
namespace local\Pages;

class Filter{
	private $export;
	private $path;
	
	public function __construct($export=false,$path=null){
		$this->export = $export;
		$this->path = $path;
	}
	public function link($name){
		if($this->export){
			if($name == 'index.html'){
				return str_repeat('../',substr_count($this->path,'/')+(($this->path == 'index.html') ? 0 : 1)).$name;
			}
			return str_repeat('../',substr_count($this->path,'/')+(($this->path == 'index.html') ? 0 : 1)).'contents/'.$name;
		}
		return '?contents='.$name;
	}
}