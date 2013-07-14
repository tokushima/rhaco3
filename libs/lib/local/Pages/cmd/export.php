<?php
/**
 * 書き出す
 * @param string $output_path
 */
$output_path = $in_value('output',getcwd().'/contents/');

\local\Pages::export($output_path);
