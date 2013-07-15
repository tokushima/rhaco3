<?php
/**
 * GitHub Pagesを独自ドメインで表示する為のファイルを書き出す
 * @param string $name ドメイン名
 */
$domain = $in_value('name');
if(empty($domain)){
	throw new \LogicException('require -name [domain name]');
}
file_put_contents(getcwd().'/CNAME',$domain);

$println('@see https://help.github.com/articles/setting-up-a-custom-domain-with-pages');

