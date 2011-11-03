<?php
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
function dirs($dir,$base){
	if(substr($dir,-1) == '/') $dir = substr($dir,0,-1);
	if(substr($base,-1) != '/') $base = $base.'/';
	$list = array(5=>array(),0=>array());
	if(is_file($dir)){
		$list[0][str_replace($base,'',$dir)] = $dir;
	}else{
		if($h = opendir($dir)){
			while($p = readdir($h)){
				if($p != '.' && $p != '..'){
					$s = sprintf('%s/%s',$dir,$p);
					if(is_dir($s)){
						$list[5][str_replace($base,'',$s)] = $s;
						$r = dirs($s,$base);
						$list[5] = array_merge($list[5],$r[5]);
						$list[0] = array_merge($list[0],$r[0]);
					}else{
						$list[0][str_replace($base,'',$s)] = $s;
					}
				}
			}
			closedir($h);
		}
	}
	return $list;
}
function tgz($base,$input,$output){
	$fp = gzopen($output,'wb9');
		tar($base,$input,$output.'.tar');
		$fr = fopen($output.'.tar','rb');
			while(!feof($fr)) gzwrite($fp,fread($fr,4096));
		fclose($fr);
	gzclose($fp);
	unlink($output.'.tar');
	print('Written: '.realpath($output).PHP_EOL);
}
function tar_head($type,$filename,$filesize=0,$fileperms=0644,$uid=0,$gid=0,$update_date=null){
	if(strlen($filename) > 99) throw new \InvalidArgumentException('invalid filename (max length 100) `'.$filename.'`');
	if($update_date === null) $update_date = time();
	$checksum = 256;
	$first = pack('a100a8a8a8a12A12',$filename,
					sprintf('%06s ',decoct($fileperms)),sprintf('%06s ',decoct($uid)),sprintf('%06s ',decoct($gid)),
					sprintf('%011s ',decoct(($type === 0) ? $filesize : 0)),sprintf('%11s',decoct($update_date)));
	$last = pack('a1a100a6a2a32a32a8a8a155a12',$type,null,null,null,null,null,null,null,null,null);
	for($i=0;$i<strlen($first);$i++) $checksum += ord($first[$i]);
	for($i=0;$i<strlen($last);$i++) $checksum += ord($last[$i]);
	return $first.pack('a8',sprintf('%6s ',decoct($checksum))).$last;
}
function tar($base,$input,$output){
	$fp = fopen($output,'wb');
	$tree = dirs($input,$base);
	$man = null;

	foreach(array(5,0) as $t){
		if(!empty($tree[$t])) ksort($tree[$t]);
		foreach($tree[$t] as $a => $n){
			$n = realpath($n);
			if(strpos($n,'/.') === false){
				if($t == 0){
					$i = stat($n);
					$rp = fopen($n,'rb');
						fwrite($fp,tar_head($t,$a,filesize($n),fileperms($n),$i[4],$i[5],filemtime($n)));
						while(!feof($rp)){
							$buf = fread($rp,512);
							if($buf !== '') fwrite($fp,pack('a512',$buf));
						}
					fclose($rp);
				}else{
					fwrite($fp,tar_head($t,$a,0,0777));
				}
			}
		}
	}
	fwrite($fp,pack('a1024',null));
	fclose($fp);
}
function get_summary($path){
	if(is_file($path) && preg_match('/\/\*\*[^\*](.+?)\*\//ms',file_get_contents($path),$m)){
		list($summary) = explode("\n",trim(preg_replace('/@.+/','',preg_replace('/^[\s]*\*[\s]{0,1}/m','',str_replace(array('/'.'**','*'.'/'),'',$m[0])))));
		return trim($summary);
	}
}
function last_update($filename){
	if(is_dir($filename)){
		$last_update = 0;
		$dirs = dirs($filename,$filename);
		foreach($dirs[0] as $file){
			$ft = filemtime($file);
			if($last_update < $ft) $last_update = $ft;
		}
		return $last_update;
	}
	return is_file($filename) ? filemtime($filename) : 0;
}
$input_path = str_replace('\\','/',isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : getcwd().'/_repository');
$output_path = str_replace('\\','/',isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : getcwd().'/repository');

if(!is_dir($input_path)) die('Permission denied '.$input_path);
if(substr($input_path,-1) != '/') $input_path = $input_path.'/';
if(substr($output_path,-1) != '/') $output_path = $output_path.'/';
if(!is_dir($output_path)) mkdir($output_path,0777,true);
$dup = array();
$list = dirs($input_path,$input_path);
$docs = null;
foreach($list[0] as $p){
	if(substr($p,-4) == '.php'){
		$r = str_replace("\\",'/',$p);
		$n = substr(basename($r),0,-4);
		$b = true;

		if(ctype_upper($n[0])){
			foreach($dup as $d){
				if(strpos($r,$d) === 0){
					$b = false;
					break;
				}
			}
			if($b && strpos(file_get_contents($r),'@unfinished') === false){
				if(preg_match("/^(.*)\/(\w+)\/(\w+)\.php$/",$r,$m) && $m[2] == $m[3] && !preg_match('/[A-Z]/',str_replace($input_path,'',$m[1]))){
					$dir = dirname($r);
					$dup[] = $dir.'/';
					$package = str_replace(array($input_path,'/'),array('','.'),$dir);
					if(strpos($package,'test.') !== 0){
						tgz($input_path,$dir,$output_path.$package.'.tgz');
						$time = last_update($dir);
						if(0 < $time){
							$tn = $output_path.$package.'_'.date('Ymd',$time).'.tgz';
							copy($output_path.$package.'.tgz',$tn);
							touch($tn,$time);
							touch($output_path.$package.'.tgz',$time);
						}
						$docs = $docs.$package.','.get_summary($dir.'/'.basename($dir).'.php')."\n";
					}
				}else if(!preg_match('/[A-Z]/',str_replace($input_path,'',dirname($r)))){
					$package = str_replace(array($input_path,'/'),array('','.'),substr($r,0,-4));
					if(strpos($package,'test.') !== 0){
						tgz($input_path,$r,$output_path.$package.'.tgz');			
						$time = last_update($r);
						if(0 < $time){
							$tn = $output_path.$package.'_'.date('Ymd',$time).'.tgz';
							copy($output_path.$package.'.tgz',$tn);
							touch($tn,$time);
							touch($output_path.$package.'.tgz',$time);
						}
						$docs = $docs.$package.','.get_summary($r)."\n";
					}
				}
			}
		}
	}
}
file_put_contents($output_path.'packages.csv',trim($docs));

