<?php
$src = "<tag><abc><def var='123'><ghi selected>hoge</ghi></def></abc></tag>";

eq(true,\org\rhaco\Xml::set($tag,$src,"tag"));
eq("hoge",$tag->f("abc.def.ghi.value()"));
eq("123",$tag->f("abc.def.attr(var)"));
eq("selected",$tag->f("abc.def.ghi.attr(selected)"));
eq("<def var='123'><ghi selected>hoge</ghi></def>",$tag->f("abc.def.plain()"));
eq(null,$tag->f("abc.def.xyz"));


$src = <<< XML
<tag>
	<abc>
		<def var="123">
			<ghi selected>hoge</ghi>
			<ghi>
				<jkl>rails</jkl>
			</ghi>
			<ghi ab="true">django</ghi>
		</def>
	</abc>
</tag>
XML;

\org\rhaco\Xml::set($tag,$src,"tag");
eq("django",$tag->f("abc.def.ghi[2].value()"));
eq("rails",$tag->f("abc.def.ghi[1].jkl.value()"));

eq("django",$tag->f("abc.def.ghi[2].value()"));
$tag->f("abc.def.ghi[2].value()","python");
eq("python",$tag->f("abc.def.ghi[2].value()"));

eq("123",$tag->f("abc.def.attr(var)"));
eq("true",$tag->f("abc.def.ghi[2].attr(ab)"));

$tag->f("abc.def.ghi[2].attr(cd)",456);
eq("456",$tag->f("abc.def.ghi[2].attr(cd)"));

eq('selected',$tag->f("abc.def.ghi[0].attr(selected)"));
eq(null,$tag->f("abc.def.ghi[1].attr(selected)"));
eq(array(),$tag->f("abc.def.in(xyz)"));
eq(array(),$tag->f("abc.opq.in(xyz)"));
