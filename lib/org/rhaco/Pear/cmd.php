<?php
/**
 * Installs one or more PEAR packages.
 * @param string $value package path [pear.php.net/PEAR]
 * @param string $state state [stable]
 */
if(empty($value)) throw new RuntimeException('Invalid argument');
$output_path = (isset($_ENV['PATH_EXTLIB_DIR'])) ? $_ENV['PATH_EXTLIB_DIR'] : null;
$output_path = defined('EXTLIBDIR') ? constant('EXTLIBDIR') : null;
if(empty($output_path)) $output_path = getcwd();
if(!is_file($output_path.'PEAR.php')) \org\rhaco\Pear::install('pear.php.net/PEAR','stable',$output_path);	
\org\rhaco\Pear::install($value,(isset($params['state']) ? $params['state'] : 'stable'),$output_path);
print('installed '.$value.PHP_EOL);
