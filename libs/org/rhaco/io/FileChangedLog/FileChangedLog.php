<?php
namespace org\rhaco\io;
/**
 * ファイルが更新されたら通知する
 * @author tokushima
 */
class FileChangedLog extends \org\rhaco\Object{
	private $path = null;
	private $pre = null;

	/**
	 * 監視を開始する
	 * @param string $path
	 */
	protected function __new__($path){
		$this->path = $path;
		print('File change monitor '.$path.PHP_EOL);
		print('Quit the monitor with CONTROL-C.'.PHP_EOL.PHP_EOL);

		while(true){
			clearstatcache();
			$f = array();
			$pre = $this->pre;

			if(is_file($path)){
				$i = $this->info(dirname($path),$path);
				$this->diff($i,$path);
				$f[$path] = $i;
			}else if(is_dir($path)){
				foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS),\RecursiveIteratorIterator::SELF_FIRST) as $e){
					if(is_file($e->getPathname())){
						$i = $this->info($path,$e->getPathname());
						$this->diff($i,$e->getPathname());
						$f[$e->getPathname()] = $i;
					}
				}
			}
			if(sizeof($f) < sizeof($this->pre)){
				foreach($this->pre as $p => $i){
					if(!isset($f[$p])) $this->msg($i,0,'REMOVE');
				}
			}
			$this->pre = $f;
			sleep(1);
		}
	}
	private function diff($i,$p){
		if(isset($this->pre)){
			if(isset($this->pre[$p])){
				if($i[1] > $this->pre[$p][1]){
					$this->msg($i,$this->pre[$p][2],null);
				}
			}else{
				$this->msg($i,0,'NEW');
			}
		}
	}
	private function info($base,$path){
		return array(str_replace($base,'',$path),filemtime($path),filesize($path));
	}
	private function msg($info,$pre_size,$text){
		$text = (string)(isset($text) ? $text : 'UPDATE '.($info[2]-$pre_size)).' byte';
		/**
		 * infoログの場合の処理
		 * @param org.rhaco.Log $log
		 * @param string $id
		 */
		self::module('info',new \org\rhaco\Log('info',$text,$info[0],0,$info[1]),$this->path);
		print(date('Y/m/d H:i:s',$info[1]).' '.$info[0].'('.$text.')'.PHP_EOL);
	}
}