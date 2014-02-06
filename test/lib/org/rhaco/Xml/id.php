<?php
$src = <<< PRE
		<aaa>
		<bbb id="DEF"></bbb>
		<ccc id="ABC">
		<ddd id="XYZ">hoge</ddd>
		</ccc>
		</aaa>
PRE;
\org\rhaco\Xml::set($tag,$src);
eq("ddd",$tag->id("XYZ")->name());
eq(null,$tag->id("xyz"));
