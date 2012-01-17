<?php
/**
 * Sample Hello World
 */
list($value,$params) = array(isset($_ENV['value'])?$_ENV['value']:null,isset($_ENV['params'])?$_ENV['params']:array());
if(empty($value)) $value = 'index';
$dir = str_replace("\\",'/',getcwd()).'/';

$path = $dir.$value.'.php';
if(is_file($path)) throw new \InvalidArgumentException($path.': File exists');
file_put_contents($path
				,"<?php require dirname(__FILE__).\"/rhaco3.php\"; app(); ?>\n"
				."<a"."pp>\n"
					."\t<handler>\n"
					."\t\t<map url=\"\" class=\"yourdomain.HelloWorld\" method=\"sample\" template=\"index.html\" />\n"
					."\t</handler>\n"
				."</"."app>\n"
			);
\org\rhaco\lang\Text::println('Create '.$path,true);

foreach(array('resources/templates','resources/media','libs') as $p){
	if(!is_dir($dir.$p)){
		mkdir($dir.$p);
		\org\rhaco\lang\Text::println('Create '.$dir.$p,true);
	}
}
$path = $dir.'libs/yourdomain/HelloWorld.php';
if(!is_file($path)){
	file_put_contents($path
					,"<?php\n"
					."class HelloWorld extends Flow{\n"
					."\tpublic function sample(){\n"
					."\t\t\$this->vars('message','hello world');\n"
					."\t}\n"
					."}\n"
				);
	\org\rhaco\lang\Text::println('Create '.$path,true);
}
$path = $dir.'resources/media/style.css';
if(!is_file($path)){
	file_put_contents($path
					,"body{ font-family: Georgia; }\n"
					."h1{ font-style: italic; }\n"
				);
	\org\rhaco\lang\Text::println('Create '.$path,true);
}
$path = $dir.'resources/templates/index.html';
if(!is_file($path)){
	file_put_contents($path
					,"<html>\n"
					."<head>\n"
					."\t<title>new page</title>\n"
					."\t<link href=\"style.css\" rel=\"stylesheet\" type=\"text/css\" />\n"
					."</head>\n"
					."<body>\n"
					."\t<h1>{\$message}</h1>\n"
					."\t<a href=\"http://rhaco.org/\">powered by rhaco</a>\n"
					."</body>\n"
					."</html>\n"
				);
	\org\rhaco\lang\Text::println('Create '.$path,true);
}
