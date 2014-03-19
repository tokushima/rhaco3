<?php



\org\rhaco\Xml::set($xml,'<asd><abc><def><aa>AA</aa><bb>BB</bb><cc>CC</cc></def></abc></asd>');
eq('<asd><abc><def><aa>AA</aa><bb>ZZ</bb><cc>CC</cc></def></abc></asd>',$xml->replace('def/bb','ZZ')->get());
eq('<asd><abc><def><aa>AA</aa><bb>BB</bb><cc>CC</cc></def></abc></asd>',$xml->get());


\org\rhaco\Xml::set($xml,'<asd><abc><def><aa>AA</aa><bb>BB</bb><cc>CC</cc></def></abc></asd>');
eq('<asd><abc><def>ZZ</def></abc></asd>',$xml->replace('def','ZZ')->get());


\org\rhaco\Xml::set($xml,'<asd><abc><def><aa>AA</aa><bb>BB</bb><cc>CC</cc><dd><aaa>AAA</aaa><bbb>BBB</bbb><ccc>CCC</ccc></dd></def></abc></asd>');
eq('<asd><abc><def><aa>AA</aa><bb>BB</bb><cc>CC</cc><dd><aaa>AAA</aaa><bbb>ZZZ</bbb><ccc>CCC</ccc></dd></def></abc></asd>',$xml->replace('def/dd/bbb','ZZZ')->get());

