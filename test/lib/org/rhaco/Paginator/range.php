<?php
$p = new \org\rhaco\Paginator(4,1,3);
eq(1,$p->first());
eq(1,$p->last());
eq(false,$p->has_range());
	
$p = new \org\rhaco\Paginator(4,2,3);
eq(1,$p->first());
eq(1,$p->last());
eq(false,$p->has_range());
	
$p = new \org\rhaco\Paginator(4,1,10);
eq(1,$p->first());
eq(3,$p->last());
eq(true,$p->has_range());
	
$p = new \org\rhaco\Paginator(4,2,10);
eq(1,$p->first());
eq(3,$p->last());
eq(true,$p->has_range());
