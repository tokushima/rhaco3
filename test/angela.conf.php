<?php
return array(
	'urls'=>\org\rhaco\Dt::get_urls(),
	'setup_func'=>function(){
		\org\rhaco\Exceptions::clear();
	}
);

