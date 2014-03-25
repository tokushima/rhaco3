<?php
/**
 * .htaccessを書き出す
* @param string $base
*/
if(!isset($base)) $base = '/'.basename(getcwd());
list($path,$rules) = \org\rhaco\Dt::htaccess($base);

\brev\Std::println_success('Written: '.$path);
\brev\Std::println_default(str_repeat('-',60));
\brev\Std::println_info(trim($rules));
\brev\Std::println_default(str_repeat('-',60));
