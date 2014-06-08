<?php
namespace org\rhaco\Dt;

class Replace{
	/**
	 * @module org.rhaco.Template
	 * @param org.rhaco.lang.String $obj
	 */
	public function after_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		$src = str_replace("{\$t.package_method_url('class_info',","{\$f.class_html_filename(",$src);
		$src = str_replace("{\$t.package_method_url('method_info',","{\$f.method_html_filename(",$src);
		$src = str_replace("{\$t.package_method_url('class_module_info',","{\$f.module_html_filename(",$src);
		$obj->set($src);
	}
}