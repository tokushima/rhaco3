<?php
namespace org\rhaco\Dt;

class Replace{
	/**
	 * @module org.rhaco.Template
	 * @param string $src
	 */
	public function after_template(&$src){
		$src = str_replace("{\$t.package_method_url('class_info',","{\$f.class_html_filename(",$src);
		$src = str_replace("{\$t.package_method_url('method_info',","{\$f.method_html_filename(",$src);
	}
}