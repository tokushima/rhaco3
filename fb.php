<?php
include('rhaco3.php');

//$fb_client_id = '';
//$fb_client_secret = '';

$fb = new \org\rhaco\service\Facebook($fb_client_id,$fb_client_secret);
$album_list = array();
foreach($fb->require_permissions('user_photos,friends_photos')->albums() as $a){
	$album_list[] = array('name'=>$a['name'],'photos'=>$fb->photos($a['id']));
}
$me = $fb->me();

$template = new \org\rhaco\Template();
$template->vars('name',$me['name']);
$template->vars('album_list',$album_list);
$template->output(__FILE__);
?>
<rt:template>
<html>
<head>
	<meta charset="utf-8" />
</head>
<body>
<h1>{$name}</h1>

<rt:loop param="{$album_list}" var="album">
	<h2>{$album['name']}</h2>
	<div style="float: both;">
		<rt:loop param="{$album['photos']}" var="p">
			<div style="float: left;">
				<a href="{$p['source']}"><img src="{$p['picture']}" border="0" /></a><br />
				{$p['width']}x{$p['height']}
			</div>
		</rt:loop>
		<div style="clear: both;"></div>
	</div>
</rt:loop>

</body>
</html>
</rt:template>
