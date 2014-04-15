<?php
/**
 * media_urlの置換処理とsecureが指定された場合の挙動
 */
$src = <<< PRE
<html>
<body>
	<a href="abc/def.html">link</a>
	<img src="media/xyz.jpg" />
</body>
</html>
PRE;

$result = <<< PRE
<html>
<body>
	<a href="http://localhost/resources/abc/def.html">link</a>
	<img src="http://localhost/resources/media/xyz.jpg" />
</body>
</html>
PRE;

$t = new \org\rhaco\Template();
$t->media_url('http://localhost/resources');
eq($result,$t->get($src));


$result_secure = <<< PRE
<html>
<body>
	<a href="http://localhost/resources/abc/def.html">link</a>
	<img src="https://localhost/resources/media/xyz.jpg" />
</body>
</html>
PRE;

$t = new \org\rhaco\Template();
$t->media_url('http://localhost/resources');
$t->secure(true);
eq($result_secure,$t->get($src)); // imgはhttpsになる





// URLが関数の場合
$link = function($p){
	return 'http://localhost/resources/'.$p;
};
$src = <<< PRE
<html>
<body>
	<a href="{$link('abc/def.html')}">link</a>
	<img src="{$link('media/xyz.jpg')}" />
</body>
</html>
PRE;

$result = <<< PRE
<html>
<body>
	<a href="http://localhost/resources/abc/def.html">link</a>
	<img src="http://localhost/resources/media/xyz.jpg" />
</body>
</html>
PRE;

$t = new \org\rhaco\Template();
$t->vars('link',$link);
eq($result,$t->get($src));


$result_secure = <<< PRE
<html>
<body>
	<a href="http://localhost/resources/abc/def.html">link</a>
	<img src="http://localhost/resources/media/xyz.jpg" />
</body>
</html>
PRE;

$t = new \org\rhaco\Template();
$t->secure(true);
$t->vars('link',$link);
eq($result_secure,$t->get($src)); // httpsには置換されない


