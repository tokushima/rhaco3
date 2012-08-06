<?php
namespace org\rhaco\io;
/**
 * xhprofでプロファイラ情報を記録する
 * 
 * @see http://pecl.php.net/package/xhprof
 * @see http://www.graphviz.org/
 * @author tokushima
 * @conf string $output_dir 出力ディレクトリ
 * @conf string $type 出力ファイルの種類 ( ******.xhprof )
 * @conf string $preg_match 対象のURLパターン
 * @conf string $view_url 結果表示のURL(xhprof_html)
 */
class Xhprof{
	static public function __shutdown__(){
		if(extension_loaded('xhprof')){
			$preg_match = \org\rhaco\Conf::get('preg_match');
			if(empty($preg_match) || preg_match($preg_match,self::request())){
				$data = xhprof_disable();
				$dir = \org\rhaco\Conf::get('output_dir',ini_get('xhprof.output_dir'));
	
				if(!empty($dir)){
					$id = date('Ymd').'_'.uniqid();
					$type = \org\rhaco\Conf::get('type','xhprof');
					if(substr($dir,-1) != '/') $dir = $dir.'/';				
					if(is_dir($dir) && is_writable($dir)){
						$view_url = \org\rhaco\Conf::get('view_url');
						if(isset($view_url)){
							\org\rhaco\Log::info(sprintf('view: %s, request: %s',\org\rhaco\lang\Text::fstring($view_url,$id,$type),self::request()));
						}else{
							\org\rhaco\Log::info(sprintf('run: %s ,source: %s, request: %s',$id,$type,self::request()));
						}
						file_put_contents($dir.$id.'.'.$type,serialize($data));
					}else{
						\org\rhaco\Log::warn($dir.' permission denied');
					}
				}
			}
		}
	}
	static public function run(){
		if(extension_loaded('xhprof')){
			$preg_match = \org\rhaco\Conf::get('preg_match');
			if(empty($preg_match) || preg_match($preg_match,self::request())) xhprof_enable();
		}
	}
	static private function request(){
		return isset($_SERVER['REQUEST_URI']) ? 
					preg_replace("/^(.+)\?.*$/","\\1",$_SERVER['REQUEST_URI']) : 
					(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'].(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '') : '');		
	}
}

/*
# cp /etc/php.ini.default /etc/php.ini

cd xhprof-0.9.2/extension
phpize
./configure --with-php-config=/usr/bin/php-config
make 
make test
sudo make install

/etc/php.ini
----------
[xhprof]
extension=xhprof.so
;
; directory used by default implementation of the iXHProfRuns
; interface (namely, the XHProfRuns_Default class) for storing
; XHProf runs.
;
xhprof.output_dir=<directory_for_storing_xhprof_runs>
*/
