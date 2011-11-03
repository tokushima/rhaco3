<?php
$rhaco3 = file_get_contents(__DIR__.'/rhaco3.php');
list($simple) = explode("\n##",$rhaco3);
file_put_contents(__DIR__.'/bin/rhaco3.php',$rhaco3);
file_put_contents(__DIR__.'/bin/rhaco3_'.date('Ymd').'.php',$rhaco3);
file_put_contents(__DIR__.'/bin/rhaco3_min.php',$simple);
file_put_contents(__DIR__.'/bin/rhaco3_'.date('Ymd').'_min.php',$simple);
