<?php
namespace org\rhaco\flow\module;
use \org\rhaco\Xml;
/**
 * html表現の拡張
 * twotter bootstrap のPagination風
 * @author tokushima
 *
 */
class TwitterBootstrapExtHtml{
	/**
	 * @module org.rhaco.Template
	 */
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();

		if(\org\rhaco\Xml::set($tag,$src,'body')){			
			foreach($tag->in(array('pre','cli','tree')) as $b){
				$plain = $b->plain();
				$tag = strtolower($b->name());
				$b->escape(false);
				$caption = $b->in_attr('caption');
				$b->rm_attr('caption');

				if($tag == 'cli'){
					$b->name('pre');
					$b->attr('style','background-color:#fff; color:#000; border-color:#000;');
				}else if($tag == 'tree'){
					$b->name('pre');
					$b->attr('style','padding: 5px; line-height: 20px');
					$b->attr('class','prettyprint lang-c');
				}else{
					$b->attr('class','prettyprint');
				}
				if(empty($caption)) $b->attr('style','margin-top: 20px; '.$b->in_attr('style'));
				$value = \org\rhaco\lang\Text::plain($b->value());
				$value = preg_replace("/<(rt:.+?)>/ms","&lt;\\1&gt;",$value);
				$value = str_replace(array('<php>','</php>'),array('<?php','?>'),$value);
				if(empty($value)) $value = PHP_EOL;
				
				if($tag == 'tree'){
					$tree = array();
					$len = 0;
					foreach(explode("\n",$value) as $k => $line){
						if(preg_match("/^(\s*)([\.\w]+):(.+)$/",$line,$m)){
							$tree[$k] = array(strlen(str_replace("\t",' ',$m[1])),trim($m[2]),trim($m[3]));
							$tree[$k][3] = strlen($tree[$k][1]);
							if($len < ($tree[$k][3] + $tree[$k][0])) $len = $tree[$k][3] + $tree[$k][0];
						}
					}
					if(!empty($caption)) $value = $caption.PHP_EOL;
					$value .= '.'.PHP_EOL;
					$last = sizeof($tree) - 1;
					foreach($tree as $k => $t){
						$value .= str_repeat('| ',$t[0]);
						$value .= (($t[0] > 0 && isset($tree[$k+1]) && $tree[$k+1][0] < $t[0]) || $k == $last) ? '`' : '|';
						$value .= '-- '.$t[1].str_repeat(' ',$len - $t[3] - ($t[0]*2) + 4).' .. '.$t[2].PHP_EOL;
					}
					$b->value($value);
					$plain = $b->get();
				}else{
					$value = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value);
					$value = str_replace("\t","&nbsp;&nbsp;",$value);
					$b->value($value);
					$plain = str_replace(array('$','='),array('__RTD__','__RTE__'),$b->get());
					if(!empty($caption)) $plain = '<div style="margin-top:20px; color:#7a43b6; font-weight: bold;">'.$caption.'</div>'.$plain;
				}
				$src = str_replace($b->plain(),$plain,$src);
			}
		}
		$obj->set($src);
	}
	/**
	 * @module org.rhaco.Template
	 */
	public function after_exec_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		$src = preg_replace("/<alert>(.+?)<\/alert>/ms",'<p class="alert alert-error">\\1</p>',$src);
		$src = preg_replace("/<information>(.+?)<\/information>/ms",'<p class="alert alert-info">\\1</p>',$src);
		$src = preg_replace("/!!!(.+?)!!!/ms",'<span style="font-weight:bold">\\1</span>',$src);
		$src = preg_replace("/##(.+?)##/ms",'<span class="label label-warning">\\1</span>',$src);
		$src = str_replace('<table>','<table class="table table-striped table-bordered table-condensed">',$src);
		$src = str_replace(array('__RTD__','__RTE__'),array('$','='),$src);
		$obj->set($src);
	}
}