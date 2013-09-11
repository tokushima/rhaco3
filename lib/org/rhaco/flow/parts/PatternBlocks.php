<?php
namespace org\rhaco\flow\parts;
/**
 * リクエストされたファイルをテンプレートとして利用する
 * @author tokushima
 */
class PatternBlocks implements \org\rhaco\flow\FlowInterface{
	private $put_block;
	private $args;
	
	public function get_block(){
		return $this->put_block;
	}
	public function set_args($args){
		$this->args = $args;
	}
	public function set_maps($maps){}
	public function set_select_map_name($name){}
	/**
	 * リクエストされたファイルをテンプレートとして利用する
	 * @param string $filename
	 */
	public function select($filename){
		if(!empty($filename)){
			if($filename[0] === '/') $filename = substr($filename,1);
			$path = isset($this->args['path']) ? $this->args['path'] : null;
			if(!empty($path) && substr($path,-1) !== '/') $path = $path.'/';
			$this->put_block = $path.$filename;
		}
	}
	public function before(){}
	public function after(){}
	public function get_template_modules(){}
}