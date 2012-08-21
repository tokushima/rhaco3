<?php
namespace org\rhaco\Dt;

class Formatter{
	/**
	 * @module org.rhaco.Template
	 */
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		$src = str_replace('{$f.docimg(','{$t.package_method_url(\'document_media\',',$src);
		$obj->set($src);
	}
}