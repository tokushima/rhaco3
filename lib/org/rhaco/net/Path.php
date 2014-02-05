<?php
namespace org\rhaco\net;
/**
 * ファイルパスやURLを操作するクラス
 * @author tokushima
 */
class Path{
	/**
	 * 絶対パスを返す
	 * @param string $a
	 * @param string $b
	 * @return string
	 */
	static public function absolute($a,$b){
		$a = str_replace("\\",'/',$a);
		if($b === '' || $b === null) return $a;
		$b = str_replace("\\",'/',$b);
		if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)) return $b;
		if(preg_match("/^[\w]+\:\/\/[^\/]+/",$a,$h)){
			$a = preg_replace("/^(.+?)[".(($b[0] === '#') ? '#' : "#\?")."].*$/","\\1",$a);
			if($b[0] == '#' || $b[0] == '?') return $a.$b;
			if(substr($a,-1) != '/') $b = (substr($b,0,2) == './') ? '.'.$b : (($b[0] != '.' && $b[0] != '/') ? '../'.$b : $b);
			if($b[0] == '/' && isset($h[0])) return $h[0].$b;
		}else if($b[0] == '/'){
			return $b;
		}
		$p = array(array('://','/./','//'),array('#R#','/','/'),array("/^\/(.+)$/","/^(\w):\/(.+)$/"),array("#T#\\1","\\1#W#\\2",''),array('#R#','#T#','#W#'),array('://','/',':/'));
		$a = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$a));
		$b = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$b));
		$d = $t = $r = '';
		if(strpos($a,'#R#')){
			list($r) = explode('/',$a,2);
			$a = substr($a,strlen($r));
			$b = str_replace('#T#','',$b);
		}
		$al = preg_split("/\//",$a,-1,PREG_SPLIT_NO_EMPTY);
		$bl = preg_split("/\//",$b,-1,PREG_SPLIT_NO_EMPTY);
	
		for($i=0;$i<sizeof($al)-substr_count($b,'../');$i++){
			if($al[$i] != '.' && $al[$i] != '..') $d .= $al[$i].'/';
		}
		for($i=0;$i<sizeof($bl);$i++){
			if($bl[$i] != '.' && $bl[$i] != '..') $t .= '/'.$bl[$i];
		}
		$t = (!empty($d)) ? substr($t,1) : $t;
		$d = (!empty($d) && $d[0] != '/' && substr($d,0,3) != '#T#' && !strpos($d,'#W#')) ? '/'.$d : $d;
		return str_replace($p[4],$p[5],$r.$d.$t);
	}
	/**
	 * 相対パスを取得
	 * @param string $baseUrl ベースのファイルパス
	 * @param string $targetUrl ファイルパス
	 * @return string
	 */
	static public function relative($baseUrl,$targetUrl){
		$rlist = array(array('://','/./','//'),array('#REMOTEPATH#','/','/')
					,array("/^\/(.+)$/","/^(\w):\/(.+)$/"),array("#ROOT#\\1","\\1#WINPATH#\\2",'')
					,array('#REMOTEPATH#','#ROOT#','#WINPATH#'),array('://','/',':/'));
		$baseUrl = preg_replace($rlist[2],$rlist[3],str_replace($rlist[0],$rlist[1],str_replace("\\",'/',$baseUrl)));
		$targetUrl = preg_replace($rlist[2],$rlist[3],str_replace($rlist[0],$rlist[1],str_replace("\\",'/',$targetUrl)));
		$filename = $url = '';
		$counter = 0;

		if(preg_match("/^(.+\/)[^\/]+\.[^\/]+$/",$baseUrl,$null)) $baseUrl = $null[1];
		if(preg_match("/^(.+\/)([^\/]+\.[^\/]+)$/",$targetUrl,$null)) list($tmp,$targetUrl,$filename) = $null;
		if(substr($baseUrl,-1) == '/') $baseUrl = substr($baseUrl,0,-1);
		if(substr($targetUrl,-1) == '/') $targetUrl = substr($targetUrl,0,-1);
		$baseList = explode('/',$baseUrl);
		$targetList = explode('/',$targetUrl);
		$baseSize = sizeof($baseList);

		if($baseList[0] != $targetList[0]) return str_replace($rlist[4],$rlist[5],$targetUrl);
		foreach($baseList as $key => $value){
			if(!isset($targetList[$key]) || $targetList[$key] != $value) break;
			$counter++;
		}
		for($i=sizeof($targetList)-1;$i>=$counter;$i--) $filename = $targetList[$i].'/'.$filename;
		if($counter == $baseSize) return sprintf('./%s',$filename);
		return sprintf('%s%s',str_repeat('../',$baseSize - $counter),$filename);
	}
	/**
	 * パスの前後にスラッシュを追加／削除を行う
	 * @param string $path ファイルパス
	 * @param boolean $prefix 先頭にスラッシュを存在させるか
	 * @param boolean $postfix 末尾にスラッシュを存在させるか
	 * @return string
	 */	
	static public function slash($path,$prefix,$postfix){
		if($path == '/') return ($postfix === true) ? '/' : '';
		if(!empty($path)){
			if($prefix === true){
				if($path[0] != '/') $path = '/'.$path;
			}else if($prefix === false){
				if($path[0] == '/') $path = substr($path,1);
			}
			if($postfix === true){
				if(substr($path,-1) != '/') $path = $path.'/';
			}else if($postfix === false){
				if(substr($path,-1) == '/') $path = substr($path,0,-1);
			}
		}
		return $path;
	}
	/**
	 * ファイルパスからディレクトリ名部分を取得
	 * @param string $path ファイルパス
	 * @return string
	 */
	static public function dirname($path){
		$dir_name = dirname(str_replace("\\",'/',$path));
		$len = strlen($dir_name);
		return ($len === 1 || ($len === 2 && $dir_name[1] === ':')) ? null : $dir_name;
	}
	/**
	 * フルパスからファイル名部分を取得
	 * @param string $path ファイルパス
	 * @return string
	 */
	static public function basename($path){
		$basename = basename($path);
		$len = strlen($basename);
		return ($len === 1 || ($len === 2 && $basename[1] === ':')) ? null : $basename;
	}
}