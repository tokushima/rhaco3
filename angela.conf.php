<?php
return array(
	'urls'=>\org\rhaco\Dt::get_urls(),
	'setup'=>function(){
		\org\rhaco\Exceptions::clear();
	}
);

