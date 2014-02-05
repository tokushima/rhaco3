<?php
$src = pre('
 		<table><tr><td><table rt:param="xyz" rt:var="o">
 		<tr class="odd"><td>{$o["B"]}</td></tr>
 		</table></td></tr></table>
 		');
$result = pre('
		<table><tr><td><table><tr class="odd"><td>222</td></tr>
		<tr class="even"><td>444</td></tr>
		<tr class="odd"><td>666</td></tr>
		</table></td></tr></table>
		');
$t = new \org\rhaco\Template();
$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
eq($result,$t->get($src));


$src = pre('
 		<table rt:param="abc" rt:var="a"><tr><td><table rt:param="a" rt:var="x"><tr><td>{$x}</td></tr></table></td></td></table>
 		');
$result = pre('
		<table><tr><td><table><tr><td>A</td></tr><tr><td>B</td></tr></table></td></td><tr><td><table><tr><td>C</td></tr><tr><td>D</td></tr></table></td></td></table>
		');
$t = new \org\rhaco\Template();
$t->vars("abc",array(array("A","B"),array("C","D")));
eq($result,$t->get($src));


$src = pre('
 		<ul rt:param="abc" rt:var="a"><li><ul rt:param="a" rt:var="x"><li>{$x}</li></ul></li></ul>
 		');
$result = pre('
		<ul><li><ul><li>A</li><li>B</li></ul></li><li><ul><li>C</li><li>D</li></ul></li></ul>
		');
$t = new \org\rhaco\Template();
$t->vars("abc",array(array("A","B"),array("C","D")));
eq($result,$t->get($src));

$src = pre('
 		<table rt:param="xyz" rt:var="o">
 		<tr class="odd"><td>{$o["B"]}</td></tr>
 		</table>
 		');
$result = pre('
		<table><tr class="odd"><td>222</td></tr>
		<tr class="even"><td>444</td></tr>
		<tr class="odd"><td>666</td></tr>
		</table>
		');
$t = new \org\rhaco\Template();
$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
eq($result,$t->get($src));

$src = pre('
 		<table rt:param="xyz" rt:var="o">
 		<tr><td>{$o["B"]}</td></tr>
 		</table>
 		');
$result = pre('
		<table><tr><td>222</td></tr>
		<tr><td>444</td></tr>
		<tr><td>666</td></tr>
		</table>
		');
$t = new \org\rhaco\Template();
$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
eq($result,$t->get($src));


$src = pre('
 		<ul rt:param="xyz" rt:var="o">
 		<li class="odd">{$o["B"]}</li>
 		</ul>
 		');
$result = pre('
		<ul>	<li class="odd">222</li>
		<li class="even">444</li>
		<li class="odd">666</li>
		</ul>
		');
$t = new \org\rhaco\Template();
$t->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
eq($result,$t->get($src));

// abc
$src = pre('
		<rt:loop param="abc" var="a">
		<ul rt:param="{$a}" rt:var="b">
		<li>
		<ul rt:param="{$b}" rt:var="c">
		<li>{$c}<rt:loop param="xyz" var="z">{$z}</rt:loop></li>
		</ul>
		</li>
		</ul>
		</rt:loop>
		');
$result = pre('
		<ul><li>
		<ul><li>A12</li>
		<li>B12</li>
		</ul>
		</li>
		</ul>
		<ul><li>
		<ul><li>C12</li>
		<li>D12</li>
		</ul>
		</li>
		</ul>

		');
$t = new \org\rhaco\Template();
$t->vars("abc",array(array(array("A","B")),array(array("C","D"))));
$t->vars("xyz",array(1,2));
eq($result,$t->get($src));


// nest_table
$src = pre('<table rt:param="object_list" rt:var="obj"><tr><td><table rt:param="obj" rt:var="o"><tr><td>{$o}</td></tr></table></td></tr></table>');
$t = new \org\rhaco\Template();
$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
eq('<table><tr><td><table><tr><td>A1</td></tr><tr><td>A2</td></tr><tr><td>A3</td></tr></table></td></tr><tr><td><table><tr><td>B1</td></tr><tr><td>B2</td></tr><tr><td>B3</td></tr></table></td></tr></table>',$t->get($src));


// nest_ul
$src = pre('<ul rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ul>');
$t = new \org\rhaco\Template();
$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
eq('<ul><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ul>',$t->get($src));


// nest_ol
$src = pre('<ol rt:param="object_list" rt:var="obj"><li><ol rt:param="obj" rt:var="o"><li>{$o}</li></ol></li></ol>');
$t = new \org\rhaco\Template();
$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
eq('<ol><li><ol><li>A1</li><li>A2</li><li>A3</li></ol></li><li><ol><li>B1</li><li>B2</li><li>B3</li></ol></li></ol>',$t->get($src));


// nest_olul
$src = pre('<ol rt:param="object_list" rt:var="obj"><li><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></li></ol>');
$t = new \org\rhaco\Template();
$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
eq('<ol><li><ul><li>A1</li><li>A2</li><li>A3</li></ul></li><li><ul><li>B1</li><li>B2</li><li>B3</li></ul></li></ol>',$t->get($src));


// nest_tableul
$src = pre('<table rt:param="object_list" rt:var="obj"><tr><td><ul rt:param="obj" rt:var="o"><li>{$o}</li></ul></td></tr></table>');
$t = new \org\rhaco\Template();
$t->vars("object_list",array(array("A1","A2","A3"),array("B1","B2","B3")));
eq('<table><tr><td><ul><li>A1</li><li>A2</li><li>A3</li></ul></td></tr><tr><td><ul><li>B1</li><li>B2</li><li>B3</li></ul></td></tr></table>',$t->get($src));


