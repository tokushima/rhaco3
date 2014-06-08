<?php
$b = new \testman\Browser();

$b->do_get(test_map_url('test_index::csrf'));
eq(200,$b->status());
meq('<result>',$b->body());

$b->do_post(test_map_url('test_index::csrf'));
eq(403,$b->status());
meq('<error>',$b->body());



$b->do_get(test_map_url('test_index::csrf'));
eq(200,$b->status());
meq('<result>',$b->body());

$no = null;
if(\org\rhaco\Xml::set($xml,$b->body(),'csrftoken')){
	$no = $xml->value();
}
neq(null,$no);

$b->vars('csrftoken',$no);
$b->do_post(test_map_url('test_index::csrf'));
eq(200,$b->status());
meq('<result>',$b->body());


$b->do_get(test_map_url('test_index::csrf_template'));
eq(200,$b->status());
meq('<form><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="post"><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="get"><input type="hidden" name="csrftoken"',$b->body());
meq(sprintf('<form action="%s"><input type="hidden" name="csrftoken"',test_map_url('test_index::csrf')),$b->body());
meq('<form action="http://localhost"><input type="text" name="aaa" /></form>',$b->body());
