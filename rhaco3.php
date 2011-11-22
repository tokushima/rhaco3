<?php
if(!class_exists('Rhaco3')){
/**
 * rhaco3の環境定義クラス
 * @author tokushima
 */
class Rhaco3{
	static private $mode;
	static private $common_dir;
	static private $libs;
	static private $rep = array('http://rhaco.org/repository/3/lib/');
	/**
	 * ライブラリのパスを設定する
	 * @param string $libs_dir ライブラリのディレクトリパス
	 * @param string $mode 実行モード
	 * @param string $common_dir 設定ファイルのディレクトリ 
	 */
	static public function config_path($libs_dir=null,$mode=null,$common_dir=null){
		self::libs($libs_dir);
		self::mode($mode);
		self::common_dir($common_dir);
	}
	/**
	 * リポジトリの場所を指定する
	 * @param string $rep リポジトリのパス
	 */
	static public function repository($rep){
		array_unshift(self::$rep,$rep);
	}
	/**
	 * リポジトリパスの一覧を返す
	 * @return string[]
	 */
	static public function repositorys(){
		return self::$rep;
	}
	/**
	 * ライブラリのパスを設定/取得
	 * @param string $p ライブラリのパス
	 * @return string ライブラリのパス
	 */
	static public function libs($p=null){
		if(self::$libs === null){
			self::$libs = __DIR__.'/libs/';
			set_include_path(self::$libs.'_extlibs'.PATH_SEPARATOR.get_include_path());
			define('__PEAR_DATA_DIR__',self::$libs.'_extlibs/data');
		}
		return self::$libs.$p;
	}
	/**
	 * 実行モードを設定/取得
	 * @param string $mode モード
	 * @return string モード
	 */
	static public function mode($mode='noname'){
		if(self::$mode === null) self::$mode = $mode;
		return self::$mode;
	}
	/**
	 * 設定ファイルのディレクトリを設定/取得
	 * @param string $common_dir 設定ファイルのディレクトリ
	 * @return string モード
	 */
	static public function common_dir($dir=null){
		if(self::$common_dir === null){
			$dir = str_replace("\\",'/',(empty($dir)) ? __DIR__.'/commons/' : $dir);
			if(substr($dir,-1) != '/') $dir = $dir.'/';
			self::$common_dir = $dir;
		}
		return self::$common_dir;
	}
}
ini_set('display_errors','On');
ini_set('html_errors','Off');
set_error_handler(function($n,$s,$f,$l){throw new ErrorException($s,0,$n,$f,$l);});
if(ini_get('date.timezone') == '') date_default_timezone_set('Asia/Tokyo');
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}
spl_autoload_register(function($c){
	if($c[0] == '\\') $c = substr($c,1);
	$p = str_replace('\\','/',$c);
	if(ctype_upper($p[0]) || preg_match('/^(.+)\/([A-Z][\w_]*)$/',$p,$m)){
		foreach(array('','_vendors/') as $q){
			if(is_file($f=Rhaco3::libs($q.$p.'.php'))){require_once($f);break;
			}else if(isset($m[2]) && is_file($f=Rhaco3::libs($q.$p.'/'.$m[2].'.php'))){require_once($f);break;}
		}
	}
	if(!class_exists($c,false) && !interface_exists($c,false)){
		$e = Rhaco3::libs('_extlibs/');
		if(is_file($f=$e.$p.'.php')){require_once($f);
		}else if(is_file($f=$e.str_replace('_','/',$c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.php')){require_once($f);
		}else if(is_file($f=$e.strtolower($c).'.class.php')){require_once($f);
		}else{$f=$c;}
	}
	if(class_exists($c,false) || interface_exists($c,false)){
		if(method_exists($c,'__import__') && ($i = new ReflectionMethod($c,'__import__')) && $i->isStatic()) $c::__import__();
		if(method_exists($c,'__shutdown__') && ($i = new ReflectionMethod($c,'__shutdown__')) && $i->isStatic()) register_shutdown_function(array($c,'__shutdown__'));
		return true;
	}
	return false;
},true,false);
if(sizeof(debug_backtrace(false))>0){
	if(is_file($f=(__DIR__.'/__settings__.php'))){
		require_once($f);
		if(Rhaco3::mode() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::mode().'.php'))) require_once($f);
	}
	return;
}
}
##
if(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])){header('HTTP/1.1 404 Not Found');exit;}
if(isset($_SERVER['argv'][1])){
	$println = function($value,$fmt=null){
		if(substr(PHP_OS,0,3) == 'WIN'){
			$value = mb_convert_encoding($value,'UTF-8','SJIS');
		}else if($fmt !== null){
			$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
			$value = "\033[".$fmt.'m'.$value."\033[0m";
		}
		print($value.PHP_EOL);
	};
	$download = function($argv,$renew) use($println){
		$search = function($s){
			$z = $u = array();
			$s = preg_replace("/\/\*.+?\*\//s",'',$s);
			if(preg_match_all('/([\"\'])(\w+\.[\.\w:]+?)\\1/',$s,$m)){
				foreach($m[2] as $c){
					if(strpos($c,':') !== false) list($c) = explode(':',$c); 
					if(preg_match('/\.[A-Z]\w*$/',$c)) $z['\\'.str_replace('.','\\',$c)] = '\\'.str_replace('.','\\',$c);
				}
			}
			$s = preg_replace("/([\"\']).*?\\1/",'',$s);
			if(preg_match_all('/use\s+([\w\\\\]+)\s*;/',$s,$m)){foreach($m[1] as $c) $z[$c] = $u[preg_replace('/^.+\\\\([^\\\\]+)$/','\\1',$c)] = $c;}
			if(preg_match_all('/use\s+([\w\\\\]+)\s+as\s+(\w+)\s*;/',$s,$m)){foreach($m[1] as $k => $c) $z[$c] = $u[$m[2][$k]] = $c;}
			if(preg_match_all('/\s+instanceof\s+([\\\\\w]+)/',$s,$m)){foreach($m[1] as $k => $c) $z[$c] = $c;}
			if(preg_match_all('/\s+class_exists\(([\"\'])([\\\\\w]+)\\1/',$s,$m)){foreach($m[2] as $k => $c) $z[$c] = $c;}
			if(preg_match('/\s+extends\s+([\\\\\w]+)/',$s,$m)) $z[$m[1]] = $m[1];
			if(preg_match_all('/new ([\w\\\\]+)|([\w\\\\]+)::/',$s,$m)){for($i=1;$i<=2;$i++){foreach($m[$i] as $c){$z[$c] = $c;}}}
			if(preg_match('/implements\s+([\\\\\w,\s]+)/ms',$s,$m)){foreach(explode(',',$m[1]) as $r) $z[trim($r)] = trim($r);}
			if(!empty($z)){
				$n = ((preg_match("/namespace ([\w\\\\]+)/",$s,$m)) ? $m[1] : null);
				if(!empty($n)) $n = '\\'.$n;
				foreach($z as $k => $v){
					$r = $k;
					if(!empty($k)){
						if(isset($u[$k])){$r = $u[$k];}else if(!empty($n) && strpos($v,'\\') !== 0){$r = $n.'\\'.$k;}
						if($r != $k) unset($z[$k]);
						$z[$r] = str_replace('\\','.',preg_replace('/^\\\\(.+)$/','\\1',$r));
					}
					if(empty($k) || !preg_match('/[A-Z]/',$r) || preg_match('/^(.*[A-Z].*)\\\\\w+$/',$r) || strpos(substr($r,1),'\\') === false) unset($z[$r]);
				}
			}
			return $z;
		};
		$rm = function($d,$t){if(is_dir($d)){
			$f = array();
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS),RecursiveIteratorIterator::SELF_FIRST) as $e) $f[] = $e->getPathname();
			rsort($f);
			foreach($f as $p) ((is_file($p)) ? unlink($p) : rmdir($p));
			if($t && is_dir($d)) rmdir($d);
		}};
		$pkg = function(&$imported,$package) use(&$pkg,$rm,$search,$println){
			if(isset($imported[$package])) return true;
			$imported[$package] = true;
			$dl = str_replace('/','_',$package);
			$dp = Rhaco3::libs('_download/');
			$ep = Rhaco3::libs('_download/extract/'.$package.'/');
			$vp = Rhaco3::libs('_vendors/');

			foreach(Rhaco3::repositorys() as $rp){
				try{
					$rp = str_replace('\\','/',$rp);					
					if(substr($rp,-1) != '/') $rp = $rp.'/';
					if(strpos($rp,'://') !== false){
						if(!is_dir($dp)) mkdir($dp,0777,true);
						if(is_file($dp.$dl.'.tgz')) unlink($dp.$dl.'.tgz');
						$fr = fopen($rp.$dl.'.tgz','rb');
						$fw = fopen($dp.$dl.'.tgz','wb');
						while(!feof($fr)) fwrite($fw,fread($fr,4096));
						fclose($fr);
						fclose($fw);
					}else{
						$dp = $rp;
					}
					if(is_file($dp.$dl.'.tgz')){
						$p = str_replace('.','/',$package);
						if(is_file($vp.$p.'.php')) unlink($vp.$p.'.php');
						if(is_file($vp.$p.'/'.basename($p).'.php')) $rm($vp.$p,true);
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
								$path = $ep.$d['name'];
								switch((int)$d['typeflg']){
									case 0:
										$size = base_convert($d['size'],8,10);
										if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
										for($i=0;$i<=$size;$i+=512){
											$s = (($i+512>$size)?$size-$i:512);
											file_put_contents($path,gzread($fp,$s),FILE_APPEND);
											if($s < 512) gzread($fp,512-$s);
										}
										touch($path,base_convert($d['mtime'],8,10));
										break;
									case 5:
										if(!is_dir($path)) mkdir($path,0777,true);
										break;
								}
							}
						}
						gzclose($fp);
						$required = array();
						foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ep,FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
							$so = str_replace($ep,$vp,$f->getPathname());
							if(!is_dir(dirname($f->getPathname()))) mkdir(dirname($f->getPathname()),0777,true);
							if(!is_dir(dirname($so))) mkdir(dirname($so),0777,true);
							copy($f->getPathname(),$so);
							if(substr($f->getPathname(),-4) == '.php'){$required = array_merge($required,$search(file_get_contents($f->getPathname())));}
						}
						$rm($ep,true);
						foreach($required as $r){if(!$pkg($imported,$r)){$println('not found: '.$package.' > '.$package.' in '.$r,false);}}
						$println('installed: '.$package.' ('.$rp.')');
						return true;
					}
				}catch(Exception $e){}
			}
			return false;
		};
		$error = $imported = array();
		$argv = array_flip($argv);
		if(empty($argv)){
			foreach(new DirectoryIterator(__DIR__) as $f){
				if($f->isFile() && strpos($f->getPathname(),'/_') === false && substr($f->getFilename(),-4) == '.php' && strpos($f->getPathname(),Rhaco3::libs()) === false){
					foreach($search(file_get_contents($f->getPathname())) as $k => $v) $argv[$v] = $f->getPathname();
				}
			}
			if(is_dir(Rhaco3::libs())){
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::libs(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					if($f->isFile() && strpos($f->getPathname(),'/_') === false && substr($f->getPathname(),-4) == '.php'){ foreach($search(file_get_contents($f->getPathname())) as $k => $v){ $argv[$v] = $f->getPathname(); } }
				}
			}
			if(is_file(__DIR__.'/__settings__.php')){
				foreach($search(file_get_contents(__DIR__.'/__settings__.php')) as $k => $v){ $argv[$v] = $f->getPathname(); }
			} 
		}
		foreach($argv as $arg => $f){
			if(($b = class_exists($p = '\\'.str_replace('.','\\',$arg))) || ($b = interface_exists($p = '\\'.str_replace('.','\\',$arg)))){
				if($renew){
					$r = new ReflectionClass($p);
					$b = (strpos($r->getFilename(),Rhaco3::libs('_vendors')) === false);
				}
			}
			if(!$b && !$pkg($imported,$arg)) $error[$arg] = $f;
		}
		$rm(Rhaco3::libs('_download/'),true);
		if(!empty($error)){
			foreach($error as $p => $f) print('not found: '.(is_int($f) ? '' : $f.(' in ')).$p.PHP_EOL);
		}
	};
	try{
		$help = (substr($_SERVER['argv'][1],-1) == '?');
		$_SERVER['argv'][1] = ($help) ? substr($_SERVER['argv'][1],0,-1) : $_SERVER['argv'][1];
		switch($_SERVER['argv'][1]){
			case '-import':
				if(is_file($f=__DIR__.'/__settings__.php') && preg_match_all('/\n\s*[\\\\]{0,1}Rhaco3::.+?\);/ms',file_get_contents($f),$m)){foreach($m[0] as $e){eval($e);}}
				if(is_file(Rhaco3::libs()) || strpos(Rhaco3::libs(),'://') !== false) throw new RuntimeException(Rhaco3::libs().' is not a directory');
				$argv = array_slice($_SERVER['argv'],2);
				$download($argv,true);
				exit;
			case '-phar':
				if(!Phar::canWrite()) die('write operations disabled by the php.ini setting phar.readonly'.PHP_EOL.' > php -d phar.readonly=0 '.basename(__FILE__).' '.$_SERVER['argv'][1].PHP_EOL);
				$path = __DIR__.'/libs_'.date('Ymd_Hi').'.phar';
				$phar = new Phar($path,0,'libs.phar');
				print('Writen: '.$path.PHP_EOL);
				$phar->setDefaultStub((isset($phar['cli.php']) ? 'cli.php' : '<?php return; __HALT_COMPILER();'),(isset($phar['web.php']) ? 'web.php' : '<?php return; __HALT_COMPILER();'));
				foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::libs(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
					$phar->addFile($f->getPathname(),str_replace(Rhaco3::libs(),'',$f->getPathname()));
					print(' Add: '.$f->getPathname().PHP_EOL);
				}
				$phar->compress(Phar::GZ,'phar.gz');
				print(PHP_EOL.'Created: '.$path.'.gz'.PHP_EOL);
				if(is_file($path)) unlink($path);
				exit;
			case '-search':
				if(is_file($f=__DIR__.'/__settings__.php') && preg_match_all('/\n\s*[\\\\]{0,1}Rhaco3::.+?\);/ms',file_get_contents($f),$m)){foreach($m[0] as $e){eval($e);}}
				$argv = array_slice($_SERVER['argv'],2);
				$q = (!empty($argv)) ? strtolower($argv[0]) : '';
				$list = array();
				$len = 0;
				foreach(Rhaco3::repositorys() as $rp){
					$rp = str_replace('\\','/',$rp);		
					if(substr($rp,-1) != '/') $rp = $rp.'/';
					print('Read repository: '.$rp.PHP_EOL);
					$csv = file_get_contents($rp.'packages.csv');
					foreach(explode("\n",$csv) as $line){
						if(empty($q) || strpos(strtolower($line),$q) !== false){
							list($pkg,$dec) = explode(',',$line);
							$list[trim($pkg)] = trim($dec);
							if(strlen(trim($pkg)) > $len) $len = strlen(trim($pkg));
						}
					}
				}
				ksort($list);
				print(PHP_EOL.'Search result: '.((empty($q) ? '' : '['.$q.']')).PHP_EOL);
				foreach($list as $pkg => $dec){
					print(' '.str_pad($pkg,$len).' '.$dec.PHP_EOL);
				}
				exit;
			case '-htaccess':
				$path = isset($argv[2]) ? $argv[2] : '/'.basename(getcwd());
				if(substr($path,0,1) !== '/') $path = '/'.$path;
				$rules = "RewriteEngine On\nRewriteBase ".$path."\n\n";
				foreach(new DirectoryIterator(__DIR__) as $f){
					if($f->isFile() && substr($f->getPathname(),-4) == '.php' && substr($f->getFilename(),0,1) != '_' && $f->getPathname() != __FILE__ && $f->getFilename() != 'index.php'){
						$src = file_get_contents($f->getPathname());
						if(strpos($src,'Flow') !== false && strpos($src,'patterns') !== false && strpos($src,'output') !== false){
							$app = substr($f->getFilename(),0,-4);
							$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^".$app."[/]{0,1}(.*)\$ ".$app.".php/\$1?%{QUERY_STRING} [L]\n\n";
						}
					}
				}
				if(is_file(getcwd().'/index.php')) $rules .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^(.*)\$ index.php/\$1?%{QUERY_STRING} [L]\n\n";
				file_put_contents('.htaccess',$rules);
				print('Writen: '.realpath('.htaccess').PHP_EOL.str_repeat('-',60).PHP_EOL.trim($rules).PHP_EOL.str_repeat('-',60).PHP_EOL);
				exit;
			case '-settings':
				$path = getcwd().'/__settings__.php';
				$header = 'HTTP/1.1 404 Not Found';
				if(isset($argv[2])){
					$header = (strtolower($argv[2]) == 'fastcgi') ? 'Status: 404 Not Found' : 'Location: '.$argv[2];
				}
				$rep = sprintf("if(\$_SERVER['SCRIPT_FILENAME']==__FILE__){header('%s');exit;}",$header);
				if(is_file($path)){
					$str = file_get_contents($path);
					if(preg_match("/if\(\\\$_SERVER\['SCRIPT_FILENAME'\]==__FILE__.+?;exit;\}/",$str,$m)) $str = str_replace($m[0].PHP_EOL,'',$str);
					$str = str_replace('<?php','<?php'.PHP_EOL.$rep,$str);
				}else{
					$str = '<?php'.PHP_EOL.$rep;					
				}
				file_put_contents($path,$str);
				print('Writen: __settings__.php'.PHP_EOL);
				exit;
			default:
				if($_SERVER['argv'][1][0] == '-'){
					if(is_file($f=(__DIR__.'/__settings__.php'))){
						require_once($f);
						if(Rhaco3::mode() !== null && is_file($f=(Rhaco3::common_dir().Rhaco3::mode().'.php'))) require_once($f);
					}
					$package = substr($_SERVER['argv'][1],1);
					$download(array($package),false);
					if(is_file($f=Rhaco3::libs(str_replace('.','/',$package).'/setup.php')) || is_file($f=Rhaco3::libs('_vendors/'.str_replace('.','/',$package).'/setup.php'))){
						if($help){
							$params = array();
							$pad = 4;
							$pvalue = '';
							$doc = (preg_match('/\/\*\*.+?\*\//s',file_get_contents($f),$m)) ? trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[0]))) : '';
							if(preg_match_all('/@.+/',$doc,$as)){
								foreach($as[0] as $m){
									if(preg_match("/@(\w+)\s+([^\s]+)\s+\\$(\w+)(.*)/",$m,$p)){
										if($p[2] == '$this' || $p[2] == 'self') $p[2] = $package;
										if($p[1] == 'param' && $p[3] == 'value'){ $pvalue = sprintf('[(%s) %s]',$p[2],trim($p[4]));
										}else if($p[1] == 'param'){ $params[$p[3]] = array($p[2],trim($p[4])); }
									}else if(preg_match("/@(\w+)\s+\\$(\w+)(.*)/",$m,$p)){
										$params[$p[2]] = array(null,trim($p[3]));
									}
								}
								foreach(array_keys($params) as $k){if($pad < strlen($k)){ $pad = strlen($k); }}
							}
							print("\nUsage:\n");
							print("  -".$package." ".$pvalue."\n");
							if(!empty($params)){
								print("\n  Options:\n");
								foreach($params as $k => $v){
									print('    '.sprintf('-%s%s %s',str_pad($k,$pad),(empty($v[0]) ? '' : ' ('.$v[0].')'),trim($v[1]))."\n");
								}
							}
							$doc = trim(preg_replace('/@.+/','',$doc));
							print("\n\n  description:\n");
							print('    '.str_replace("\n","\n    ",$doc)."\n\n");
						}else{
							$_SERVER['argv'] = array_slice($_SERVER['argv'],2);
							$_ENV['PATH_LIBS'] = Rhaco3::libs();
							$_ENV['PATH_EXTLIBS'] = Rhaco3::libs('_extlibs');
							$_ENV['params'] = array('value'=>(isset($_SERVER['argv'][0]) && substr($_SERVER['argv'][0],0,1) == '-') ? null : array_shift($_SERVER['argv']));
							for($i=0,$argv=$_SERVER['argv'];$i<sizeof($_SERVER['argv']);$i++){
								if($argv[$i][0] == '-') $_ENV['params'][substr($argv[$i],1)] = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
							}
							include_once(dirname($f).'/'.basename(dirname($f)).'.php');
							include_once($f);
						}
					}else{
						throw new RuntimeException('Setup not found `'.$package.'`');
					}
				}
		}
	}catch(Exception $e){
		print($e->getMessage().PHP_EOL);
	}
	exit;
}
$list = array('import'=>'Download package','phar'=>'Create a phar libs','search'=>'Search package','htaccess'=>'Create .htaccess','settings'=>'Create __settings__.php');
$len = 8;
if(is_dir(Rhaco3::libs())){
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Rhaco3::libs(),FilesystemIterator::CURRENT_AS_FILEINFO|FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS)) as $f){
		if($f->isFile() && substr($f->getPathname(),-4) == '.php' 
			&& basename(dirname($f->getPathname())) === $f->getBasename('.php') && preg_match('/^[A-Z].*/',$f->getBasename('.php'))
			&& is_file(dirname($f->getPathname()).'/setup.php')
			&& (strpos($f->getPathname(),'/_') === false || strpos($f->getPathname(),'/_vendors/') !== false)
		){
			$package = str_replace(array(Rhaco3::libs(),'/'),array('','.'),dirname($f->getPathname()));
			if(strpos($package,'_vendors.') === 0) $package = substr($package,9);
			if($len < strlen($package)) $len = strlen($package);
			list($summary) = (preg_match('/\/\*\*.+?\*\//s',file_get_contents(dirname($f->getPathname()).'/setup.php'),$m)) ? explode("\n",trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$m[0])))) : '';
			$list[$package] = $summary;
		}
	}
}
print(PHP_EOL.'Commands: '.PHP_EOL);
foreach($list as $p => $s) print('  '.str_pad($p,$len).' : '.$s.PHP_EOL);
exit;
