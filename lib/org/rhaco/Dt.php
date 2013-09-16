<?php
namespace org\rhaco;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Q;
/**
 * マップ情報、モデル情報、パッケージ情報を表示
 * @author tokushima
 * @conf string $document_path ドキュメントの配置されたフォルダ
 */
class Dt extends \org\rhaco\flow\parts\RequestFlow{
	private $smtp_blackhole_dao;
	private $dao;
	private $flow_output_maps = array();

	protected function __init__(){
		$name = $summary = $description = null;
		$d = debug_backtrace(false);
		$d = array_pop($d);
		$this->smtp_blackhole_dao = '\\'.implode('\\',array('org','rhaco','net','mail','module','SmtpBlackholeDao'));
		$this->dao = '\\'.implode('\\',array('org','rhaco','store','db','Dao'));
		
		$this->vars('app_mode',\org\rhaco\Conf::appmode());
		$this->vars('f',new Dt\Helper());
		$this->vars('has_smtp_blackhole_dao',class_exists($this->smtp_blackhole_dao));
		$this->vars('has_dao',class_exists($this->dao));
		$this->vars('has_document',is_dir(\org\rhaco\Conf::get('document_path',\org\rhaco\io\File::resource_path('document'))));
	}
	public function get_template_modules(){
		return array(
					new \org\rhaco\flow\module\TwitterBootstrapPagination()
					,new \org\rhaco\flow\module\TwitterBootstrapExtHtml()
					,new \org\rhaco\flow\module\Exceptions()
					,new \org\rhaco\flow\module\Dao()
				);
	}
	/**
	 * アプリケーションのマップ一覧
	 */
	private function get_flow_output_maps(){
		if(empty($this->flow_output_maps)){
			$trace = debug_backtrace(false);
			$entry = array_pop($trace);
		
			foreach(new \RecursiveDirectoryIterator(
					dirname($entry['file']),
					\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
			) as $e){
				if(substr($e->getFilename(),-4) == '.php' &&
				strpos($e->getPathname(),'/.') === false &&
				strpos($e->getPathname(),'/_') === false
				){
					$src = file_get_contents($e->getFilename());
		
					if(strpos($src,'Flow') !== false){
						$entry_name = substr($e->getFilename(),0,-4);
						foreach(\org\rhaco\Flow::get_maps($e->getPathname()) as $k => $m){
							if(!isset($m['deprecated'])) $m['deprecated'] = false;
							if(!isset($m['mode'])) $m['mode'] = null;
							if(!isset($m['summary'])) $m['summary'] = null;
														
							if(!isset($m['class']) || $m['class'] != str_replace('\\','.',__CLASS__)){
								$m['error'] = '';
								$m['url'] = $k;

								try{
									if(isset($m['method'])){
										$info = \org\rhaco\Dt\Man::method_info($m['class'],$m['method']);
										if(!isset($m['summary'])){
											list($m['summary']) = explode(PHP_EOL,$info['description']);
										}
										if(!$m['deprecated']) $m['deprecated'] = $info['deprecated'];
									}
									$m['entry'] = $entry_name;										
								}catch(\Exception $e){
									$m['error'] = $e->getMessage();
								}
								$this->flow_output_maps[$entry_name.'::'.$m['name']] = $m;
							}
						}
					}
				}
			}
		}
		
		return $this->flow_output_maps;
	}
	/**
	 * @automap
	 */
	public function index(){
		$this->vars('map_list',$this->get_flow_output_maps());
	}
	/**
	 * Daoモデルの一覧
	 * @automap
	 */
	public function model_list(){
		$list = $errors = $error_query = $model_list = $con = array();
		Dt\Man::classes();
		
		foreach(get_declared_classes() as $class){
			$r = new \ReflectionClass($class);
			if((!$r->isInterface() && !$r->isAbstract()) && is_subclass_of($class,$this->dao)){
				$class_doc = $r->getDocComment();
				$package = str_replace('\\','.',$class);
				$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
				list($summary) = explode("\n",$document);
				$errors[$package] = null;
				$con[$package] = true;
				$dao = $this->dao;
				try{
					$dao::start_record();
					$class::find_get();
					$dao::stop_record();
				}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
				}catch(\org\rhaco\store\db\exception\DaoConnectionException $e){
					$errors[$package] = $e->getMessage();
					$con[$package] = false;
				}catch(\Exception $e){
					$errors[$package] = $e->getMessage();
					$error_query[$package] = print_r($dao::recorded_query(),true);
				}
				if($this->search_str($package,$summary)) $model_list[$package] = $summary;				
			}
		}
		$this->vars('dao_models',$model_list);
		$this->vars('dao_model_errors',$errors);
		$this->vars('dao_model_error_query',$error_query);
		$this->vars('dao_model_con',$con);
		$this->vars('getcwd',getcwd());
	}

	private function search_str(){
		$query = str_replace('　',' ',trim($this->in_vars('q')));
		if(!empty($query)){
			$args = func_get_args();
			foreach(explode(' ',$query) as $q){
				if(stripos(str_replace('\\','.',implode(' ',$args)),$q) === false) return false;
			}
		}
		return true;
	}
	/**
	 * ライブラリの一覧
	 * @automap
	 */
	public function class_list(){
		$libs = array();
		foreach(Dt\Man::classes() as $package => $info){
			$r = new \ReflectionClass($info['class']);
			$src = file_get_contents($r->getFileName());
			$class_doc = $r->getDocComment();
			$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
			list($summary) = explode("\n",$document);			
			
			$bool = true;
			if($this->in_vars('q') != ''){
				$modules = null;
				$module = array();
				
				foreach($r->getMethods() as $method){
					if(substr($method->getName(),0,1) != '_' && ($method->isPublic() || $method->isProtected())){
						$method_document = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$method->getDocComment()));
						if(preg_match_all("/@module\s+([\w\.\\\\]+)/",$method_document,$match)){
							foreach($match[1] as $v) $module[trim($v)] = true;
						}						
					}
				}
				$modules = implode(':',array_keys($module));
				$bool = $this->search_str($info['class'],$document,$modules);
			}
			if($bool){
				$c = new \org\rhaco\Object();
				$c->summary = $summary;
				$c->usemail = (strpos($src,'\org'.'\rhaco'.'\net'.'\mail'.'\Mail') !== false);
				$libs[$package] = $c;
			}
		}
		ksort($libs);
		$this->vars('class_list',$libs);
	}
	/**
	 * クラスのソース表示
	 * @param string $class
	 * @automap
	 */
	public function class_src($class){
		$info = Dt\Man::class_info($class);
		foreach($info as $k => $v){
			$this->vars($k,$v);
		}
		$this->vars('class_src',file_get_contents($info['filename']));
	}
	/**
	 * クラスのドキュメント
	 * @param string $class
	 * @automap
	 */
	public function class_doc($class){
		$info = Dt\Man::class_info($class);
		foreach($info as $k => $v){
			$this->vars($k,$v);
		}
		$is_dao = false;
		$class_name = str_replace(array('.','/'),'\\',$class);
		if(substr($class_name,0,1) != '\\') $class_name = '\\'.$class_name;
		
		if(is_subclass_of($class_name,$this->dao)){
			try{
				$class_name::find_get();
				$is_dao = true;
			}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
				$is_dao = true;
			}catch(\Exception $e){}
		}
		$this->vars('is_dao',$is_dao);
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 * @automap
	 */
	public function method_doc($class,$method){
		foreach(Dt\Man::method_info($class,$method) as $k => $v){
			$this->vars($k,$v);
		}
	}
	/**
	 * モジュールのドキュメント
	 * @param string $class
	 * @param string $module_name
	 * @automap
	 */
	public function class_module_info($class,$module_name){
		$ref = Dt\Man::class_info($class);
		if(!isset($ref['modules'][$module_name])) throw new \LogicException($module_name.' not found');
		$this->vars('package',$class);
		$this->vars('module_name',$module_name);
		$this->vars('description',$ref['modules'][$module_name][0]);
		$this->vars('params',$ref['modules'][$module_name][1]);
		$this->vars('return',$ref['modules'][$module_name][2]);
	}
	private function get_model($name,$sync=true){
		$r = new \ReflectionClass('\\'.str_replace('.','\\',$name));
		$obj = $r->newInstance();
		$args = null;
		if(is_array($this->in_vars('primary'))){
			foreach($this->in_vars('primary') as $k => $v) $obj->{$k}($v);
		}
		return ($sync) ? $obj->sync() : $obj;
 	}
	/**
	 * 検索
	 * 
	 * @param string $name モデル名
	 * @automap
	 * 
	 * @request string $order ソート順
	 * @request int $page ページ番号
	 * @request string $query 検索文字列
	 * @request string $porder 直前のソート順
	 * 
	 * @context array $object_list 結果配列
	 * @context Paginator $paginator ページ情報
	 * @context string $porder 直前のソート順
	 * @context Dao $model 検索対象のモデルオブジェクト
	 * @context string $model_name 検索対象のモデルの名前
	 */
	public function do_find($package){
		$name = '\\'.str_replace('.','\\',$package);
		$order = \org\rhaco\lang\Sorter::order($this->in_vars('order'),$this->in_vars('porder'));
		
		if(empty($order)){
			$dao = new $name();
			foreach($dao->props() as $n => $v){
				if($dao->prop_anon($n,'primary')){
					$order = '-'.$n;
					break;
				}
			}
		}
		list($object_list,$paginator) = $this->filter_find($name, $order);
		$this->vars('object_list',$object_list);
		$this->vars('paginator',$paginator);
		$this->vars('model',new $name());
		$this->vars('package',$package);
	}
	/**
	 * 詳細
	 * @param string $package モデル名
	 * @automap
	 */
	public function do_detail($package){
		$obj = $this->get_model($package);
		$this->vars('object',$obj);
		$this->vars('model',$obj);
		$this->vars('package',$package);
	}
	/**
	 * 削除
	 * @param string $package モデル名
	 * @automap @['post_after'=>'']
	 */
	public function do_drop($package){
		if($this->is_post()){
			$this->get_model($package)->delete();
		}
	}
	/**
	 * 更新
	 * @param string $package モデル名
	 * @automap @['post_after'=>['save_and_add_another'=>['do_create','package'],'save'=>['do_find','package']]]
	 */
	public function do_update($package){
		if($this->is_post()){
			try{
				$obj = $this->get_model($package,false);
				$obj->set_props($this);
				$obj->save();
			}catch(\Exception $e){
				\org\rhaco\Log::error($e);
			}
		}else{
			$obj = $this->get_model($package);
		}
		foreach($obj->props() as $k => $v){
			$fm = 'fm_'.$k;
			$this->vars($k,$obj->$fm());
		}
		$this->vars('model',$obj);
		$this->vars('package',$package);
	}
	/**
	 * 作成
	 * @param string $package モデル名
	 * @automap @['post_after'=>['save_and_add_another'=>['do_create','package'],'save'=>['do_find','package']]]
	 */
	public function do_create($package){
		if($this->is_post()){
			try{
				$obj = $this->get_model($package,false);
				$obj->set_props($this);
				$obj->save();
			}catch(\Exception $e){
				\org\rhaco\Log::error($e);
			}
		}else{
			$obj = $this->get_model($package,false);
		}
		$this->vars('model',$obj);
		$this->vars('package',$package);
	}
	static public function get_dao_connection($package){
		if(!is_object($package)){
			$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
			$package = $r->newInstance();
		}		
		if(!is_subclass_of($package,'\org\rhaco\store\db\Dao')) throw new \RuntimeException('not Dao');
	
		$connections = \org\rhaco\store\db\Dao::connections();
		$conf = explode("\\",get_class($package));
		while(!isset($connections[implode('.',$conf)]) && !empty($conf)) array_pop($conf);
		if(empty($conf)){
			if(!isset($connections['*'])) throw new \RuntimeException(get_class($package).' connection not found');
			$conf = array('*');
		}
		$conf = implode('.',$conf);	
		foreach($connections as $k => $con){
			if($k == $conf) return $con;
		}		
	}
	/**
	 * SQLを実行する
	 * @param string $package
	 * @automap
	 */
	public function do_sql($package){
		$result_list = $keys = array();
		$sql = $this->in_vars('sql');
		$count = 0;
		
		try{
			$con = self::get_dao_connection($package);
			
			if($this->is_vars('create_sql')){
				$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
				$dao = $r->newInstance();
				$sql = $con->connection_module()->create_table_sql($dao);
				$this->rm_vars('create_sql');
				$this->vars('sql',$sql);
			}		
			if($this->is_post() && !empty($sql)){
				$excute_sql = array();
				$sql = str_replace(array('\\r\\n','\\r','\\n','\;'),array("\n","\n","\n",'{SEMICOLON}'),$sql);
				foreach(explode(';',$sql) as $q){
					$q = trim(str_replace('{SEMICOLON}',';',$q));
					$excute_sql[] = $q;
					if(!empty($q)) $con->query($q);
				}
				foreach($con as $k => $v){
					if(empty($keys)) $keys = array_keys($v);
					$result_list[] = $v;
					$count++;
					
					if($count >= 100) break;
				}
				$this->vars('excute_sql',implode(';'.PHP_EOL,$excute_sql));
			}
		}catch(\Exception $e){
			\org\rhaco\Exceptions::add($e);
		}
		$this->vars('result_keys',$keys);
		$this->vars('result_list',$result_list);
		$this->vars('package',$package);
		$this->vars('maximum',($count >= 100));
	}
	/**
	 * メールの一覧
	 * @automap
	 */
	public function mail_list(){
		$order = $this->in_vars('order','-id');
		$name = $this->smtp_blackhole_dao;
		list($object_list,$paginator) = $this->filter_find($name,$order);
		$this->vars('object_list',$object_list);
		$this->vars('paginator',$paginator);
		$this->vars('model',new $name());
	}
	private function filter_find($class,$order){
		$object_list = array();
		$paginator = new \org\rhaco\Paginator(20,$this->in_vars('page',1));
		$paginator->cp(array('order'=>$order));
		
		if($this->is_vars('search_clear')){
			$object_list = $class::find_all($paginator,Q::select_order($order,$this->in_vars('porder')));
			$this->rm_vars();
		}else if($this->is_vars('search')){
			$q = new \org\rhaco\store\db\Q();
			foreach($this->ar_vars() as $k => $v){
				if($v !== '' && strpos($k,'search_') === 0){
					list(,$type,$key) = explode('_',$k,3);
					switch($type){
						case 'timestamp':
						case 'date':
							list($fromto,$key) = explode('_',$key);
							$q->add(($fromto == 'to') ? Q::lte($key,$v) : Q::gte($key,$v));
							break;
						default:
							$q->add(Q::contains($key,$v));
					}
					$paginator->vars($k,$v);
				}
				$paginator->vars('search',true);
			}
			$object_list = $class::find_all($q,$paginator,Q::select_order($order,$this->in_vars('porder')));
			$this->rm_vars('q');	
		}else{
			$object_list = $class::find_all(Q::match($this->in_vars('q')),$paginator,Q::select_order($order,$this->in_vars('porder')));
			$paginator->vars('q',$this->in_vars('q'));
		}
		return array($object_list,$paginator);		
	}
	/**
	 * メールの詳細
	 * @param integer $id
	 * @automap
	 */
	public function mail_detail($id){
		$sbd = $this->smtp_blackhole_dao;
		$model = $sbd::find_get(Q::eq('id',$id));
		$this->vars('obj',$model);
	}
	/**
	 * Confの一覧
	 * @automap
	 */
	public function conf_list(){
		$list = array();
		foreach(self::config_all() as $p => $confs){
			foreach($confs as $n => $conf){
				$obj = new \org\rhaco\Object();
				$obj->package = $p;
				$obj->name = $n;
				$obj->type = $conf[0];
				$obj->summary = $conf[1];
				$obj->exists = \org\rhaco\Conf::exists($p,$n);
				if($this->search_str($obj->package,$obj->name,$obj->summary)) $list[$p.'@'.$n] = $obj;
			}
		}
		ksort($list);
		$this->vars('object_list',$list);
	}

	/**
	 * モジュールの一覧
	 * @automap
	 */
	public function module_list(){
		$list = array();
		foreach(Dt\Man::classes() as $package => $info){
			$i = Dt\Man::class_info($package);
			foreach($i['modules'] as $name => $m){
				$obj = new \org\rhaco\Object();
				$obj->package = $package;
				$obj->name = $name;
				$obj->summary = $m[0];
				
				if($this->search_str($obj->package,$obj->name,$obj->summary)) $list[] = $obj;
			}
		}
		$this->vars('object_list',$list);
	}
	/**
	 * @automap
	 */
	public function explorer(){
		$this->vars('maps',$this->get_flow_output_maps());
	}
	/**
	 * @automap
	 */
	public function method_info_json(){
		$result = array();
		try{
			$result = Dt\Man::method_info($this->in_vars('class'),$this->in_vars('method'));
		}catch(\Exception $e){}
		\org\rhaco\lang\Json::output($result);
	}
	/**
	 * @automap
	 */
	public function document(){
		$filename = $this->in_vars('page');
		if($filename[0] === '/') $filename = substr($filename,1);
		$path = \org\rhaco\Conf::get('document_path',\org\rhaco\io\File::resource_path('document'));
		if(substr($path,-1) !== '/') $path = $path.'/';
		$template_path = $path.'templates/';
		$list = $document_list = array();
		$paginator = null;

		if(is_file($template_path.$filename)){
			$this->set_block($template_path.$filename);
			$paginator = \org\rhaco\Paginator::dynamic_contents(1,$filename);
		}
		if(is_dir($template_path)){
			foreach(\org\rhaco\io\File::ls($template_path,true) as $f){
				$name = $file = str_replace($template_path,'',$f->fullname());
				$dirname = dirname($name);
				if($dirname == '.') $dirname = '';
				$src = file_get_contents($f->fullname());
				
				if($this->search_str($dirname.$src)){
					if(\org\rhaco\Xml::set($xml,$src,'h2')) $name = trim($xml->value());
					$list[$dirname.'/'.$f->fullname()] = array($name,$dirname,$file);
				}
			}
			$keys = array_keys($list);
			natcasesort($keys);

			foreach($keys as $k){
				if(isset($paginator) && !$paginator->add($list[$k][2])) break;
				$document_list[$list[$k][2]] = $list[$k];
			}
		}
		if(isset($paginator)) $paginator->vars('q',$this->in_vars('q'));
		$this->vars('document_list',$document_list);
		$this->vars('paginator',$paginator);
		$this->vars('q',$this->in_vars('q'));
	}
	/**
	 * @automap
	 * @param string $file
	 */
	public function document_media($file){
		$path = \org\rhaco\Conf::get('document_path',\org\rhaco\io\File::resource_path('document'));
		if(substr($path,-1) !== '/') $path = $path.'/';
		$media_path = $path.'media/'.$file;
		
		\org\rhaco\net\http\File::attach($media_path);
	}
	
	/**
	 * Configの一覧を取得する
	 * @return array
	 */
	static public function config_all(){
		$conf_get = function($filename){
			$src = file_get_contents($filename);
			$gets = array();
			if(preg_match_all('/[^\w]Conf::'.'(get)\(([\"\'])(.+?)\\2/',$src,$m)){
				foreach($m[3] as $k => $n){
					if(!isset($gets[$n])) $gets[$n] = array('string','');
				}
			}
			if(preg_match_all("/@conf\s+([^\s]+)\s+\\$(\w+)(.*)/",$src,$m)){
				foreach($m[0] as $k => $v) $docs[trim($m[2][$k])] = array($m[1][$k],trim($m[3][$k]));
			}
			if(preg_match_all("/@conf\s+\\$(\w+)(.*)/",$src,$m)){
				foreach($m[0] as $k => $v) $docs[trim($m[1][$k])] = array('string',trim($m[2][$k]));
			}
			foreach($gets as $n => $v){
				if(isset($docs[$n])) $gets[$n] = $docs[$n];
			}
			return $gets;
		};
		$gets = array();
		foreach(Dt\Man::classes() as $p => $lib){
			$ret = $conf_get($lib['filename']);
			if(!empty($ret)) $gets[$p] = $ret;
		}
		return $gets;
	}
	static public function class_info($class){
		return Dt\Man::class_info($class);
	}
	static public function method_info($class,$method){
		return Dt\Man::method_info($class,$method);
	}
	static public function classes(){
		return Dt\Man::classes();
	}
	static public function startup(){
		$cwd = getcwd();
		
		$bool = true;
		foreach(\org\rhaco\io\File::ls($cwd,false) as $f){
			if($f->is_ext('php')){
				$bool = false;
				break;
			}
		}
		if($bool){
			file_put_contents($cwd.'/index.php',file_get_contents(__DIR__.'/Dt/resources/index.tmpl'));
		}
		if(!is_file($f=$cwd.'/bootstrap.php')){
			file_put_contents($f,'<?php'.PPH_EOL.'include_once(\'vendor/autoload.php\');');
		}
		if(!is_file($f=$cwd.'/kate.php')){
			file_put_contents($f,file_get_contents('https://raw.github.com/tokushima/kate/master/kate.php'));
		}
		if(!is_file($f=$cwd.'/angela.php')){
			file_put_contents($f,file_get_contents('https://raw.github.com/tokushima/angela/master/angela.php'));
			file_put_contents($cwd.'/angela_cc.php',file_get_contents('https://raw.github.com/tokushima/angela/master/angela_cc.php'));			
		}
	}
	/**
	 * エントリのURL群
	 * @param string $dir
	 * @return array
	 */
	static public function get_urls($dir=null){
		if(empty($dir)) $dir = getcwd();
		$urls = array();
		foreach(new \RecursiveDirectoryIterator(
				$dir,
				\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
		) as $f){
			if(substr($f->getFilename(),-4) == '.php' && !preg_match('/\/[\._]/',$f->getPathname())){
				$entry_name = substr($f->getFilename(),0,-4);
				$src = file_get_contents($f->getPathname());
		
				if(strpos($src,'Flow') !== false){
					$entry_name = substr($f->getFilename(),0,-4);
					foreach(\org\rhaco\Flow::get_maps($f->getPathname()) as $p => $m){
						$urls[$entry_name.'::'.$m['name']] = $m['pattern'];
					}
				}
			}
		}
		return $urls;
	}
}
