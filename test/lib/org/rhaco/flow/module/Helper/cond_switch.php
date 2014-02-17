<?php
$t = new \org\rhaco\flow\module\Helper();
eq('on',$t->cond_switch(true,'on','off'));
eq('off',$t->cond_switch(false,'on','off'));
eq('off',$t->cond_switch('','on','off'));
eq('off',$t->cond_switch(0,'on','off'));
eq('off',$t->cond_switch(false,'on','off'));
eq('off',$t->cond_switch(array(),'on','off'));
eq('on',$t->cond_switch('1','on','off'));
eq('on',$t->cond_switch(1,'on','off'));
eq('on',$t->cond_switch(true,'on','off'));
eq('on',$t->cond_switch(array(1),'on','off'));

