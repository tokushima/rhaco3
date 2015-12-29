<?php
namespace org\rhaco\flow\module;
/**
 * htmlのフィルタ
 *  - 自動エスケープ処理
 * @author tokushima
 */
class HtmlFilter{
	/**
	 * @module org.rhaco.Template
	 * @param \org\rhaco\lang\Str $obj
	 */
	public function before_exec_template(\org\rhaco\lang\Str $obj){
		$src = $obj->get();
		if(preg_match_all('/\$_t_->print_variable\((.+?)\);/ms',$src,$match)){
			$src = str_replace($match[0],array_map(array($this,'add_escape'),$match[1]),$src);
			$obj->set($src);
		}
	}
	private function add_escape($value){
		if(strpos($value,'$_t_->htmlencode(') === false
			&& strpos($value,'$t->html(') === false
			&& strpos($value,'$t->text(') === false
			&& strpos($value,'$t->noop(') !== 0
		){
			$value = '$_t_->htmlencode('.$value.')';
		}
		return '$_t_->print_variable('.$value.');';
	}
}