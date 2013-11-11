<?php
include_once(__DIR__.'/bootstrap.php');

/**
 * @name rhaco.org
 * @summary site
 */
\org\rhaco\Flow::out(
array(
'error_status'=>403,
'patterns'=>array(
	''=>array(
		'name'=>'index',
		'template'=>'index.html',
		'vars'=>array(
			'xml_var'=>'hogehoge_xml_var',
			'xml_var_value'=>'hogehoge_xml_var_value',
			'xml_var_array'=>array('A1','A2','A3'),
			'xml_var_obj'=>new \test\FlowVar(),
			'xml_var_objC'=>\test\FlowVar::ccc(),
		)
	),
	'map_url'=>array('template'=>'map_url.html'),
	'module'=>array('name'=>'module','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule'),
	'module_request_flow'=>array('name'=>'module_request_flow','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','action'=>'org.rhaco.flow.parts.RequestFlow::noop'),
	'sample_flow_html'=>array('name'=>'sample_flow_html','template'=>'sample.html'),
	'sample_flow_exception/throw'=>array('name'=>'sample_flow_exception_throw','action'=>'test.SampleExceptionFlow::throw_method','error_template'=>'exception_flow/error.html'),
	'sample_flow_exception/throw/xml'=>array('name'=>'sample_flow_exception_throw_xml','action'=>'test.SampleExceptionFlow::throw_method'),
	'sample_flow_exception/throw/xml/package'=>array('name'=>'sample_flow_exception_package_throw_xml','action'=>'test.SampleExceptionFlow::throw_method_package'),
	'extends_block_template/extendsA'=>array('name'=>'template_super_a','template'=>'template_super.html'),
	'extends_block_template/extendsB'=>array('name'=>'template_super_b','template'=>'template_super.html','template_super'=>'template_super_x.html'),
	
	'under_var'=>array('name'=>'under_var','action'=>'test.CoreApp::under_var','template'=>'under_var.html'),
	'module_throw_exception'=>array('name'=>'module_throw_exception','action'=>'test.CoreApp::noop','modules'=>'test.flow.module.CoreTestExceptionModule'),
	'noop'=>array('name'=>'noop','action'=>'test.CoreApp::noop'),
	'method_not_allowed'=>array('name'=>'method_not_allowed','action'=>'test.CoreApp::method_not_allowed'),
	'module_map'=>array('name'=>'module_map','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule'),
	'module_maps'=>array('name'=>'module_maps','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule'),
	'module_raise'=>array('name'=>'module_raise','action'=>'test.CoreApp::raise','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','error_template'=>'module_exception.html'),
	'module_add_exceptions'=>array('name'=>'module_add_exceptions','action'=>'test.CoreApp::add_exceptions','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','error_template'=>'module_exception.html'),
	'raise'=>array('name'=>'raise','action'=>'test.CoreApp::raise'),
	'module_order'=>array('name'=>'module_order','template'=>'module_order.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModuleOrder'),
	
	'redirect_test_a'=>array('name'=>'redirect_by_map_method_a','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_a'),
	'redirect_test_call_a'=>array('name'=>'redirect_by_map_method_call_a','template'=>'redirect_test_call_a.html'),
	'redirect_test_b'=>array('name'=>'redirect_by_map_method_b','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_b','args'=>array('redirect_by_map_method_call_b'=>'redirect_by_map_method_call_alias_b')),
	'redirect_test_call_b'=>array('name'=>'redirect_by_map_method_call_alias_b','template'=>'redirect_test_call_b.html'),
	'redirect_test_c'=>array('name'=>'redirect_by_map_method_c','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_c','args'=>array('redirect_by_map_method_call_c'=>'redirect_by_map_method_call_alias_c')),
	'redirect_test_call_c'=>array('name'=>'redirect_by_map_method_call_alias_c','template'=>'redirect_test_call_c.html'),
	'redirect_test_call_c_e'=>array('name'=>'redirect_by_map_method_call_c','template'=>'redirect_test_call_c_e.html'),

	'notemplate'=>array('name'=>'notemplate','action'=>'test.CoreTestNotTemplate::aaa'),
	
	'put_block'=>array('name'=>'put_block','action'=>'test.CoreTestPutBlock::index','template'=>'put_block.html'),
	'sample_flow'=>array('name'=>'sample_flow','action'=>'test.SampleFlow'),

	'set_session'=>array('name'=>'set_session','action'=>'test.flow.Session::set_session'),
	'get_session'=>array('name'=>'get_session','action'=>'test.flow.Session::get_session'),
	'plain_noop'=>array('request_redirect','action'=>'test.flow.PlainFlow::noop'),
	
	'upload_multi'=>array('name'=>'upload_multi','action'=>'test.SampleFlow::upload_multi'),
	'upload_value'=>array('name'=>'upload_value','action'=>'test.SampleFlow::upload_value'),
	'upload_file'=>array('name'=>'upload_file','action'=>'test.SampleFlow::upload_file'),
	
	

	'dao'=>array(
		'modules'=>array('org.rhaco.flow.module.Dao'),
		'patterns'=>array(
			'insert'=>array('name'=>'dao/insert','action'=>'test.flow.Model::insert'),
			'update'=>array('name'=>'dao/update','action'=>'test.flow.Model::update'),
			'delete'=>array('name'=>'dao/delete','action'=>'test.flow.Model::delete'),
			'get'=>array('name'=>'dao/get','action'=>'test.flow.Model::get'),
		)
	),

	'after'=>array('name'=>'after','action'=>'test.Sample::after_redirect','after'=>'after_to'),
	'after/to'=>array('name'=>'after_to','action'=>'test.Sample::after_to'),
	'after/to/arg1'=>array('name'=>'after_arg1','action'=>'test.Sample::after_redirect','after'=>array('after_to_arg1','next_var_A')),
	'after/to/(.+)'=>array('name'=>'after_to_arg1','action'=>'test.Sample::after_to'),
	'after/to/arg2'=>array('name'=>'after_arg2','action'=>'test.Sample::after_redirect','after'=>array('after_to_arg2','next_var_A','next_var_B')),
	'after/to/(.+)/(.+)'=>array('name'=>'after_to_arg2','action'=>'test.Sample::after_to'),
	
	'post_after'=>array('name'=>'post_after','action'=>'test.Sample::after_redirect','post_after'=>'post_after_to'),
	'post_after/to'=>array('name'=>'post_after_to','action'=>'test.Sample::after_to'),
	'post_after/to/arg1'=>array('name'=>'post_after_arg1','action'=>'test.Sample::after_redirect','after'=>array('post_after_to_arg1','next_var_A')),
	'post_after/to/(.+)'=>array('name'=>'post_after_to_arg1','action'=>'test.Sample::after_to'),
	'post_after/to/arg2'=>array('name'=>'post_after_arg2','action'=>'test.Sample::after_redirect','after'=>array('post_after_to_arg2','next_var_A','next_var_B')),
	'post_after/to/(.+)/(.+)'=>array('name'=>'post_after_to_arg2','action'=>'test.Sample::after_to'),
	
	'rt/exceptions'=>array('name'=>'rt_exceptions','action'=>'test.CoreApp::raise','modules'=>array('org.rhaco.flow.module.Exceptions'),'template'=>'hoge.html','error_template'=>'exceptions.html'),

	'helper/range'=>array('name'=>'helper_range','template'=>'helper/range.html','vars'=>array('max'=>5)),
	
	'html_filter'=>array(
		'name'=>'html_filter',
		'template'=>'html_filter.html',
		'vars'=>array(
			'aaa'=>'hogehoge',
			'ttt'=>'<tag>ttt</tag>',
			'bbb'=>'hoge',
			'XYZ'=>'B',
			'xyz'=>array('A'=>'456','B'=>'789','C'=>'010'),
			'ddd'=>array('456','789'),
			'eee'=>true,
			'fff'=>false,
			
			'ppp'=>'PPPPP',
			'qqq'=>'<tag>QQQ</tag>',
		),
		'modules'=>array('org.rhaco.flow.module.HtmlFilter'),
	),
	'csrf'=>array(
		'name'=>'csrf',
		'action'=>'org.rhaco.flow.parts.RequestFlow::noop',
		'modules'=>array('org.rhaco.flow.module.Csrf'),
	),
	'csrf_template'=>array(
		'name'=>'csrf_template',
		'action'=>'org.rhaco.flow.parts.RequestFlow::noop',
		'modules'=>array('org.rhaco.flow.module.Csrf'),
		'template'=>'csrf.html',
	),
)));

/***
# put_block
$b = b();
$b->do_get(test_map_url('put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('NONE',$b->body());

$b = b();
$b->vars('hoge','a');
$b->do_get(test_map_url('put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('a',$b->body());
mneq('CCC',$b->body());

$b = b();
$b->vars('hoge','b');
$b->do_get(test_map_url('put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('b',$b->body());
mneq('CCC',$b->body());
*/

/***
# sample_exception_flow

$b = b();

$b->do_get(test_map_url('sample_flow_exception_throw'));
eq('ERROR',$b->body());
*/


/***
# template_super
$b = b();
$b->do_get(test_map_url('template_super_a'));
eq('abcd',$b->body());

$b->do_get(test_map_url('template_super_b'));
eq('xc',$b->body());
*/


/***
# index
$b = b();
$b->do_get(test_map_url('index'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('hogehoge_xml_var',$b->body());
meq('A1A2A3',$b->body());
meq('	hogehoge_xml_var_value',$b->body());
meq('[AAA]',$b->body());
meq('[BBB]',$b->body());
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('CCC',$b->body());
meq('resources/media',$b->body());
*/


/***
# module

$b = b();
$b->do_get(test_map_url('module'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW_HANDLE',$b->body());
meq('AFTER_FLOW_HANDLE',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_EXEC_TEMPLATE',$b->body());
meq('AFTER_EXEC_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/


/***
# module_request_flow

$b = b();
$b->do_get(test_map_url('module_request_flow'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW_HANDLE',$b->body());
meq('AFTER_FLOW_HANDLE',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_EXEC_TEMPLATE',$b->body());
meq('AFTER_EXEC_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/

/***
# under_var
$b = b();
$b->do_get(test_map_url('under_var'));
eq(200,$b->status());
meq('hogehoge',$b->body());
meq('ABC',$b->body());
meq('INIT',$b->body());
*/

/***
#noop
$b = b();
$b->do_get(test_map_url('noop'));
eq(200,$b->status());
eq('<result><init_var>INIT</init_var></result>',$b->body());
*/

/***
#module_throw_exception
$b = b();
$b->do_get(test_map_url('module_throw_exception'));
eq(403,$b->status());
meq('<message group="" type="LogicException">flow handle begin exception</message>',$b->body());
*/


/***
#method_not_allowed
$b = b();
$b->do_get(test_map_url('method_not_allowed'));
eq(405,$b->status());
meq('<message group="" type="LogicException">Method Not Allowed</message>',$b->body());
*/

/***
# module_map

$b = b();
$b->do_get(test_map_url('module_map'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW_HANDLE',$b->body());
meq('AFTER_FLOW_HANDLE',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_EXEC_TEMPLATE',$b->body());
meq('AFTER_EXEC_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/

/***
# module_maps

$b = b();
$b->do_get(test_map_url('module_maps'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('AFTER_FLOW_HANDLE',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_EXEC_TEMPLATE',$b->body());
meq('AFTER_EXEC_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/

/***
# module_raise
$b = b();
$b->do_get(test_map_url('module_raise'));
eq(403,$b->status());
mneq('INDEX',$b->body());

meq('BEFORE_FLOW_HANDLE',$b->body());
meq('[EXCEPTION]',$b->body());

meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());

meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/

/***
# module_add_exceptions
$b = b();
$b->do_get(test_map_url('module_add_exceptions'));
eq(200,$b->status());
meq('INDEX',$b->body());
meq('BEFORE_FLOW',$b->body());
meq('AFTER_FLOW',$b->body());
meq('INIT_TEMPLATE',$b->body());
meq('BEFORE_TEMPLATE',$b->body());
meq('AFTER_TEMPLATE',$b->body());
meq('BEFORE_FLOW_PRINT_TEMPLATE',$b->body());
*/

/***
#raise
$b = b();
$b->do_get(test_map_url('raise'));
eq(403,$b->status());
*/

/***
# module_order
$b = b();
$b->do_get(test_map_url('module_order'));
eq(200,$b->status());
eq('345678910',$b->body());
*/

/***
# redirect_by_map
$b = b();
$b->do_get(test_map_url('redirect_by_map_method_a'));
eq(200,$b->status());
eq('REDIRECT_A',$b->body());

$b->do_get(test_map_url('redirect_by_map_method_b'));
eq(200,$b->status());
eq('REDIRECT_B',$b->body());

$b->do_get(test_map_url('redirect_by_map_method_c'));
eq(200,$b->status());
eq('REDIRECT_C',$b->body());
*/

/***
#notemplate
$b = b();
$b->do_get(test_map_url('notemplate'));
eq(200,$b->status());
eq('<result><abc>ABC</abc><newtag><hoge>HOGE</hoge></newtag></result>',$b->body());
*/

/***
# sample_flow_exception_package_throw_xml
$b = b();
$b->do_get(test_map_url('sample_flow_exception_package_throw_xml'));
eq(403,$b->status());
meq('<message group="" type="SampleException">sample error</message>',$b->body());
*/

/***
# sample_flow_exception_throw_xml
$b = b();
$b->do_get(test_map_url('sample_flow_exception_throw_xml'));
eq(403,$b->status());
meq('<message group="" type="LogicException">error</message></error>',$b->body());
*/

/***
$b = b();
$b->do_post(test_map_url('dao/insert'));
$b->do_post(test_map_url('dao/get'));
eq(200,$b->status());
meq('<string>abcdefg</string><text />',$b->body());

$b = b();
$b->do_post(test_map_url('dao/update'));
$b->do_post(test_map_url('dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = b();
$b->do_post(test_map_url('dao/delete'));

$b = b();
$b->do_post(test_map_url('dao/insert'));
$b->do_post(test_map_url('dao/get'));
meq('<string>abcdefg</string><text />',$b->body());

$b = b();
$b->do_post(test_map_url('dao/update'));
$b->do_post(test_map_url('dao/get'));
meq('<string>abcdefg</string><text>xyz</text>',$b->body());

$b = b();
$b->do_post(test_map_url('dao/delete'));
eq('<result />',$b->body());
*/

/***
$b = b();
$b->do_get(test_map_url('get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->do_get(test_map_url('set_session'));

$b->do_get(test_map_url('get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
*/

/***
$b = b();
$b->do_get(test_map_url('get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',test_map_url('get_session'));
$b->do_get(test_map_url('set_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
eq(test_map_url('get_session'),$b->url());
*/

/***
$b = b();
$b->do_get(test_map_url('get_session'));
eq('<result><abc /></result>',$b->body());

$b->vars('abc','hoge');
$b->vars('redirect',test_map_url('plain_noop'));
$b->do_get(test_map_url('set_session'));

$b->do_get(test_map_url('get_session'));
eq('<result><abc>hoge</abc></result>',$b->body());
*/

/***
$b = b();
$b->do_get(test_map_url('map_url'));
meq('test_index/noop',$b->body());
meq('test_login/aaa',$b->body());
*/

/***
# rt_exceptions

$b = b();
$b->do_get(test_map_url('rt_exceptions'));
meq('hoge',$b->body());
*/
/***
 #helper_range
$b = b();
$b->do_get(test_map_url('helper_range'));
meq('A1234A',$b->body());
meq('B12345B',$b->body());
meq('C12345678C',$b->body());
*/

/***
 #html_filter
$b = b();
$b->do_get(test_map_url('html_filter'));
meq('PPPPP',$b->body());
meq('&lt;tag&gt;QQQ&lt;/tag&gt;',$b->body());

meq('<input type="text" name="aaa" value="hogehoge" />',$b->body());
meq('<input type="text" name="ttt" value="&lt;tag&gt;ttt&lt;/tag&gt;" />',$b->body());
meq('<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge',$b->body());
meq('<input type="checkbox" name="bbb[]" value="fuga" />fuga',$b->body());
meq('<input type="checkbox" name="eee[]" value="true" checked="checked" />foo',$b->body());
meq('<input type="checkbox" name="fff[]" value="false" checked="checked" />foo',$b->body());
meq('<input type="submit" />',$b->body());
meq('<textarea name="aaa">hogehoge</textarea>',$b->body());
meq('<textarea name="ttt">&lt;tag&gt;ttt&lt;/tag&gt;</textarea>',$b->body());
meq('<option value="456" selected="selected">456</option>',$b->body());
meq('<option value="789" selected="selected">789</option>',$b->body());
meq('<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>',$b->body());
*/

/***
#csrf
$b = b();

$b->do_get(test_map_url('csrf'));
eq(200,$b->status());
meq('<result>',$b->body());

$b->do_post(test_map_url('csrf'));
eq(403,$b->status());
meq('<error>',$b->body());



$b->do_get(test_map_url('csrf'));
eq(200,$b->status());
meq('<result>',$b->body());

$no = null;
if(xml($xml,$b->body(),'csrftoken')){
	$no = $xml->value();
}
neq(null,$no);

$b->vars('csrftoken',$no);
$b->do_post(test_map_url('csrf'));
eq(200,$b->status());
meq('<result>',$b->body());


$b->do_get(test_map_url('csrf_template'));
eq(200,$b->status());
meq('<form><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="post"><input type="hidden" name="csrftoken"',$b->body());
meq('<form method="get"><input type="hidden" name="csrftoken"',$b->body());
meq(sprintf('<form action="%s"><input type="hidden" name="csrftoken"',test_map_url('csrf')),$b->body());
meq('<form action="http://localhost"><input type="text" name="aaa" /></form>',$b->body());
*/


