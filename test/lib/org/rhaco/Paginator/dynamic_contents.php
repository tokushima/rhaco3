<?php
$p = \org\rhaco\Paginator::dynamic_contents(2,'C');
$p->add('A');
$p->add('B');
$p->add('C');
$p->add('D');
$p->add('E');
$p->add('F');
$p->add('G');
eq('A',$p->prev());
eq('E',$p->next());
eq('page=A',$p->query_prev());
eq(array('C','D'),$p->contents());
eq(null,$p->first());
eq(null,$p->last());
