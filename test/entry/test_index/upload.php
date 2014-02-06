<?php
$b = b();
$b->vars('value1','abcd');
$b->do_post(test_map_url('test_index::upload_value'));
\org\rhaco\Xml::set($xml,$b->body(),'result');


eq('abcd',$xml->f('get_data1.value()'));

$b->file_vars('upfile1',__FILE__);
$b->do_post(test_map_url('test_index::upload_file'));
\org\rhaco\Xml::set($xml,$b->body(),'result');
eq(basename(__FILE__),$xml->f('original_name1.value()'));
eq(filesize(__FILE__),(int)$xml->f('size1.value()'));
eq('true',$xml->f('mv1.value()'));
eq(filesize(__FILE__),(int)$xml->f('mv_size1.value()'));
eq(file_get_contents(__FILE__),$xml->f('data1.value()'));


$b->vars('value1','abcd');
$b->file_vars('upfile1',__FILE__);
$b->vars('value2','efg');
$b->file_vars('upfile2',__FILE__);
$b->do_post(test_map_url('test_index::upload_multi'));
\org\rhaco\Xml::set($xml,$b->body(),'result');
eq(basename(__FILE__),$xml->f('original_name1.value()'));
eq(filesize(__FILE__),(int)$xml->f('size1.value()'));
eq('true',$xml->f('mv1.value()'));
eq(filesize(__FILE__),(int)$xml->f('mv_size1.value()'));
eq(file_get_contents(__FILE__),$xml->f('data1.value()'));
eq(basename(__FILE__),$xml->f('original_name2.value()'));
eq(filesize(__FILE__),(int)$xml->f('size2.value()'));
eq('true',$xml->f('mv2.value()'));
eq(filesize(__FILE__),(int)$xml->f('mv_size2.value()'));
eq(file_get_contents(__FILE__),$xml->f('data2.value()'));
eq('abcd',$xml->f('get_data1.value()'));
eq('efg',$xml->f('get_data2.value()'));
