<?php
namespace org\rhaco\Dt;

class Formatter{
	/**
	 * @module org.rhaco.Template
	 */
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();

		if(\org\rhaco\Xml::set($tag,$src,'body')){
			foreach($tag->in(array('docimg')) as $b){
				$plain = $b->plain();
				$tag = strtolower($b->name());
				
				if($tag == 'docimg'){
					$b->name('img');
					$get = $b->get();
					$plain = str_replace($b->in_attr('src'),sprintf('{$t.package_method_url("document_media","%s")}',$b->in_attr('src')),$b->get());
				}
				$src = str_replace($b->plain(),$plain,$src);
			}
		}		
		$obj->set($src);
	}
}