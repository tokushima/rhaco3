<?php
namespace org\rhaco\flow\module;
use \org\rhaco\Xml;
/**
 * Paginatorのhtml表現
 * @author tokushima
 *
 */
class Paginator{
	/**
	 * @module org.rhaco.Template
	 * @param string $src
	 */
	public function before_template(&$src){
		if(strpos($src,'rt:paginator') !== false){
			while(Xml::set($tag,$src,'rt:paginator')){
				$param = '$'.$tag->in_attr('param','paginator');
				$func = sprintf('<?php try{ ?><?php if(%s instanceof \\org\\rhaco\\Paginator){ ?>',$param);
				if($tag->value() != ''){
					$func .= $tag->value();
				}else{
					$uniq = uniqid('');
					$name = '$__pager__'.$uniq;
					$counter_var = '$__counter__'.$uniq;
					$tagtype = $tag->in_attr('tag');
					$href = $tag->in_attr('href','?');
					$stag = (empty($tagtype)) ? '' : '<'.$tagtype.' class="%s">';
					$etag = (empty($tagtype)) ? '' : '</'.$tagtype.'>';
					$navi = array_change_key_case(array_flip(explode(',',$tag->in_attr('navi','prev,next,first,last,counter'))));
					$counter = $tag->in_attr('counter',50);
					$total = '$__pagertotal__'.$uniq;
					if(isset($navi['prev'])) $func .= sprintf('<?php if(%s->is_prev()){ ?>%s<a href="%s{%s.query_prev()}">%s</a>%s<?php } ?>',$param,sprintf($stag,'prev'),$href,$param,'prev',$etag);
					if(isset($navi['first'])) $func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_first(%d)){ ?>%s<a href="%s{%s.query(%s.first())}">{%s.first()}</a>%s%s...%s<?php } ?>',$param,$param,$counter,sprintf($stag,'first'),$href,$param,$param,$param,$etag,sprintf($stag,'first_gt'),$etag);
					if(isset($navi['counter'])){
						$func .= sprintf('<?php if(!%s->is_dynamic()){ ?>',$param);
						$func .= sprintf('<?php %s = %s; if(!empty(%s)){ ?>',$total,$param,$total);
						$func .= sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var);
						$func .= sprintf('%s<?php if(%s == %s->current()){ ?><strong>{%s}</strong><?php }else{ ?><a href="%s{%s.query(%s)}">{%s}</a><?php } ?>%s',sprintf($stag,'count'),$counter_var,$param,$counter_var,$href,$param,$counter_var,$counter_var,$etag);
						$func .= '<?php } ?>';
						$func .= '<?php } ?>';
						$func .= '<?php } ?>';
					}
					if(isset($navi['last'])) $func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_last(%d)){ ?>%s...%s%s<a href="%s{%s.query(%s.last())}">{%s.last()}</a>%s<?php } ?>',$param,$param,$counter,sprintf($stag,'last_lt'),$etag,sprintf($stag,'last'),$href,$param,$param,$param,$etag);
					if(isset($navi['next'])) $func .= sprintf('<?php if(%s->is_next()){ ?>%s<a href="%s{%s.query_next()}">%s</a>%s<?php } ?>',$param,sprintf($stag,'next'),$href,$param,'next',$etag);
				}
				$func .= "<?php } ?><?php }catch(\\Exception \$e){} ?>";
				$src = str_replace($tag->plain(),$func,$src);
			}
		}
	}
}