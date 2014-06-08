<?php
namespace org\rhaco\flow\module;
use \org\rhaco\Xml;
/**
 * Paginatorのhtml表現
 * twotter bootstrap のPagination風
 * @author tokushima
 *
 */
class TwitterBootstrapPagination{
	/**
	 * @module org.rhaco.Template
	 * @param org.rhaco.lang.String $obj
	 */
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		if(strpos($src,'rt:paginator') !== false){
			while(Xml::set($tag,$src,'rt:paginator')){
				$param = '$'.$tag->in_attr('param','paginator');
				$func = sprintf('<?php try{ ?><?php if(%s instanceof \\org\\rhaco\\Paginator){ ?>',$param);
				$func .= '<div class="pagination"><ul>';
				$uniq = uniqid('');
				$name = '$__pager__'.$uniq;
				$counter_var = '$__counter__'.$uniq;
				$href = $tag->in_attr('href','?');
				$stag = '<li%s>';
				$etag = '</li>';
				$navi = array_change_key_case(array_flip(explode(',',$tag->in_attr('navi','prev,next,first,last,counter'))));
				$counter = $tag->in_attr('counter',10);
				$total = '$__pagertotal__'.$uniq;
				if(isset($navi['prev'])) $func .= sprintf('<?php if(%s->is_prev()){ ?><li class="prev"><a href="%s{%s.query_prev()}" rel="prev"><?php }else{ ?><li class="prev disabled"><a><?php } ?>%s</a></li>',$param,$href,$param,'&larr; Previous');
				if(isset($navi['first'])) $func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_first(%d)){ ?><li><a href="%s{%s.query(%s.first())}">{%s.first()}</a></li><li class="disabled"><a>...</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
				if(isset($navi['counter'])){
					$func .= sprintf('<?php if(!%s->is_dynamic()){ ?>',$param);
					$func .= sprintf('<?php %s = %s; if(!empty(%s)){ ?>',$total,$param,$total);
					$func .= sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var);
						$func .= sprintf('<?php if(%s == %s->current()){ ?>',$counter_var,$param);
							$func .= sprintf('<li class="active"><a>{%s}</a></li>',$counter_var);
						$func .= '<?php }else{ ?>';
							$func .= sprintf('<li><a href="%s{%s.query(%s)}">{%s}</a></li>',$href,$param,$counter_var,$counter_var);
						$func .= '<?php } ?>';
					$func .= '<?php } ?>';
					$func .= '<?php } ?>';
					$func .= '<?php } ?>';
				}
				if(isset($navi['last'])) $func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_last(%d)){ ?><li class="disabled"><a>...</a></li><li><a href="%s{%s.query(%s.last())}">{%s.last()}</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
				if(isset($navi['next'])) $func .= sprintf('<?php if(%s->is_next()){ ?><li class="next"><a href="%s{%s.query_next()}" rel="next"><?php }else{ ?><li class="next disabled"><a><?php } ?>%s</a></li>',$param,$href,$param,'Next &rarr;',$etag);

				$func .= "<?php } ?><?php }catch(\\Exception \$e){} ?>";
				$func .= '</ul></div>';
				$src = str_replace($tag->plain(),$func,$src);
			}
		}
		$obj->set($src);
	}
}
