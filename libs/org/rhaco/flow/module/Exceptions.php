<?php
namespace org\rhaco\flow\module;
use \org\rhaco\Xml;
/**
 * Exceptionのhtml表現
 * @author tokushima
 */
class Exceptions{
	private function replace($name,&$src){
		if(strpos($src,$name) !== false){
			while(Xml::set($tag,$src,$name)){
				$param = $tag->in_attr('param');
				$id = $tag->in_attr('id','rtinvalid_id'.uniqid(''));
				$var = $tag->in_attr('var','rtinvalid_var'.uniqid(''));
				$messages = $tag->in_attr('messages','rtinvalid_mes'.uniqid(''));
				if(!isset($param[0]) || $param[0] !== '$') $param = '"'.$param.'"';
				$value = $tag->value();
				$tagtype = $tag->in_attr('tag');
				$stag = (empty($tagtype)) ? '' : '<'.$tagtype.' class="%s">';
				$etag = (empty($tagtype)) ? '' : '</'.$tagtype.'>';

				if(empty($value)){
					$varnm = 'rtinvalid_varnm'.uniqid('');
					$value = sprintf("<rt:loop param=\"%s\" var=\"%s\">\n"
										."%s{\$%s}%s"
									."</rt:loop>\n",$messages,$varnm,sprintf($stag,$tag->in_attr('class','exception')),$varnm,$etag);
				}
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php if(\\org\\rhaco\\Exceptions::has(%s)){ ?>"
										."<?php \$%s = \\org\\rhaco\\Exceptions::gets(%s); ?>"
										."<?php \$%s = \\org\\rhaco\\Exceptions::messages(%s); ?>"
										."<?php \$%s = \\org\\rhaco\\Exceptions::id(); ?>"
										."%s"
									."<?php } ?>"
									,$param
									,$var,$param
									,$messages,$param
									,$id
									,$value
							),
							$src);
			}
		}
	}
	/**
	 * @module org.rhaco.Template
	 * @param string $src
	 */
	public function before_template(&$src){
		$this->replace('rt:exceptions',$src);
		$this->replace('rt:invalid',$src);
	}
}
