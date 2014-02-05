<?php
$src = <<< ATOM
 		<feed xmlns="http://www.w3.org/2005/Atom">
 		<title>atom10 feed</title>
 		<subtitle>atom10 sub title</subtitle>
 		<updated>2007-07-18T16:16:31+00:00</updated>
 		<generator>tokushima</generator>
 		<link href="http://tokushimakazutaka.com" rel="abc" type="xyz" />

 		<author>
 		<url>http://tokushimakazutaka.com</url>
 		<name>tokushima</name>
 		<email>tokushima@hoge.hoge</email>
 		</author>

 		<entry>
 		<title>rhaco</title>
 		<summary type="xml" xml:lang="ja">summary test</summary>
 		<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
 		<link href="http://rhaco.org" rel="abc" type="xyz" />
 		<link href="http://conveyor.rhaco.org" rel="abc" type="conveyor" />
 		<link href="http://lib.rhaco.org" rel="abc" type="lib" />

 		<updated>2007-07-18T16:16:31+00:00</updated>
 		<issued>2007-07-18T16:16:31+00:00</issued>
 		<published>2007-07-18T16:16:31+00:00</published>
 		<id>rhaco</id>
 		<author>
 		<url>http://rhaco.org</url>
 		<name>rhaco</name>
 		<email>rhaco@rhaco.org</email>
 		</author>
 		</entry>

 		<entry>
 		<title>django</title>
 		<summary type="xml" xml:lang="ja">summary test</summary>
 		<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
 		<link href="http://djangoproject.jp" rel="abc" type="xyz" />

 		<updated>2007-07-18T16:16:31+00:00</updated>
 		<issued>2007-07-18T16:16:31+00:00</issued>
 		<published>2007-07-18T16:16:31+00:00</published>
 		<id>django</id>
 		<author>
 		<url>http://www.everes.net</url>
 		<name>everes</name>
 		<email>everes@hoge.hoge</email>
 		</author>
 		</entry>

 		</feed>
ATOM;

$xml = \org\rhaco\net\xml\Atom::parse($src);
$result = <<< ATOM
		<feed xmlns="http://www.w3.org/2005/Atom">
		<title>atom10 feed</title>
		<subtitle>atom10 sub title</subtitle>
		<id>rhaco</id>
		<generator>tokushima</generator>
		<updated>2007-07-18T16:16:31Z</updated>
		<link rel="abc" type="xyz" href="http://tokushimakazutaka.com" />
		<entry>
		<id>rhaco</id>
		<title>rhaco</title>
		<published>2007-07-18T16:16:31Z</published>
		<updated>2007-07-18T16:16:31Z</updated>
		<issued>2007-07-18T16:16:31Z</issued>
		<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
		<summary type="xml" xml:lang="ja">summary test</summary>
		<link rel="abc" type="xyz" href="http://rhaco.org" />
		<link rel="abc" type="conveyor" href="http://conveyor.rhaco.org" />
		<link rel="abc" type="lib" href="http://lib.rhaco.org" />
		<author>
		<name>rhaco</name>
		<url>http://rhaco.org</url>
		<email>rhaco@rhaco.org</email>
		</author>
		</entry>
		<entry>
		<id>django</id>
		<title>django</title>
		<published>2007-07-18T16:16:31Z</published>
		<updated>2007-07-18T16:16:31Z</updated>
		<issued>2007-07-18T16:16:31Z</issued>
		<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
		<summary type="xml" xml:lang="ja">summary test</summary>
		<link rel="abc" type="xyz" href="http://djangoproject.jp" />
		<author>
		<name>everes</name>
		<url>http://www.everes.net</url>
		<email>everes@hoge.hoge</email>
		</author>
		</entry>
		<author>
		<name>tokushima</name>
		<url>http://tokushimakazutaka.com</url>
		<email>tokushima@hoge.hoge</email>
		</author>
		</feed>
ATOM;

$result = str_replace(array("\n","\t"),"",$result);
eq($result,(string)$xml);

