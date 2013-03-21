<?php
// import先のライブラリのフォルダ
$output = getcwd().'/lib';
// 検索するライブラリのリポジトリ
$repository = array(
					'http://rhaco.org/repository/3/lib/',
					dirname(__DIR__).'/pkgman/repository',
				);


set_time_limit(0);

$imported = array();
$package = $_SERVER['argv'][1];
package_import($imported,$package,$repository,$output);


function println($value,$fmt=null,$indent=0){
	if($indent > 0) $value = str_repeat(' ',$indent).implode(PHP_EOL.str_repeat(' ',$indent),explode(PHP_EOL,$value));
	if(substr(PHP_OS,0,3) == 'WIN'){
		$value = mb_convert_encoding($value,'UTF-8','SJIS');
	}else if($fmt !== null){
		$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
		$value = "\033[".$fmt.'m'.$value."\033[0m";
	}
	print($value.PHP_EOL);
}
function rm($d,$t){
	if(is_dir($d)){
		$f = array();
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS),RecursiveIteratorIterator::SELF_FIRST) as $e) $f[] = $e->getPathname();
		rsort($f);
		foreach($f as $p) ((is_file($p)) ? unlink($p) : rmdir($p));
		if($t && is_dir($d)) rmdir($d);
	}
}
function search($s){
	$z = $u = array();
	$s = preg_replace("/\/\*.+?\*\//s",'',$s);
	if(preg_match_all('/([\"\'])(\w+\.[\.\w:]+?)\\1/',$s,$m)){
		foreach($m[2] as $c){
			if(strpos($c,':') !== false) list($c) = explode(':',$c);
			if(preg_match('/\.[A-Z]\w*$/',$c)) $z['\\'.str_replace('.','\\',$c)] = '\\'.str_replace('.','\\',$c);
		}
	}
	$s = preg_replace("/([\"\']).*?\\1/",'',$s);
	if(preg_match_all('/use\s+([\w\\\\]+)\s*;/',$s,$m)){
		foreach($m[1] as $c) $z[$c] = $u[preg_replace('/^.+\\\\([^\\\\]+)$/','\\1',$c)] = (($c[0] != "\\") ? "\\" : '').$c;
	}
	if(preg_match_all('/use\s+([\w\\\\]+)\s+as\s+(\w+)\s*;/',$s,$m)){
		foreach($m[1] as $k => $c) $z[$c] = $u[$m[2][$k]] = (($c[0] != "\\") ? "\\" : '').$c;
	}
	if(preg_match_all('/\s+instanceof\s+([\\\\\w]+)/',$s,$m)){
		foreach($m[1] as $k => $c) $z[$c] = $c;
	}
	if(preg_match_all('/\s+class_exists\(([\"\'])([\\\\\w]+)\\1/',$s,$m)){
		foreach($m[2] as $k => $c) $z[$c] = $c;
	}
	if(preg_match('/\s+extends\s+([\\\\\w]+)/',$s,$m)) $z[$m[1]] = $m[1];
	if(preg_match_all('/new ([\w\\\\]+)|([\w\\\\]+)::/',$s,$m)){
		for($i=1;$i<=2;$i++){
			foreach($m[$i] as $c){
				$z[$c] = $c;
			}
		}
	}
	if(preg_match('/implements\s+([\\\\\w,\s]+)/ms',$s,$m)){
		foreach(explode(',',$m[1]) as $r) $z[trim($r)] = trim($r);
	}
	if(!empty($z)){
		$n = ((preg_match("/namespace ([\w\\\\]+)/",$s,$m)) ? $m[1] : null);
		if(!empty($n)) $n = '\\'.$n;
		foreach($z as $k => $v){
			$r = $k;
			if(!empty($k)){
				if(isset($u[$k])){
					$r = $u[$k];
				}else if(!empty($n) && strpos($v,'\\') !== 0){
					$r = $n.'\\'.$k;
				}
				if($r != $k) unset($z[$k]);
				$z[$r] = str_replace('\\','.',preg_replace('/^\\\\(.+)$/','\\1',$r));
			}
			if(empty($k) || !preg_match('/[A-Z]/',$r) || preg_match('/^(.*[A-Z].*)\\\\\w+$/',$r) || strpos(substr($r,1),'\\') === false) unset($z[$r]);
		}
	}
	return $z;
}
function package_import(&$imported,$package,$repository,$output=null){
	if(isset($imported[$package])) return true;
	$imported[$package] = true;

	$dl = str_replace(array('/',':','-'),array('.','_','_'),$package);
	$vp = str_replace('\\','/',isset($output) ? $output : getcwd().'/_vendor');
	if(substr($vp,-1) != '/') $vp = $vp.'/';
	$dp = $vp.'_download/';
	$ep = $dp.'/extract/'.$package.'/';

	if(!is_dir($vp)) mkdir($vp,0777,true);
	foreach($repository as $rp){
		try{
			$rp = str_replace('\\','/',$rp);
			if(substr($rp,-1) != '/') $rp = $rp.'/';
			if(!is_dir($dp)) mkdir($dp,0777,true);
			if(is_file($dp.$dl.'.tgz')) unlink($dp.$dl.'.tgz');
			
			if(strpos($rp,'://') !== false){
				$fr = fopen($rp.$dl.'.tgz','rb');
				$fw = fopen($dp.$dl.'.tgz','wb');
				while(!feof($fr)) fwrite($fw,fread($fr,4096));
				fclose($fr);
				fclose($fw);
			}else{
				if(is_file($rp.$dl.'.tgz')){
					copy($rp.$dl.'.tgz',$dp.$dl.'.tgz');
				}
			}
			if(is_file($dp.$dl.'.tgz')){
				$p = str_replace('.','/',$package);
				if(is_file($vp.$p.'.php')) unlink($vp.$p.'.php');
				if(is_file($vp.$p.'/'.basename($p).'.php')) rm($vp.$p,true);
				$fp = gzopen($dp.$dl.'.tgz','rb');
				
				while(!gzeof($fp)){
					$b = gzread($fp,512);
					if(strlen($b) < 512) break;
					$d = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/'
							.'a8chksum/'
							.'a1typeflg/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix',
							$b);
					if(!empty($d['name'])){
						if($d['name'][0] == '/') $d['name'] = substr($d['name'],1);
						if(ctype_alnum($d['name'][0])){
							$path = $ep.$d['name'];
							if(ctype_digit($d['typeflg'])){
								switch((int)$d['typeflg']){
									case 0:
										$size = base_convert($d['size'],8,10);
										if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
										for($i=0;$i<=$size;$i+=512){
											$s = ($i+512>$size) ? $size - $i : 512;
											if($s > 0){
												file_put_contents($path,gzread($fp,$s),FILE_APPEND);
												if($s < 512) gzread($fp,512-$s);
											}
										}
										touch($path,base_convert($d['mtime'],8,10));
										break;
									case 5:
										if(!is_dir($path)) mkdir($path,0777,true);
										break;
								}
							}
						}
					}
				}
				gzclose($fp);
				$required = array();
				if(!is_dir($ep)) return false;
				
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ep,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					$so = str_replace($ep,$vp,$f->getPathname());
					if(!is_dir(dirname($f->getPathname()))) mkdir(dirname($f->getPathname()),0777,true);
					if(!is_dir(dirname($so))) mkdir(dirname($so),0777,true);
					copy($f->getPathname(),$so);
					if(substr($f->getPathname(),-4) == '.php'){
						$required = array_merge($required,search(file_get_contents($f->getPathname())));
					}
				}
				foreach($required as $r){
					if(!package_import($imported,$r,$repository,$output)){
						println('not found: '.$package.' > '.$package.' in '.$r,false);
					}
				}
				rm($dp,true);
				println('installed: '.$package.' ('.$rp.')');
				return true;
			}
		}catch(Exception $e){
			println($rp.$dl.'.tgz'.' => '.$e->getMessage(),false);
		}
	}
	return false;
}
