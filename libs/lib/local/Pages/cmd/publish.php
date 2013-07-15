<?php
/**
 * GitHub Pages用のHTMLを書き出す
 * @param string $output_path
 */
$output_path = $in_value('output',getcwd().'/contents/');

\local\Pages::publish($output_path);
