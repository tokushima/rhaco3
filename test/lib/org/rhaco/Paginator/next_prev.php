<?php
$p = new \org\rhaco\Paginator(10,1,100);
eq(2,$p->next());


$p = new \org\rhaco\Paginator(10,2,100);
eq(1,$p->prev());


$p = new \org\rhaco\Paginator(10,1,100);
eq(true,$p->is_next());
$p = new \org\rhaco\Paginator(10,9,100);
eq(true,$p->is_next());
$p = new \org\rhaco\Paginator(10,10,100);
eq(false,$p->is_next());


$p = new \org\rhaco\Paginator(10,1,100);
eq(false,$p->is_prev());
$p = new \org\rhaco\Paginator(10,9,100);
eq(true,$p->is_prev());
$p = new \org\rhaco\Paginator(10,10,100);
eq(true,$p->is_prev());


