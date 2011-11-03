<?php
/**
 * Installs one or more PEAR packages.
 * @param string $value package path [pear.php.net/PEAR]
 * @param string $state state [stable]
 */
if(!isset($_ENV['params']['value'])) throw new RuntimeException('Invalid argument');
$output_path = (isset($_ENV['PATH_EXTLIBS'])) ? $_ENV['PATH_EXTLIBS'] : getcwd();
if(!is_file($output_path.'PEAR.php')) \org\rhaco\Pear::install('pear.php.net/PEAR','stable',$output_path);	
\org\rhaco\Pear::install($_ENV['params']['value'],(isset($_ENV['params']['state']) ? $_ENV['params']['state'] : 'stable'),$output_path);
print('installed '.$_ENV['params']['value'].PHP_EOL);
