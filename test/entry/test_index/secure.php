<?php

$pre = <<< PRE
<html>
<body>
	<a href="http://rhaco.org"></a>
	<a href="http://localhost:8000/test_index.php/"></a>
	<a href="https://localhost:8000/test_index.php/secure"></a>
	<a href="https://localhost:8000/test_login.php/secure"></a>
	<img src="http://localhost/images/abc.jpg" />
	<img src="http://localhost:8000/resources/media/images/def.jpg" />
	<img src="http://localhost:8000/resources/media/images/def.jpg" />
</body>
</html>
PRE;

meq('https://',test_map_url('test_login::secure'));

$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::to_secure'));
eq(200,$b->status());
eq($pre,$b->body());



