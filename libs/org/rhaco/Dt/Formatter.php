<?php
namespace org\rhaco\Dt;

class Formatter{
	/**
	 * @module org.rhaco.Template
	 * @param org.rhaco.lang.String $obj
	 */
	public function after_exec_template(\org\rhaco\lang\String $obj){
		$src = str_replace('<table>','<table class="table table-striped table-bordered table-condensed">',$obj->get());
		$obj->set($src);
	}
}