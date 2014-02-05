<?php

$p = new \org\rhaco\Paginator(10,3,100);
$p->query_name("page");
$p->vars("abc","DEF");
eq("abc=DEF&page=2",$p->query_prev());


$p = new \org\rhaco\Paginator(10,3,100);
$p->query_name("page");
$p->vars("abc","DEF");
eq("abc=DEF&page=4",$p->query_next());


$p = new \org\rhaco\Paginator(10,3,100);
$p->query_name("page");
$p->vars("abc","DEF");
$p->order("bbb");
eq("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));
	
$p = new \org\rhaco\Paginator(10,3,100);
$p->query_name("page");
$p->vars("abc","DEF");
$p->vars("order","bbb");
eq("abc=DEF&order=aaa&porder=bbb",$p->query_order("aaa"));



$p = new \org\rhaco\Paginator(10,1,100);
eq("page=3",$p->query(3));



