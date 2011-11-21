<?php
/**
 * core export
 */
$ref = new ReflectionClass('Rhaco3');
$rhaco3 = file_get_contents($ref->getFileName());
list($simple) = explode("\n##",$rhaco3);
$dir = dirname($ref->getFileName());
file_put_contents($dir.'/bin/rhaco3.php',$rhaco3);
file_put_contents($dir.'/bin/rhaco3_min.php',$simple);

print('Writen '.$dir.'/bin/rhaco3.php'.PHP_EOL);
print('Writen '.$dir.'/bin/rhaco3_min.php'.PHP_EOL);
