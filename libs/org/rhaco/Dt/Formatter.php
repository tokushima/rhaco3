<?php
namespace org\rhaco\Dt;

class Formatter{
	/**
	 * @module org.rhaco.Template
	 * @param string $src
	 */
	public function after_exec_template(&$src){
		$src = str_replace('<table>','<table class="table table-striped table-bordered table-condensed">',$src);
	}
}