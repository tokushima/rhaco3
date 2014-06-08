<?php
/**
 * .htaccessを書き出す
* @param string $base
*/
if(!isset($base)) $base = '/'.basename(getcwd());
list($path,$rules) = \org\rhaco\Dt::htaccess($base);

\cmdman\Std::println_success('Written: '.$path);
\cmdman\Std::println_default(str_repeat('-',60));
\cmdman\Std::println_info(trim($rules));
\cmdman\Std::println_default(str_repeat('-',60));
