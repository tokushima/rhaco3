<?php
/**
 * 書き出す
 * @param string $template
 * @param string $output
 */
$template = $in_value('template',getcwd().'/template');
$output = $in_value('output',getcwd().'/contents');

\local\Pages::export($template,$output);
