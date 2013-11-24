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
		
		$this->vars('app_mode',\org\rhaco\Conf::appmode());
		$this->vars('f',new Dt\Helper());
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
							if(!isset($m['template'])) $m['template'] = null;
														
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

		foreach(self::classes('\org\rhaco\store\db\Dao') as $class_info){
			$class = $class_info['class'];
			$r = new \ReflectionClass($class);
			$class_doc = $r->getDocComment();
			$package = str_replace('\\','.',substr($class,1));
			$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
			list($summary) = explode("\n",$document);
			$errors[$package] = null;
			$con[$package] = true;
			
			try{
				\org\rhaco\store\db\Dao::start_record();
				$class::find_get();
				\org\rhaco\store\db\Dao::stop_record();
			}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
			}catch(\org\rhaco\store\db\exception\DaoConnectionException $e){
				$errors[$package] = $e->getMessage();
				$con[$package] = false;
			}catch(\Exception $e){
				$errors[$package] = $e->getMessage();
				$error_query[$package] = print_r(\org\rhaco\store\db\Dao::recorded_query(),true);
			}
			$model_list[$package] = $summary;
		}
		ksort($model_list);
		$this->vars('dao_models',$model_list);
		$this->vars('dao_model_errors',$errors);
		$this->vars('dao_model_error_query',$error_query);
		$this->vars('dao_model_con',$con);
		$this->vars('getcwd',getcwd());
	}
	/**
	 * ライブラリの一覧
	 * @automap
	 */
	public function class_list(){
		$libs = array();
		foreach(self::classes() as $package => $info){
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
			}
			$c = new \org\rhaco\Object();
			$c->summary = $summary;
			$c->usemail = (strpos($src,'\org'.'\rhaco'.'\net'.'\mail'.'\Mail') !== false);
			$libs[$package] = $c;
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
		$class_name = str_replace(array('.','/'),'\\',$class);
		if(substr($class_name,0,1) != '\\') $class_name = '\\'.$class_name;
		
		if(is_subclass_of($class_name,'\org\rhaco\store\db\Dao')){
			try{
				$class_name::find_get();
			}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
			}catch(\Exception $e){}
		}
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
		list($object_list,$paginator) = $this->filter_find('\org\rhaco\net\mail\module\SmtpBlackholeDao',$order);
		$this->vars('object_list',$object_list);
		$this->vars('paginator',$paginator);
		$this->vars('model',new \org\rhaco\net\mail\module\SmtpBlackholeDao());
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
		$this->vars('obj',\org\rhaco\net\mail\module\SmtpBlackholeDao::find_get(Q::eq('id',$id)));
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
	static public function class_info($class){
		return Dt\Man::class_info($class);
	}
	static public function method_info($class,$method){
		return Dt\Man::method_info($class,$method);
	}
	/**
	 * ライブラリ一覧
	 * composerの場合はcomposer.jsonで定義しているPSR-0のもののみ
	 * @return array
	 */
	static public function classes($parent_class=null){
		$result = array();
		$include_path = array();
		if(is_dir(getcwd().'/lib')){
			$include_path[] = realpath(getcwd().'/lib');
		}
		if(class_exists('Composer\Autoload\ClassLoader')){
			$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
			$composer_dir = dirname($r->getFileName());
			$json_file = dirname(dirname($composer_dir)).'/composer.json';
			
			if(is_file($json_file)){
				$json = json_decode(file_get_contents($json_file),true);
				if(isset($json['autoload']['psr-0'])){
					foreach($json['autoload']['psr-0'] as $path){
						$p = realpath(dirname($json_file).'/'.$path);
						if($p !== false) $include_path[] = $p;
					}
				}
			}
		}
		foreach($include_path as $libdir){
			if($libdir !== '.'){
				foreach(new \RecursiveIteratorIterator(
							new \RecursiveDirectoryIterator(
									$libdir,
									\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
							),\RecursiveIteratorIterator::SELF_FIRST
				) as $e){
					if(strpos($e->getPathname(),'/.') === false 
							&& strpos($e->getPathname(),'/_') === false 
							&& strpos(strtolower($e->getPathname()),'/test') === false					
							&& ctype_upper(substr($e->getFilename(),0,1)) 
							&& substr($e->getFilename(),-4) == '.php'
					){
						try{
							include_once($e->getPathname());
						}catch(\Exeption $ex){}
					}
				}
			}
		}
		$set = function(&$result,$r,$include_path,$parent_class){
			if(!$r->isInterface() && !$r->isAbstract() && (empty($parent_class) || is_subclass_of($r->getName(),$parent_class))){
				if(empty($include_path)) return true;
				foreach($include_path as $libdir){
					if(strpos($r->getFileName(),$libdir) === 0){
						$n = str_replace('\\','/',$r->getName());
						$result[str_replace('/','.',$n)] = array('filename'=>$r->getFileName(),'class'=>'\\'.$r->getName());
						break;
					}
				}
			}
		};
		foreach(get_declared_classes() as $class){
			$set($result,new \ReflectionClass($class),$include_path,$parent_class);
		}
		$add = \org\rhaco\Conf::get('use_vendor',array());
		if(is_string($add)) $add = array($add);
		foreach($add as $class){
			$class = str_replace('.','\\',$class);
			if(substr($class,0,1) != '\\') $class = '\\'.$class;
			$set($result,new \ReflectionClass($class),$include_path,$parent_class);
		}
		ksort($result);		
		return $result;
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
