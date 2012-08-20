<?php
include('rhaco3.php');

$req = new \org\rhaco\Request();

if(!$req->is_vars('text')){
	throw new \LogicException('-text ???');
}
$page = 1;
while(true){
	$b = new \org\rhaco\net\Http();
	$b->vars('method','flickr.photos.search');
	$b->vars('api_key',$req->in_vars('key',(defined('FLICKR_API_KEY') ? constant('FLICKR_API_KEY') : null)));
	$b->vars('text',$req->in_vars('text','tolot'));
	$b->vars('format','rest');
	$b->vars('page',$page);
	$b->vars('extras','url_o');
	$b->do_get('http://api.flickr.com/services/rest/');

	if(\org\rhaco\Xml::set($xml,$b->body(),'photos')){
		$count = 0;
		foreach($xml->in('photo') as $p){

			$url_o = $p->in_attr('url_o');
			if(!empty($url_o)){
				print($url_o.PHP_EOL);
				$b->do_download($url_o,getcwd().'/photos/'.$p->in_attr('id').'.jpg');
			}
			$count++;
		}
		if($count == 0) break;
	}
	$page++;
}




