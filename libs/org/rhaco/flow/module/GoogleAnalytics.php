<?php
namespace org\rhaco\flow\module;
/**
 * Google analyticsのJSを埋め込む
 * @author tokushima
 *
 */
class GoogleAnalytics{
	public function before_template(&$src){
		if(strpos($src,'rt:ga') !== false){
			$base_account = \org\rhaco\Conf::get('account');
			while(\org\rhaco\Xml::set($tag,$src,'rt:ga')){
				$account = $tag->in_attr('account',$base_account);
				if(empty($account)){
					$func = '';
				}else{
					$func = sprintf(<<< _JS_

 <script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '%s']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();	
</script>
_JS_
								,$account);
				}
				$src = str_replace($tag->plain(),$func,$src);
			}
		}
	}	
}