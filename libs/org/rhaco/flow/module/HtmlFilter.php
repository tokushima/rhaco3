<?php
namespace org\rhaco\flow\module;
/**
 * htmlのフィルタ
 *  - 自動エスケープ処理
 * @author tokushima
 */
class HtmlFilter{
	public function before_exec_template(&$src){
		if(preg_match_all('/@print\((.+?)\);/ms',$src,$match)){
			$src = str_replace($match[0],array_map(array($this,'add_escape'),$match[1]),$src);
		}
	}
	private function add_escape($value){
		if(!(
				strpos($value,'$_t_->htmlencode(') === 0
				|| strpos($value,'$t->map_url(') === 0
				|| strpos($value,'$t->htmlencode(') === 0
				|| strpos($value,'$t->html(') === 0
				|| strpos($value,'$t->text(') === 0
				|| strpos($value,'$t->noop(') === 0
		)){
			$value = '$_t_->htmlencode('.$value.')';
		}
		return '@print('.$value.');';
	}
}