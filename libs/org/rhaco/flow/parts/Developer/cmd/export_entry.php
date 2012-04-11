<?php
/**
 * エントリ一覧を出力する
 */
$entry = isset($_ENV['params']['entry']) ? $_ENV['params']['entry'] : 'index';
$mode = isset($_ENV['params']['mode']) ? $_ENV['params']['mode'] : null;
$base = isset($_ENV['params']['template']) ? $_ENV['params']['template'] : null;

$zip_dir = str_replace("\\",'/',isset($_ENV['params']['o']) ? $_ENV['params']['o'] : \org\rhaco\io\File::work_path());
$zip_dir = (substr($zip_dir,-1) == '/') ? $zip_dir : $zip_dir.'/';
$path = getcwd().'/'.$entry.'.php';
if(!is_file($path)) throw new \RuntimeException('Entry `'.$entry.'` not found');

$out_dir = \org\rhaco\io\File::work_path('export_'.time().'/');
$template_base = (empty($base)) ? dirname(__DIR__).'/resources/export_templates/base.html' : $base;
$template_dir = dirname(__DIR__).'/resources/templates/';

\org\rhaco\io\File::mkdir($out_dir);
\org\rhaco\io\File::copy(dirname(__DIR__).'/resources/media/bootstrap/css/bootstrap.min.css',$out_dir);

$self_name = 'org.rhaco.flow.parts.Developer';
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
$template = template($template_base,array('app_name'=>$entry,'maps'=>$maps));
file_put_contents($out_dir.'index.html',$template->read($template_dir.'index.html'));


// class list 
$class_list = array();
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
}
ksort($class_list);

$template = template($template_base,array('app_name'=>$entry,'class_list'=>$class_list));
file_put_contents($out_dir.'classes.html',$template->read($template_dir.'classes.html'));

//class info
foreach($class_list as $package => $c){
	$class_info = \org\rhaco\Man::class_info($package);

	foreach(array('static_methods','methods') as $k){
		foreach($class_info[$k] as $method => $doc){
			$method_info = \org\rhaco\Man::method_info($package,$method);
			$template = template($template_base,array_merge($method_info,array('app_name'=>$entry)));
			file_put_contents($out_dir.$package.'__'.$method.'.html',$template->read($template_dir.'method_info.html'));
		}
	}
	$template = template($template_base,array_merge($class_info,array('app_name'=>$entry)));
	file_put_contents($out_dir.$package.'.html',$template->read($template_dir.'class_info.html'));
}

$arc = new \org\rhaco\io\Archive();
$arc->add($out_dir,$out_dir);
$arc->zipwrite($zipfile=($zip_dir.'entry_export_'.$entry.'_'.date('YmdHis').(empty($mode) ? '' : '_'.$mode).'.zip'));

\org\rhaco\io\File::rm($out_dir);

\org\rhaco\lang\AnsiEsc::println('Output: '.$zipfile,true);
