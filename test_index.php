<?php
include_once(__DIR__.'/bootstrap.php');

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
		
	'secure'=>array(
		'secure'=>true,
		'name'=>'secure',
		'template'=>'secure.html',
	),
)));

