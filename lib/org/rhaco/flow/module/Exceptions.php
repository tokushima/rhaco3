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
				$var = $tag->in_attr('var','rtinvalid_var'.uniqid(''));
				if(!isset($param[0]) || $param[0] !== '$') $param = '"'.$param.'"';
				$value = $tag->value();
				$tagtype = $tag->in_attr('tag');
				$stag = (empty($tagtype)) ? '' : '<'.$tagtype.' class="%s">';
				$etag = (empty($tagtype)) ? '' : '</'.$tagtype.'>';

				if(empty($value)){
					$varnm = 'rtinvalid_varnm'.uniqid('');
					$value = sprintf("<rt:loop param=\"%s\" var=\"%s\">\n"
										."%s{\$%s.getMessage()}%s"
									."</rt:loop>\n",$var,$varnm,sprintf($stag,$tag->in_attr('class','exception')),$varnm,$etag);
				}
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php if(\\org\\rhaco\\Exceptions::has(%s)){ ?>"
										."<?php \$%s = \\org\\rhaco\\Exceptions::to_array(%s); ?>" // TODO 暫定
										.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$value)
									."<?php } ?>"
									,$param
									,$var,$param
							),
							$src);
			}
		}
	}
	/**
	 * @module org.rhaco.Template
	 * @param org.rhaco.lang.String $obj
	 */
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		$this->replace('rt:exceptions',$src);
		$this->replace('rt:invalid',$src);
		$obj->set($src);
	}
}
