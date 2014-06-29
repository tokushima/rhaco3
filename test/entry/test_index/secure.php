<?php

$pre = <<< PRE
<html>
<body>
	<a href="http://rhaco.org">no</a>
	<a href="http://localhost:8000/test_index/">no</a>
	<a href="https://localhost:8000/test_index/secure">yes</a>
	<a href="https://localhost:8000/test_login/secure">yes</a>
	<img src="http://localhost/images/abc.jpg" />no
	<img src="http://localhost:8000/resources/media/images/def.jpg" />yes
	<img src="http://localhost:8000/resources/media/images/def.jpg" />yes
</body>
</html>
PRE;

meq('https://',test_map_url('test_login::secure'));

$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::to_secure'));
eq(200,$b->status());
eq($pre,$b->body());



