<?php
namespace org\rhaco\io;
/**
 * アーカイブの作成、解凍を行う
 * @author tokushima
 */
class Archive{
	private $base_dir;
	private $tree = array(5=>array(),0=>array());

	public function __construct($dir=null){
		if(isset($dir) &&  is_dir($dir)){
			$this->base_dir = $dir;
			$this->add($dir);
		}
	}	
	/**
	 * エントリ名から取り除くパスを設定する
	 * @param string $base_dir アーカイブ内部での名前から取り除く文字
	 * @return $this
	 */
	public function base_dir($base_dir){
		$this->base_dir = $base_dir;
		return $this;
	}
	/**
	 * 指定したパスからアーカイブに追加する
	 * @param string $path 追加するファイルへのパス
	 * @param string $base_dir アーカイブ内部での名前から取り除く文字
	 * @return $this
	 */	
	public function add($path,$base_dir=null){
		if(!isset($base_dir)) $base_dir = $this->base_dir;
		if(is_dir($path)){
			if($base_dir != $path) $this->tree[5][$this->source($path,$base_dir)] = $path;
			$l = $this->dirs($path);
			foreach($l[0] as $p) $this->tree[0][$this->source($p,$base_dir)] = $p;
			foreach($l[5] as $p) $this->tree[5][$this->source($p,$base_dir)] = $p;
		}else if(is_file($path)){
			$this->tree[0][$this->source($path,$base_dir)] = $path;
		}
		return $this;
	}
	/**
	 * tarを出力する
	 * @param string $filename 出力するファイルパス
	 * @return $this
	 */
	public function write($filename){
		$fp = fopen($filename,'wb');
		foreach(array(5,0) as $t){
			if(!empty($this->tree[$t])) ksort($this->tree[$t]);
			foreach($this->tree[$t] as $a => $n){
				if(strpos($n,'/.') === false){
					if($t == 0){
						$i = stat($n);
						$rp = fopen($n,'rb');
							fwrite($fp,$this->tar_head($t,$a,filesize($n),fileperms($n),$i[4],$i[5],filemtime($n)));
							while(!feof($rp)){
								$buf = fread($rp,512);
								if($buf !== '') fwrite($fp,pack('a512',$buf));
							}
						fclose($rp);
					}else{
						fwrite($fp,$this->tar_head($t,$a,0,0777));
					}
				}
			}
		}
		fwrite($fp,pack('a1024',null));
		fclose($fp);
		return $this;		
	}
	private function tar_head($type,$filename,$filesize=0,$fileperms=0777,$uid=0,$gid=0,$update_date=null){
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
	/**
	 * tgzを出力する
	 * @param string $filename 出力するファイルパス
	 * @return $this
	 */
	public function gzwrite($filename){
		$fp = gzopen($filename,'wb9');
			$this->write($filename.'.tar');
			$fr = fopen($filename.'.tar','rb');
				while(!feof($fr)){
					gzwrite($fp,fread($fr,4096));
				}
			fclose($fr);
		gzclose($fp);
		unlink($filename.'.tar');
		chmod($filename,0777);
		return $this;
	}
	/**
	 * zipを出力する
	 * @param string $filename 出力するファイルパス
	 * @return $this
	 */
	public function zipwrite($filename){
		$zip = new \ZipArchive();
		if($zip->open($filename,\ZipArchive::CREATE) === true){
			foreach(array(5,0) as $t){
				ksort($this->tree[$t]);
				foreach($this->tree[$t] as $a => $n){
					if(strpos($n,'/.') === false){
						if($t == 0){
							$zip->addFile($n,$a);
						}else{
							$zip->addEmptyDir($a);
						}
					}
				}
			}
			$zip->close();
			chmod($filename,0777);
		}
		return $this;
	}
	private function source($path,$base_dir){
		$source = (strpos($path,$base_dir) !== false) ? str_replace($base_dir,'',$path) : $path;
		if(strpos($source,'://') !== false) $source = preg_replace('/^.*:\/\/(.+)$/','\\1',$source);
		if($source[0] == '/') $source = substr($source,1);
		return $source;		
	}
	private function dirs($dir){
		$list = array(5=>array(),0=>array());
		if($h = opendir($dir)){
			while($p = readdir($h)){
				if($p != '.' && $p != '..'){
					$s = sprintf('%s/%s',$dir,$p);
					if(is_dir($s)){
						$list[5][$s] = $s;
						$r = $this->dirs($s);
						$list[5] = array_merge($list[5],$r[5]);
						$list[0] = array_merge($list[0],$r[0]);
					}else{
						$list[0][$s] = $s;
					}
				}
			}
			closedir($h);
		}
		return $list;
	}

	/**
	 * tarを解凍する
	 * @param string $inpath 解凍するファイルパス
	 * @param string $outpath 展開先のファイルパス
	 */
	static public function untar($inpath,$outpath){
		if(substr($outpath,-1) != '/') $outpath = $outpath.'/';
		if(!is_dir($outpath)) \org\rhaco\io\File::mkdir($outpath,0777);
		$fr = fopen($inpath,'rb');

		while(!feof($fr)){
			$buf = fread($fr,512);
			if(strlen($buf) < 512) break;
			$data = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/'
							.'a8chksum/'
							.'a1typeflg/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix',
							$buf);
			if(!empty($data['name'])){
				if($data['name'][0] == '/') $data['name'] = substr($data['name'],1);
				$f = $outpath.$data['name'];
				switch((int)$data['typeflg']){
					case 0:	
						$size = base_convert($data['size'],8,10);
						$cur = ftell($fr);
						if(!is_dir(dirname($f))) \org\rhaco\io\File::mkdir(dirname($f),0777);
						$fw = fopen($f,'wb');
							for($i=0;$i<=$size;$i+=512){
								fwrite($fw,fread($fr,512));
							}
						fclose($fw);
						$skip = $cur + (ceil($size / 512) * 512);
						fseek($fr,$skip,SEEK_SET);
						break;
					case 5:
						if(!is_dir($f)) \org\rhaco\io\File::mkdir($f,0777);
						break;
				}
			}
		}
		fclose($fr);
	}
	/**
	 * tar.gz(tgz)を解凍してファイル書き出しを行う
	 * @param string $inpath 解凍するファイルパス
	 * @param string $outpath 解凍先のファイルパス
	 */
	static public function untgz($inpath,$outpath){
		$fr = gzopen($inpath,'rb');
		$ft = fopen($outpath.'.tar','wb');
			while(!gzeof($fr)) fwrite($ft,gzread($fr,4096));
		fclose($ft);
		gzclose($fr);
		self::untar($outpath.'.tar',$outpath);
		unlink($outpath.'.tar');
		return true;
	}
	static public function unzip($inpath,$outpath){
		if(substr($outpath,-1) != '/') $outpath = $outpath.'/';
		if(!is_dir($outpath)) \org\rhaco\io\File::mkdir($outpath,0777);
		$zip = new \ZipArchive();
		if($zip->open($inpath) !== true) throw new \ErrorException('failed to open stream');
		$zip->extractTo($outpath);
		$zip->close();
	}
}