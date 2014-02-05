<?php
$src = pre('
		<script src="abc.js"></script>
		<script language="javascript">
		var i = "{$abc}";
		var img = "<img src=\'hoge.jpg\' />";
		</script>
		<img src=\'hoge.jpg\' />
		');
$result = pre('
		<script src="http://localhost/hoge/media/abc.js"></script>
		<script language="javascript">
		var i = "123";
		var img = "<img src=\'hoge.jpg\' />";
		</script>
		<img src=\'http://localhost/hoge/media/hoge.jpg\' />
		');
$t = new \org\rhaco\Template('http://localhost/hoge/media');
$t->vars("abc",123);
eq($result,$t->get($src));
