<?php
namespace test;

class SampleFlow extends \org\rhaco\flow\parts\RequestFlow{
	/**
	 * @automap
	 */
	public function index(){
	}
	/**
	 * 
	 * @automap
	 */
	public function hoge(){
	}
	/**
	 * 
	 * @automap
	 */
	public function upload_value(){
		$value1 = $this->in_vars('value1');
		
		$this->rm_vars();
		$this->vars('get_data1',$value1);
	}
	/**
	 * 
	 * @automap
	 */
	public function upload_file(){
		if($this->is_post()){
			$file_info1 = $this->in_files('upfile1');			
			$this->rm_vars();
			
			$mv = getcwd().'/'.md5(microtime());			
			$this->vars('original_name1',$this->file_original_name($file_info1));
			$this->vars('size1',filesize($this->file_path($file_info1)));
			$this->vars('has1',$this->has_file($file_info1));

			$this->move_file($file_info1,$mv);
			$this->vars('mv1',is_file($mv));
			$this->vars('mv_size1',filesize($mv));
			$this->vars('data1',file_get_contents($mv));
			unlink($mv);
		}
		
	}
	/**
	 * 
	 * @automap
	 */
	public function upload_multi(){
		if($this->is_post()){
			$value1 = $this->in_vars('value1');
			$value2 = $this->in_vars('value2');
			$file_info1 = $this->in_files('upfile1');
			$file_info2 = $this->in_files('upfile2');
			
			$this->rm_vars();
			$this->vars('get_data1',$value1);
			$this->vars('get_data2',$value2);
			
			$mv = getcwd().'/'.md5(microtime());			
			$this->vars('original_name1',$this->file_original_name($file_info1));
			$this->vars('size1',filesize($this->file_path($file_info1)));
			$this->vars('has1',$this->has_file($file_info1));

			$this->move_file($file_info1,$mv);
			$this->vars('mv1',is_file($mv));
			$this->vars('mv_size1',filesize($mv));
			$this->vars('data1',file_get_contents($mv));
			unlink($mv);
			
			$mv = getcwd().'/'.md5(microtime());			
			$this->vars('original_name2',$this->file_original_name($file_info2));
			$this->vars('size2',filesize($this->file_path($file_info2)));
			$this->vars('has2',$this->has_file($file_info2));

			$this->move_file($file_info2,$mv);
			$this->vars('mv2',is_file($mv));
			$this->vars('mv_size2',filesize($mv));
			$this->vars('data2',file_get_contents($mv));
			unlink($mv);
		}
	}
}
