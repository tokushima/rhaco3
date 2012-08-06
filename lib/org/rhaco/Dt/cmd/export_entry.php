<?php
/**
 * エントリ一覧を出力する
 */
function output($output_file,$template_file,$vars,$option){
	list($template_base,$extends,$block,$map_url,$prefix) = $option;
	$helper = new \org\rhaco\Dt\Helper();
	$helper->set_html_replace_var($map_url,$prefix);
	
	$template = new \org\rhaco\Template();
	$template->template_super($template_base);
	
	$template->set_object_module(new \org\rhaco\Dt\Replace());
	$template->set_object_module(new \org\rhaco\flow\module\TwitterBootstrapPagination());
	$template->set_object_module(new \org\rhaco\flow\module\TwitterBootstrapExtHtml());
	foreach($vars as $k => $v) $template->vars($k,$v);
	$template->vars('t',new \org\rhaco\flow\module\Helper());
	$template->vars('f',$helper);

	$src = $template->read($template_file);
	if(!empty($extends) || !empty($block)){
		if(empty($extends)) $extends = '../index.html';
		if(empty($block)) $block = 'contents';
		
		$src = str_replace('<replace:extends />',sprintf('<rt:extends href="%s" />',$extends),$src);
		$src = str_replace('<replace:block>',sprintf('<rt:block name="%s">',$block),$src);
		$src = str_replace('</replace:block>','</rt:block>',$src);
	}
	file_put_contents($output_file,$src);
}
$entry = isset($_ENV['params']['entry']) ? $_ENV['params']['entry'] : 'index';
$mode = isset($_ENV['params']['mode']) ? $_ENV['params']['mode'] : null;
$extends = isset($_ENV['params']['extends']) ? $_ENV['params']['extends'] : null;
$block = isset($_ENV['params']['block']) ? $_ENV['params']['block'] : null;
$prefix = isset($_ENV['params']['prefix']) ? $_ENV['params']['prefix'] : null;
$map_url = isset($_ENV['params']['map_url']) ? $_ENV['params']['map_url'] : null;
$base = isset($_ENV['params']['template']) ? $_ENV['params']['template'] : null;
$zip = isset($_ENV['params']['zip']) ? $_ENV['params']['zip'] : null;
$out_dir = isset($_ENV['params']['out']) ? $_ENV['params']['out'] : \org\rhaco\io\File::work_path('export_entry/');

if(substr($out_dir,-1) != '/') $out_dir = $out_dir.'/';
$zip_dir = \org\rhaco\io\File::work_path();
$path = getcwd().'/'.$entry.'.php';
if(!is_file($path)) throw new \RuntimeException('Entry `'.$entry.'` not found');


$template_base = (empty($base)) ? dirname(__DIR__).'/resources/export_templates/'.((!empty($extends) || !empty($block)) ? 'block.html' : 'base.html') : $base;
$template_dir = dirname(__DIR__).'/resources/templates/';

\org\rhaco\io\File::mkdir($out_dir);
\org\rhaco\io\File::copy(dirname(__DIR__).'/resources/media/bootstrap/css/bootstrap.min.css',$out_dir);

$option = array($template_base,$extends,$block,$map_url,$prefix);
$self_name = 'org.rhaco.Dt';
$maps = array();
foreach(\org\rhaco\Flow::get_maps($path) as $k => $m){
	if(!isset($m['class']) || $m['class'] != $self_name){
		$bool = false;
		if(isset($m['mode']) && isset($mode) && !empty($mode)){		
			foreach(explode(',',$m['mode']) as $expmode){
				if($mode == trim($expmode)){
					$bool = true;
					break;
				}
			}
		}else{
			$bool = true;
		}
		if($bool){
			$m['summary'] = $m['error'] = '';
			if(isset($m['class']) && isset($m['method'])){
				try{
					$cr = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$m['class']));
					$mr = $cr->getMethod($m['method']);
					list($m['summary']) = explode("\n",trim(preg_replace("/@.+/","",preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$mr->getDocComment())))));
				}catch(\ReflectionException $e){
					$m['error'] = $e->getMessage();
				}
			}
			$maps[$k] = $m;
		}
	}
}
$output_file = $out_dir.'index.html';
$template_file = $template_dir.'index.html';
$vars = array('app_name'=>$entry,'maps'=>$maps);
output($output_file,$template_file,$vars,$option);


// class list 
$class_list = $classes = array();
foreach(\org\rhaco\Man::libs() as $package => $info){
	$r = new \ReflectionClass($info['class']);
	$class_doc = $r->getDocComment();
	$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
	list($summary) = explode("\n",$document);

	$src = file_get_contents($r->getFileName());
	$c = new \org\rhaco\Object();
	$c->summary = $summary;
	$c->usemail = (strpos($src,'\org'.'\rhaco'.'\net'.'\mail'.'\Mail') !== false);
	$class_list[$package] = $c;
	if(strpos($package,'test.') !== 0 && strpos($class_doc,'@incomplete') === false) $classes[$package] = $c;
}
ksort($class_list);

$output_file = $out_dir.'classes.html';
$template_file = $template_dir.'classes.html';
$vars = array('app_name'=>$entry,'class_list'=>$classes);
output($output_file,$template_file,$vars,$option);

//class info
foreach($class_list as $package => $c){
	$class_info = \org\rhaco\Man::class_info($package);

	foreach(array(
		'static_methods','methods','protected_static_methods','protected_methods',
		'inherited_methods','inherited_static_methods','inherited_protected_static_methods','inherited_protected_methods'
	) as $k){
		foreach($class_info[$k] as $method => $doc){
			$method_info = \org\rhaco\Man::method_info($package,$method);
			$output_file = $out_dir.$package.'__'.$method.'.html';
			$template_file = $template_dir.'method_info.html';
			$vars = array_merge($method_info,array('app_name'=>$entry));
			output($output_file,$template_file,$vars,$option);
		}
	}
	foreach($class_info['modules'] as $module_name => $v){
		$vars = array(
					'package'=>$package,
					'module_name'=>$module_name,
					'description'=>$v[0],
					'params'=>$v[1],
					'return',$v[2],
				);
		$output_file = $out_dir.$package.'___'.$module_name.'.html';
		$template_file = $template_dir.'class_module_info.html';
		$vars = array_merge($vars,array('app_name'=>$entry));
		output($output_file,$template_file,$vars,$option);
	}
	$output_file = $out_dir.$package.'.html';
	$template_file = $template_dir.'class_info.html';
	$vars = array_merge($class_info,array('app_name'=>$entry));
	output($output_file,$template_file,$vars,$option);
}

if(isset($zip)){
	$arc = new \org\rhaco\io\Archive();
	$arc->add($out_dir,$out_dir);
	$arc->zipwrite($zipfile=($zip_dir.'entry_export_'.$entry.'_'.date('YmdHis').(empty($mode) ? '' : '_'.$mode).'.zip'));
	\org\rhaco\io\File::rm($out_dir);
	\org\rhaco\lang\AnsiEsc::println('Output file: '.$zipfile,true);
}else{
	\org\rhaco\lang\AnsiEsc::println('Output files: '.$out_dir,true);	
}