<?php
$src = <<< PRE
		<form rt:ref="true" rt:param="data">
		<input type="text" name="aaa" />
		</form>
PRE;
$result = <<< PRE
		<form>
		<input type="text" name="aaa" value="hogehoge" />
		</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("data",array("aaa"=>"hogehoge"));
eq($result,$t->get($src));

//input
$src = <<< PRE
		<form rt:ref="true">
		<input type="text" name="aaa" />
		<input type="text" name="ttt" />
		<input type="checkbox" name="bbb" value="hoge" />hoge
		<input type="checkbox" name="bbb" value="fuga" checked="checked" />fuga
		<input type="checkbox" name="eee" value="true" checked />foo
		<input type="checkbox" name="fff" value="false" />foo
		<input type="submit" />
		<textarea name="aaa"></textarea>
		<textarea name="ttt"></textarea>

		<select name="ddd" size="5" multiple>
		<option value="123" selected="selected">123</option>
		<option value="456">456</option>
		<option value="789" selected>789</option>
		</select>
		<select name="XYZ" rt:param="xyz"></select>
		</form>
PRE;
$result = <<< PRE
		<form>
		<input type="text" name="aaa" value="hogehoge" />
		<input type="text" name="ttt" value="&lt;tag&gt;ttt&lt;/tag&gt;" />
		<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
		<input type="checkbox" name="bbb[]" value="fuga" />fuga
		<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
		<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
		<input type="submit" />
		<textarea name="aaa">hogehoge</textarea>
		<textarea name="ttt">&lt;tag&gt;ttt&lt;/tag&gt;</textarea>

		<select name="ddd[]" size="5" multiple="multiple">
		<option value="123">123</option>
		<option value="456" selected="selected">456</option>
		<option value="789" selected="selected">789</option>
		</select>
		<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
		</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("aaa","hogehoge");
$t->vars("ttt","<tag>ttt</tag>");
$t->vars("bbb","hoge");
$t->vars("XYZ","B");
$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
$t->vars("ddd",array("456","789"));
$t->vars("eee",true);
$t->vars("fff",false);
eq($result,$t->get($src));

$src = <<< PRE
		<form rt:ref="true">
		<select name="ddd" rt:param="abc">
		</select>
		</form>
PRE;
$result = <<< PRE
		<form>
		<select name="ddd"><option value="123">123</option><option value="456" selected="selected">456</option><option value="789">789</option></select>
		</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("abc",array(123=>123,456=>456,789=>789));
$t->vars("ddd","456");
eq($result,$t->get($src));

$src = <<< 'PRE'
<form rt:ref="true">
<rt:loop param="abc" var="v">
<input type="checkbox" name="ddd" value="{$v}" />
</rt:loop>
</form>
PRE;
$result = <<< PRE
<form>
<input type="checkbox" name="ddd[]" value="123" />
<input type="checkbox" name="ddd[]" value="456" checked="checked" />
<input type="checkbox" name="ddd[]" value="789" />
</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("abc",array(123=>123,456=>456,789=>789));
$t->vars("ddd","456");
eq($result,$t->get($src));


// reform
$src = <<< 'PRE'
		<form rt:aref="true">
		<input type="text" name="{$aaa_name}" />
		<input type="checkbox" name="{$bbb_name}" value="hoge" />hoge
		<input type="checkbox" name="{$bbb_name}" value="fuga" checked="checked" />fuga
		<input type="checkbox" name="{$eee_name}" value="true" checked />foo
		<input type="checkbox" name="{$fff_name}" value="false" />foo
		<input type="submit" />
		<textarea name="{$aaa_name}"></textarea>

		<select name="{$ddd_name}" size="5" multiple>
		<option value="123" selected="selected">123</option>
		<option value="456">456</option>
		<option value="789" selected>789</option>
		</select>
		<select name="{$XYZ_name}" rt:param="xyz"></select>
		</form>
PRE;
$result = <<< PRE
		<form>
		<input type="text" name="aaa" value="hogehoge" />
		<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
		<input type="checkbox" name="bbb[]" value="fuga" />fuga
		<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
		<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
		<input type="submit" />
		<textarea name="aaa">hogehoge</textarea>

		<select name="ddd[]" size="5" multiple="multiple">
		<option value="123">123</option>
		<option value="456" selected="selected">456</option>
		<option value="789" selected="selected">789</option>
		</select>
		<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
		</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("aaa_name","aaa");
$t->vars("bbb_name","bbb");
$t->vars("XYZ_name","XYZ");
$t->vars("xyz_name","xyz");
$t->vars("ddd_name","ddd");
$t->vars("eee_name","eee");
$t->vars("fff_name","fff");

$t->vars("aaa","hogehoge");
$t->vars("bbb","hoge");
$t->vars("XYZ","B");
$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
$t->vars("ddd",array("456","789"));
$t->vars("eee",true);
$t->vars("fff",false);
eq($result,$t->get($src));

// textarea
$src = <<< PRE
		<form>
		<textarea name="hoge"></textarea>
		</form>
PRE;
$t = new \org\rhaco\Template();
eq($src,$t->get($src));


// select
$src = '<form><select name="abc" rt:param="abc"></select></form>';
$t = new \org\rhaco\Template();
$t->vars("abc",array(123=>123,456=>456));
eq('<form><select name="abc"><option value="123">123</option><option value="456">456</option></select></form>',$t->get($src));

// multiple
$src = '<form><input name="abc" type="checkbox" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input name="abc[]" type="checkbox" /></form>',$t->get($src));

$src = '<form><input name="abc" type="checkbox" rt:multiple="false" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input name="abc" type="checkbox" /></form>',$t->get($src));

// input_exception
$src = '<form rt:ref="true"><input type="text" name="hoge" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input type="text" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="password" name="hoge" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input type="password" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="hidden" name="hoge" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input type="hidden" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="checkbox" name="hoge" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input type="checkbox" name="hoge[]" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="radio" name="hoge" /></form>';
$t = new \org\rhaco\Template();
eq('<form><input type="radio" name="hoge" /></form>',$t->get($src));

$src = '<form rt:ref="true"><textarea name="hoge"></textarea></form>';
$t = new \org\rhaco\Template();
eq('<form><textarea name="hoge"></textarea></form>',$t->get($src));

$src = '<form rt:ref="true"><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>';
$t = new \org\rhaco\Template();
eq('<form><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>',$t->get($src));


// html5
$src = <<< PRE
		<form rt:ref="true">
		<input type="search" name="search" />
		<input type="tel" name="tel" />
		<input type="url" name="url" />
		<input type="email" name="email" />
		<input type="datetime" name="datetime" />
		<input type="datetime-local" name="datetime_local" />
		<input type="date" name="date" />
		<input type="month" name="month" />
		<input type="week" name="week" />
		<input type="time" name="time" />
		<input type="number" name="number" />
		<input type="range" name="range" />
		<input type="color" name="color" />
		</form>
PRE;
$rslt = <<< PRE
		<form>
		<input type="search" name="search" value="hoge" />
		<input type="tel" name="tel" value="000-000-0000" />
		<input type="url" name="url" value="http://rhaco.org" />
		<input type="email" name="email" value="hoge@hoge.hoge" />
		<input type="datetime" name="datetime" value="1970-01-01T00:00:00.0Z" />
		<input type="datetime-local" name="datetime_local" value="1970-01-01T00:00:00.0Z" />
		<input type="date" name="date" value="1970-01-01" />
		<input type="month" name="month" value="1970-01" />
		<input type="week" name="week" value="1970-W15" />
		<input type="time" name="time" value="12:30" />
		<input type="number" name="number" value="1234" />
		<input type="range" name="range" value="7" />
		<input type="color" name="color" value="#ff0000" />
		</form>
PRE;
$t = new \org\rhaco\Template();
$t->vars("search","hoge");
$t->vars("tel","000-000-0000");
$t->vars("url","http://rhaco.org");
$t->vars("email","hoge@hoge.hoge");
$t->vars("datetime","1970-01-01T00:00:00.0Z");
$t->vars("datetime_local","1970-01-01T00:00:00.0Z");
$t->vars("date","1970-01-01");
$t->vars("month","1970-01");
$t->vars("week","1970-W15");
$t->vars("time","12:30");
$t->vars("number","1234");
$t->vars("range","7");
$t->vars("color","#ff0000");

eq($rslt,$t->get($src));
