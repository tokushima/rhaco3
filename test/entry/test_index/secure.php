<?php

$pre = <<< PRE
<html>
<body>
	<a href="http://rhaco.org">no</a>
	<a href="http://localhost/rhaco3/test_index/">no</a>
	<a href="https://localhost/rhaco3/test_index/secure">yes</a>
	<a href="https://localhost/rhaco3/test_login/secure">yes</a>
	<img src="http://localhost/images/abc.jpg" />no
	<img src="https://localhost/rhaco3/resources/media/images/def.jpg" />yes
	<img src="https://localhost/rhaco3/resources/media/images/def.jpg" />yes
</body>
</html>
PRE;


$b = new \chaco\Browser();
$b->do_get(test_map_url('test_login::secure'));
eq(200,$b->status());
meq('https://',$b->url());
eq($pre,$b->body());


$b = new \chaco\Browser();
$b->do_get(test_map_url('test_index::secure'));
eq(200,$b->status());
meq('https://',$b->url());
eq($pre,$b->body());



