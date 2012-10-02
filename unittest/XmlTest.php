<?php
use \org\rhaco\Xml;
include('Dummy.php');

class XmlTest extends PHPUnit_Framework_TestCase{
	public function testValue(){
		$xml = new Xml('test');
		$this->assertEquals('hoge',$xml->value('hoge'));
		$this->assertEquals('true',$xml->value(true));
		$this->assertEquals('false',$xml->value(false));
		$this->assertEquals('<abc>1</abc><def>2</def><ghi>3</ghi>',$xml->value(array('abc'=>1,'def'=>2,'ghi'=>3)));
		$this->assertEquals(null,$xml->value(''));
		$this->assertEquals(1,$xml->value('1'));
		$this->assertEquals(null,$xml->value(null));
		$xml->escape(true);
		$this->assertEquals('<abc>123</abc>',$xml->value('<abc>123</abc>'));
		$this->assertEquals('<b>123</b>',$xml->value(new Xml('b','123')));

		$xml = new Xml('test');
		$xml->escape(false);
		$this->assertEquals('<abc>123</abc>',$xml->value('<abc>123</abc>',false));
	}
	public function testValueCDATA(){
		$xml = new Xml('test');
		$add = new Xml('addxml','hoge');
		$xml->add($add);
		$xml->add($add->get());
		$xml->add((string)$add);
		$this->assertEquals('<test><addxml>hoge</addxml><![CDATA[<addxml>hoge</addxml>]]><![CDATA[<addxml>hoge</addxml>]]></test>',$xml->get());
	}
	public function testAdd(){
		$x = new Xml('test');
		$x->value('abc');
		$this->assertEquals('abc',$x->value());
		$x->add('def');
		$this->assertEquals('abcdef',$x->value());
		$x->add(new Xml('b','123'));
		$this->assertEquals('abcdef<b>123</b>',$x->value());
	}
	public function testInAttr(){
		$x = new Xml("test");
		$x->attr("abc",123);
		$this->assertEquals("123",$x->in_attr("abc"));
		$this->assertEquals(null,$x->in_attr("def"));
		$this->assertEquals("456",$x->in_attr("ghi",456));

		$x->attr("def","'<>'");

		$x->escape(true);
		$this->assertEquals("&#039;&lt;&gt;&#039;",$x->in_attr("def"));
		$this->assertEquals('<test abc="123" def="&#039;&lt;&gt;&#039;" />',$x->get());

		$x->escape(false);
		$this->assertEquals("'<>'",$x->in_attr("def"));
		$this->assertEquals('<test abc="123" def="\'<>\'" />',$x->get());
	}
	public function testRmAttr(){
		$x = new Xml("test");
		$x->attr("abc",123);
		$x->attr("def",456);
		$x->attr("ghi",789);

		$this->assertEquals(array("abc"=>123,"def"=>456,"ghi"=>789),iterator_to_array($x));
		$x->rm_attr("def");
		$this->assertEquals(array("abc"=>123,"ghi"=>789),iterator_to_array($x));
		$x->attr("def",456);
		$this->assertEquals(array("abc"=>123,"ghi"=>789,"def"=>456),iterator_to_array($x));
		$x->rm_attr("abc","ghi");
		$this->assertEquals(array("def"=>456),iterator_to_array($x));
	}
	public function testIsAttr(){
		$x = new Xml("test");
		$this->assertEquals(false,$x->is_attr("abc"));
		$x->attr("abc",123);
		$this->assertEquals(true,$x->is_attr("abc"));
		$x->attr("abc",null);
		$this->assertEquals(true,$x->is_attr("abc"));
		$x->rm_attr("abc");
		$this->assertEquals(false,$x->is_attr("abc"));
	}
	public function testAttr(){
		$x = new Xml("test");
		$x->escape(true);
		$x->attr("abc",123);
		$this->assertEquals(123,$x->in_attr("abc"));
		$x->attr("Abc",456);
		$this->assertEquals(456,$x->in_attr("abc"));
		$x->attr("DEf",555);
		$this->assertEquals(555,$x->in_attr("def"));
		$this->assertEquals(456,$x->in_attr("abc"));
		$x->attr("Abc","<aaa>");
		$this->assertEquals("&lt;aaa&gt;",$x->in_attr("abc"));
		$x->attr("Abc",true);
		$this->assertEquals("true",$x->in_attr("abc"));
		$x->attr("Abc",false);
		$this->assertEquals("false",$x->in_attr("abc"));
		$x->attr("Abc",null);
		$this->assertEquals(null,$x->in_attr("abc"));
		$x->attr("ghi",null);
		$this->assertEquals(null,$x->in_attr("ghi"));
		$this->assertEquals(array("abc"=>null,"def"=>555,"ghi"=>null),iterator_to_array($x));

		$x->attr("Jkl","Jkl");
		$this->assertEquals(array("abc"=>null,"def"=>555,"ghi"=>null,"jkl"=>"Jkl"),iterator_to_array($x));
	}
	public function testGet(){
		$x = new Xml("test",123);
		$this->assertEquals("<test>123</test>",$x->get());
		$x = new Xml("test",new Xml("hoge","AAA"));
		$this->assertEquals("<test><hoge>AAA</hoge></test>",$x->get());
		$x = new Xml("test");
		$this->assertEquals("<test />",$x->get());
		$x = new Xml("test");
		$x->close_empty(false);
		$this->assertEquals("<test></test>",$x->get());
		$x = new Xml("test");
		$x->attr("abc",123);
		$x->attr("def",456);
		$this->assertEquals('<test abc="123" def="456" />',$x->get());
	}
	public function testToString(){
		$x = new Xml("test",123);
		$this->assertEquals("<test>123</test>",(string)$x);
	}
	public function testSet(){
		$p = "<abc><def>111</def></abc>";
		if($this->assertEquals(true,Xml::set($x,$p))){
			$this->assertEquals("abc",$x->name());
		}
		$p = "<abc><def>111</def></abc>";
		if($this->assertEquals(true,Xml::set($x,$p,"def"))){
			$this->assertEquals("def",$x->name());
			$this->assertEquals(111,$x->value());
		}
		$p = "aaaa";
		$this->assertEquals(false,Xml::set($x,$p));
		$p = "<abc>sss</abc>";
		$this->assertEquals(false,Xml::set($x,$p,"def"));
		$p = "<abc>sss</a>";

		if($this->assertEquals(true,Xml::set($x,$p))){
			$this->assertEquals("<abc />",$x->get());
		}
		$p = "<abc>0</abc>";
		if($this->assertEquals(true,Xml::set($x,$p))){
			$this->assertEquals("abc",$x->name());
			$this->assertEquals("0",$x->value());
		}
	}
	public function testIn(){
		$x = new Xml("abc","<def>123</def><def>456</def><def>789</def>");
		$r = array(123,456,789);
		$i = 0;
		foreach($x->in("def") as $d){
			$this->assertEquals($r[$i],$d->value());
			$i++;
		}
		$x = new Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
		$r = array(123,456,789);
		$i = 0;
		foreach($x->in("def") as $d){
			$this->assertEquals($r[$i],$d->value());
			$i++;
		}
		$x = new Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
		$r = array(456,789);
		$i = 0;
		foreach($x->in("def",1) as $d){
			$this->assertEquals($r[$i],$d->value());
			$i++;
		}
		$x = new Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
		$r = array(456);
		$i = 0;
		foreach($x->in("def",1,1) as $d){
			$this->assertEquals($r[$i],$d->value());
			$i++;
		}
		$x = new Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><abc>GHI</abc><def>789</def>");
		$i = 0;
		foreach($x->in(array("def","abc")) as $d){
			$i++;
		}
		$this->assertEquals(6,$i);
		$x = new Xml("abc","<def>123</def><abc>ABC</abc><def>456</def><abc>DEF</abc><ghi>000</ghi><abc>GHI</abc><def>789</def>");
		$i = 0;
		foreach($x->in(array("def","abc")) as $d){
			$i++;
		}
		$this->assertEquals(6,$i);
	}
	public function testF(){
		$src = "<tag><abc><def var='123'><ghi selected>hoge</ghi></def></abc></tag>";
		if(Xml::set($tag,$src,"tag")){
			$this->assertEquals("hoge",$tag->f("abc.def.ghi.value()"));
			$this->assertEquals("123",$tag->f("abc.def.attr(var)"));
			$this->assertEquals("selected",$tag->f("abc.def.ghi.attr(selected)"));
			$this->assertEquals("<def var='123'><ghi selected>hoge</ghi></def>",$tag->f("abc.def.plain()"));
			$this->assertEquals(null,$tag->f("abc.def.xyz"));
		}
		$src = <<< _T_
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
_T_;
		Xml::set($tag,$src,"tag");
		$this->assertEquals("django",$tag->f("abc.def.ghi[2].value()"));
		$this->assertEquals("rails",$tag->f("abc.def.ghi[1].jkl.value()"));
		$tag->f("abc.def.ghi[2].value()","python");
		$this->assertEquals("python",$tag->f("abc.def.ghi[2].value()"));

		$this->assertEquals("123",$tag->f("abc.def.attr(var)"));
		$this->assertEquals("true",$tag->f("abc.def.ghi[2].attr(ab)"));
		$tag->f("abc.def.ghi[2].attr(cd)",456);
		$this->assertEquals("456",$tag->f("abc.def.ghi[2].attr(cd)"));

		$this->assertEquals('selected',$tag->f("abc.def.ghi[0].attr(selected)"));
		$this->assertEquals(null,$tag->f("abc.def.ghi[1].attr(selected)"));

		$x = array();
		foreach($tag->f("abc.def.in(xyz)") as $f) $x[] = $f;
		$this->assertEquals(array(),$x);

		$x = array();
		foreach($tag->f("abc.opq.in(xyz)") as $f) $x[] = $f;
		$this->assertEquals(array(),$tag->f("abc.opq.in(xyz)"));
	}
	public function testId(){
		 $src = <<< _T_
<aaa>
<bbb id="DEF"></bbb>
<ccc id="ABC">
<ddd id="XYZ">hoge</ddd>
</ccc>
</aaa>
_T_;
		Xml::set($tag,$src);
		$this->assertEquals("ddd",$tag->id("XYZ")->name());
		$this->assertEquals(null,$tag->id("xyz"));
		$tag->value('xyz');
	}

	public function testGetIteratorAggregateObject(){
		$obj = new \Dummy();
		$obj->vars('aaa','hoge');
		$obj->vars('ccc',123);
		$self = new Xml('abc',$obj);
		$this->assertEquals('<abc><aaa>hoge</aaa><ccc>123</ccc></abc>',$self->get());
	}
	public function testGetIteratorAggregateObjects(){
		$obj = new \Dummy();
		$obj->vars('aaa','hoge');
		$obj->vars('ccc',123);

		$self = new Xml('abc',$obj);
		$this->assertEquals('<abc><aaa>hoge</aaa><ccc>123</ccc></abc>',$self->get());

		$n = get_class($obj);
		$obj1 = clone($obj);
		$obj2 = clone($obj);
		$obj3 = clone($obj);
		$obj2->vars('ccc',456);
		$obj3->vars('ccc',789);

		$arr = array($obj1,$obj2,$obj3);
		$self = new Xml('abc',$arr);
		$this->assertEquals(
				sprintf('<abc>'
						.'<%s><aaa>hoge</aaa><ccc>123</ccc></%s>'
						.'<%s><aaa>hoge</aaa><ccc>456</ccc></%s>'
						.'<%s><aaa>hoge</aaa><ccc>789</ccc></%s>'
						.'</abc>',
						$n,$n,$n,$n,$n,$n
				),$self->get());
	}
}
