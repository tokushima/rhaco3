<?php
include_once('rhaco3.php');
/**
 * @name rhaco.org
 * @summary site
 */
\org\rhaco\Flow::out(
array(''
,'error_status'=>403
,'patterns'=>array(''
	,''=>array(
		'name'=>'index'
		,'template'=>'index.html'
		,'vars'=>array(
			'xml_var'=>'hogehoge_xml_var'
			,'xml_var_value'=>'hogehoge_xml_var_value'
			,'xml_var_array'=>array('A1','A2','A3')
			,'xml_var_obj'=>new \test\FlowVar()
			,'xml_var_objC'=>\test\FlowVar::ccc()
		)
	)
	,'sample_flow_theme_media'=>array(
		'theme_path'=>'theme_path'
		,'action'=>'test.SampleMediaFlow'
		,'patterns'=>array(
			''=>array('name'=>'sample_flow_theme_media_index','action'=>'index','template'=>'sample_media.html')
			,'hoge'=>array('name'=>'sample_flow_theme_media_hoge','action'=>'hoge','template'=>'sample_media.html')
		)
	)
	,'sample_flow_theme_media_plain'=>array(
		'media_path'=>'media_path'
		,'action'=>'test.SampleMediaFlow'
		,'patterns'=>array(
			''=>array('name'=>'sample_flow_theme_media_plain_index','action'=>'index','template'=>'sample_media.html')
			,'hoge'=>array('name'=>'sample_flow_theme_media_plain_hoge','action'=>'hoge','template'=>'sample_media.html')
		)
	)
	,'plain_theme/template_path_theme_html'=>array('name'=>'template_path_theme_html','template_path'=>'template_path','theme_path'=>'theme_path','template'=>'index.html')
	,'plain_theme/template_path_html'=>array('name'=>'template_path_html','template'=>'index.html','template_path'=>'template_path')
	,'module'=>array('name'=>'module','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule')
	,'module_request_flow'=>array('name'=>'module_request_flow','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','action'=>'org.rhaco.flow.parts.RequestFlow::noop')
	,'sample_flow_html'=>array('name'=>'sample_flow_html','template'=>'sample.html')
	,'sample_media_flow'=>array('name'=>'sample_media_flow','action'=>'test.SampleMediaFlow','media_path'=>'media_path')
	,'sample_flow_exception/throw'=>array('name'=>'sample_flow_exception_throw','action'=>'test.SampleExceptionFlow::throw_method','error_template'=>'exception_flow/error.html')
	,'sample_flow_exception/throw/xml'=>array('name'=>'sample_flow_exception_throw_xml','action'=>'test.SampleExceptionFlow::throw_method')
	,'sample_flow_exception/throw/xml/package'=>array('name'=>'sample_flow_exception_package_throw_xml','action'=>'test.SampleExceptionFlow::throw_method_package')	
	,'extends_block_template/extendsA'=>array('name'=>'template_super_a','template'=>'template_super.html')
	,'extends_block_template/extendsB'=>array('name'=>'template_super_b','template'=>'template_super.html','template_super'=>'template_super_x.html')
	
	,'under_var'=>array('name'=>'under_var','action'=>'test.CoreApp::under_var','template'=>'under_var.html')
	,'module_throw_exception'=>array('name'=>'module_throw_exception','action'=>'test.CoreApp::noop','modules'=>'test.flow.module.CoreTestExceptionModule')
	,'noop'=>array('name'=>'noop','action'=>'test.CoreApp::noop')
	,'method_not_allowed'=>array('name'=>'method_not_allowed','action'=>'test.CoreApp::method_not_allowed')
	,'module_map'=>array('name'=>'module_map','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule')
	,'module_maps'=>array('name'=>'module_maps','template'=>'module_index.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModule')
	,'module_raise'=>array('name'=>'module_raise','action'=>'test.CoreApp::raise','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','error_template'=>'module_exception.html')
	,'module_add_exceptions'=>array('name'=>'module_add_exceptions','action'=>'test.CoreApp::add_exceptions','template'=>'module_index.html','modules'=>'test.flow.module.CoreTestModule','error_template'=>'module_exception.html')
	,'raise'=>array('name'=>'raise','action'=>'test.CoreApp::raise')
	,'module_order'=>array('name'=>'module_order','template'=>'module_order.html','action'=>'org.rhaco.flow.parts.RequestFlow::noop','modules'=>'test.flow.module.CoreTestModuleOrder')
	
	,'redirect_test_a'=>array('name'=>'redirect_by_map_method_a','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_a')
	,'redirect_test_call_a'=>array('name'=>'redirect_by_map_method_call_a','template'=>'redirect_test_call_a.html')
	,'redirect_test_b'=>array('name'=>'redirect_by_map_method_b','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_b','args'=>array('redirect_by_map_method_call_b'=>'redirect_by_map_method_call_alias_b'))
	,'redirect_test_call_b'=>array('name'=>'redirect_by_map_method_call_alias_b','template'=>'redirect_test_call_b.html')
	,'redirect_test_c'=>array('name'=>'redirect_by_map_method_c','action'=>'test.CoreTestRedirectMapA::redirect_by_map_method_c','args'=>array('redirect_by_map_method_call_c'=>'redirect_by_map_method_call_alias_c'))
	,'redirect_test_call_c'=>array('name'=>'redirect_by_map_method_call_alias_c','template'=>'redirect_test_call_c.html')
	,'redirect_test_call_c_e'=>array('name'=>'redirect_by_map_method_call_c','template'=>'redirect_test_call_c_e.html')

	,'notemplate'=>array('name'=>'notemplate','action'=>'test.CoreTestNotTemplate::aaa')
	
	,'put_block'=>array('name'=>'put_block','action'=>'test.CoreTestPutBlock::index','template'=>'put_block.html')
	,'theme'=>array('name'=>'theme','action'=>'test.CoreTestTheme::index','template'=>'abc.html','theme_path'=>'custom_theme')
	,'theme_maps'=>array('name'=>'theme_maps','action'=>'test.CoreTestTheme::index','template'=>'abc.html','theme_path'=>'custom_theme','theme_path'=>'custom_theme')

	,'theme_none'=>array('name'=>'theme_none','action'=>'test.CoreTestTheme::index','template'=>'abc.html')
	,'sample_flow'=>array('name'=>'sample_flow','action'=>'test.SampleFlow')
	
	,'sample_flow_theme'=>array(
		'theme_path'=>'theme_path'
		,'patterns'=>array(
			''=>array('name'=>'sample_flow_theme_index','action'=>'test.SampleFlow::index','template'=>'sample.html')
			,'hoge'=>array('name'=>'sample_flow_theme_hoge','action'=>'test.SampleFlow::hoge','template'=>'sample.html')
		)
	)
	,'sample_flow_theme_not_path'=>array(
		'patterns'=>array(
			''=>array('name'=>'sample_flow_theme_not_index','action'=>'test.SampleFlow::index','template'=>'sample.html')
			,'hoge'=>array('name'=>'sample_flow_theme_not_hoge','action'=>'test.SampleFlow::hoge','template'=>'sample.html')
		)
	)
	,'upload_multi'=>array('name'=>'upload_multi','action'=>'test.SampleFlow::upload_multi')
	,'upload_value'=>array('name'=>'upload_value','action'=>'test.SampleFlow::upload_value')
	,'upload_file'=>array('name'=>'upload_file','action'=>'test.SampleFlow::upload_file')
	
	,'dev'=>array('action'=>'org.rhaco.Dt','mode'=>'local')
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
nmeq('CCC',$b->body());

$b = b();
$b->vars('hoge','b');
$b->do_get(test_map_url('put_block'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('b',$b->body());
nmeq('CCC',$b->body());
*/

/***
# theme
$b = b();
$b->do_get(test_map_url('theme'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('NONE',$b->body());
meq("/resources/media/custom_theme/default/123\">a</a>",$b->body());

$b = b();
$b->vars('hoge','aaa');
$b->do_get(test_map_url('theme'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('xxx',$b->body());
meq('/resources/media/custom_theme/aaa/123',$b->body());

$b = b();
$b->vars('hoge','bbb');
$b->do_get(test_map_url('theme'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('yyy',$b->body());
meq('/resources/media/custom_theme/bbb/123',$b->body());
*/

/***
# theme_maps
$b = b();
$b->do_get(test_map_url('theme_maps'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('NONE',$b->body());
meq('/resources/media/custom_theme/default/123',$b->body());

$b = b();
$b->vars('hoge','aaa');
$b->do_get(test_map_url('theme_maps'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('xxx',$b->body());
meq('/resources/media/custom_theme/aaa/123',$b->body());

$b = b();
$b->vars('hoge','bbb');
$b->do_get(test_map_url('theme_maps'));
meq('AAA',$b->body());
meq('BBB',$b->body());
meq('yyy',$b->body());
meq('/resources/media/custom_theme/bbb/123',$b->body());
*/

/***
# theme_none
$b = b();
$b->do_get(test_map_url('theme_none'));
eq(403,$b->status());

$b = b();
$b->vars('hoge','aaa');
$b->do_get(test_map_url('theme_none'));
eq(403,$b->status());

$b = b();
$b->vars('hoge','bbb');
$b->do_get(test_map_url('theme_none'));
eq(403,$b->status());
*/

/***
# sample_flow

$b = b();

$b->do_get(test_map_url('sample_flow_html'));
eq('SAMPLE',$b->body());

$b->do_get(test_map_url('template_path_html'));
eq('INDEX',$b->body());

$b->do_get(test_map_url('template_path_theme_html'));
eq('THEME',$b->body());

$b->do_get(test_map_url('sample_flow_theme_index'));
eq('DEFAULT',$b->body());
$b->do_get(test_map_url('sample_flow_theme_index').'?view=blue');
eq('BLUE',$b->body());
$b->do_get(test_map_url('sample_flow_theme_index').'?view=red');
eq('RED',$b->body());
$b->do_get(test_map_url('sample_flow_theme_index').'?view=green');
eq('DEFAULT',$b->body());

$b->do_get(test_map_url('sample_flow_theme_hoge'));
eq('DEFAULT',$b->body());

$b->do_get(test_map_url('sample_flow_theme_not_index'));
eq('SAMPLE',$b->body());
$b->do_get(test_map_url('sample_flow_theme_not_index').'?view=blue');
eq('blue',$b->body());
$b->do_get(test_map_url('sample_flow_theme_not_index').'?view=red');
eq('red',$b->body());
$b->do_get(test_map_url('sample_flow_theme_not_index').'?view=green');
eq('default',$b->body());

$b->do_get(test_map_url('sample_flow_theme_not_hoge'));
eq('SAMPLE',$b->body());


$b->do_get(test_map_url('sample_flow/index'));
eq('SAMPLE_FLOW_INDEX',$b->body());
$b->do_get(test_map_url('sample_flow/index').'?view=blue');
eq('SAMPLE_FLOW_BLUE',$b->body());
$b->do_get(test_map_url('sample_flow/index').'?view=red');
eq('SAMPLE_FLOW_RED',$b->body());
$b->do_get(test_map_url('sample_flow/index').'?view=green');
eq('SAMPLE_FLOW_DEFAULT',$b->body());

$b->do_get(test_map_url('sample_flow/hoge'));
eq('SAMPLE_FLOW_HOGE',$b->body());
*/


/***
# sample_media_flow

$b = b();

$b->do_get(test_map_url('sample_media_flow/index'));
eq(true,(1 == preg_match('/\/package\/resources\/media\/\d+\/hoge.jpg/',$b->body())));

$b->do_get(test_map_url('sample_media_flow/hoge'));
eq(true,(1 == preg_match('/\/package\/resources\/media\/\d+\/hoge.jpg/',$b->body())));

$b->do_get(test_map_url('sample_flow_theme_media_index'));
meq('resources/media/theme_path/default/hoge.jpg',$b->body());

$b->do_get(test_map_url('sample_flow_theme_media_index').'?view=blue');
meq('resources/media/theme_path/blue/hoge.jpg',$b->body());
$b->do_get(test_map_url('sample_flow_theme_media_index').'?view=red');
meq('resources/media/theme_path/red/hoge.jpg',$b->body());

$b->do_get(test_map_url('sample_flow_theme_media_hoge'));
meq('resources/media/theme_path/default/hoge.jpg',$b->body());


$b->do_get(test_map_url('sample_flow_theme_media_index'));
meq('resources/media/theme_path/default/hoge.jpg',$b->body());

$b->do_get(test_map_url('sample_flow_theme_media_hoge'));
meq('resources/media/theme_path/default/hoge.jpg',$b->body());

$b->do_get(test_map_url('sample_flow_theme_media_plain_index'));
meq('/resources/media/media_path/hoge.jpg',$b->body());

$b->do_get(test_map_url('sample_flow_theme_media_plain_hoge'));
meq('/resources/media/media_path/hoge.jpg',$b->body());
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
eq('<error><message group="exceptions" class="LogicException" type="LogicException">flow handle begin exception</message></error>',$b->body());
*/


/***
#method_not_allowed
$b = b();
$b->do_get(test_map_url('method_not_allowed'));
eq(405,$b->status());
eq('<error><message group="exceptions" class="LogicException" type="LogicException">Method Not Allowed</message></error>',$b->body());
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
nmeq('INDEX',$b->body());

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
meq('EXCEPTION_FLOW_HANDLE',$b->body());
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
eq('<error><message group="exceptions" class="test.exception.SampleException" type="SampleException">sample error</message></error>',$b->body());
*/

/***
# sample_flow_exception_throw_xml
$b = b();
$b->do_get(test_map_url('sample_flow_exception_throw_xml'));
eq(403,$b->status());
eq('<error><message group="exceptions" class="LogicException" type="LogicException">error</message></error>',$b->body());
*/


