<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Q;
/**
 * マップ情報、モデル情報、パッケージ情報を表示
 * @author tokushima
 */
class Developer extends \org\rhaco\flow\parts\RequestFlow{
	private $smtp_blackhole_dao;
	private $dao;

	private function entry_desc($file){
		$entry = substr(basename($file),0,-4);
		if(preg_match('/\/\*\*[^\*].+?\*\//s',file_get_contents($file),$m)){
			$doc = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$m[0])));
			$name = (preg_match('/@name\s(.+)/',$doc,$m)) ? trim($m[1]) : null;
			$summary = (preg_match('/@summary\s(.+)/',$doc,$m)) ? trim($m[1]) : null;
			$description = trim(preg_replace('/@.+/','',$doc));
			return array($entry,$name,$summary,$description);
		}
		return array($entry,$entry,'','');
	}
	protected function __init__(){
		$name = $summary = $description = null;
		$d = debug_backtrace(false);
		$d = array_pop($d);
		$this->smtp_blackhole_dao = '\\'.implode('\\',array('org','rhaco','net','mail','module','SmtpBlackholeDao'));
		$this->dao = '\\'.implode('\\',array('org','rhaco','store','db','Dao'));
		
		list($entry,$name,$summary,$description) = $this->entry_desc($d['file']);
		$this->vars('app_name',(empty($name) ? 'App' : $name));
		$this->vars('app_summary',$summary);
		$this->vars('app_description',$description);
		$this->vars('app_dirname',basename(dirname($d['file'])));
		$this->vars('app_mode',\Rhaco3::mode());
		$this->vars('f',new Developer\Helper());
		$this->vars('has_smtp_blackhole_dao',class_exists($this->smtp_blackhole_dao));
		$this->vars('has_dao',class_exists($this->dao));
	}
	public function get_template_modules(){
		return array(
					new \org\rhaco\flow\module\TwitterBootstrapPagination()
					,new \org\rhaco\flow\module\Exceptions()
					,new Developer\Formatter()
					,new \org\rhaco\flow\module\Dao()
				);
	}
	/**
	 * Daoモデルの一覧
	 * @automap
	 */
	public function model_list(){
		$list = $errors = $error_query = $model_list = $con = array();
		foreach(\org\rhaco\Man::libs() as $package => $info){
			if($info['dir']){
				foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname($info['filename']),\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS),\RecursiveIteratorIterator::SELF_FIRST) as $e){
					if(ctype_upper(substr($e->getFilename(),0,1)) && substr($e->getFilename(),-4) == '.php'){
						try{
							include_once($e->getPathname());
						}catch(\Exeption $ex){}
					}
				}
			}			
		}
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
	/**
	 * アプリケーションのマップ一覧
	 * @automap
	 */
	public function index(){
		$maps = array();
		$self_name = str_replace("\\",'.',__CLASS__);
		foreach($this->maps() as $k => $m){
			if(!isset($m['class']) || $m['class'] != $self_name){
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
				if($this->search_str(
					$m['name']
					,(isset($m['class'])?$m['class']:'')
					,(isset($m['method'])?$m['method']:'')
					,(isset($m['template'])?$m['template']:'')
					,$m['url']
					,$m['summary']
				)) $maps[$k] = $m;
			}
		}
		$this->vars('maps',$maps);
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
	public function classes(){
		$libs = array();
		foreach(\org\rhaco\Man::libs() as $package => $info){
			$r = new \ReflectionClass($info['class']);
			$class_doc = $r->getDocComment();
			$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
			list($summary) = explode("\n",$document);
			if($this->search_str($info['class'],$document)){
				$src = file_get_contents($r->getFileName());
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
		$info = \org\rhaco\Man::class_info($class);
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
	public function class_info($class){
		$info = \org\rhaco\Man::class_info($class);
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
	public function method_info($class,$method){
		foreach(\org\rhaco\Man::method_info($class,$method) as $k => $v){
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
		$ref = \org\rhaco\Man::class_info($class);
		if(!isset($ref['modules'][$module_name])) throw new \LogicException($module_name.' not found');
		$this->vars('package',$class);
		$this->vars('module_name',$module_name);
		$this->vars('description',$ref['modules'][$module_name][0]);
		$this->vars('params',$ref['modules'][$module_name][1]);
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
		$object_list = array();
		
		if(empty($order)){
			$dao = new $name();
			foreach($dao->props() as $n => $v){
				if($dao->prop_anon($n,'primary')){
					$order = '-'.$n;
					break;
				}
			}
		}
		$paginator = new \org\rhaco\Paginator(20,$this->in_vars('page'));
		$paginator->cp(array('order'=>$order));
		
		if($this->is_vars('search_clear')){
			$object_list = $name::find_all($paginator,Q::select_order($order,$this->in_vars('porder')));
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
			$object_list = $name::find_all($q,$paginator,Q::select_order($order,$this->in_vars('porder')));
			$this->rm_vars('q');	
		}else{
			$object_list = $name::find_all(Q::match($this->in_vars('q')),$paginator,Q::select_order($order,$this->in_vars('porder')));
			$paginator->vars('q',$this->in_vars('q'));
		}
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
	 * @automap
	 */
	public function do_drop($package){
		if($this->is_post()){
			$this->get_model($package)->delete();
			\org\rhaco\net\http\Header::redirect_referer();
		}
	}
	/**
	 * 更新
	 * @param string $package モデル名
	 * @automap
	 */
	public function do_update($package){
		if($this->is_post()){
			try{
				try{
					$obj = $this->get_model($package,false);
					$obj->set_props($this);
					$obj->save();
				}catch(\org\rhaco\store\db\exception\DaoBadMethodCallException $e){}

				if($this->is_vars('save_and_add_another')){
					$this->redirect_by_method('do_create',$package);
				}else{
					$this->redirect_by_method('do_find',$package);
				}				
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
	 * @automap
	 */
	public function do_create($package){
		if($this->is_post()){
			try{
				$obj = $this->get_model($package,false);
				$obj->set_props($this);
				$obj->save();
				
				if($this->is_vars('save_and_add_another')){
					$this->redirect_by_method('do_create',$package);
				}else{
					$this->redirect_by_method('do_find',$package);
				}
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
				$sql = str_replace(array('\\r\\n','\\r','\\n'),array("\n","\n","\n"),$sql);
				foreach(explode(';',$sql) as $q){
					$q = trim($q);
					if(!empty($q)) $con->query($q);
				}
				foreach($con as $k => $v){
					if(empty($keys)) $keys = array_keys($v);
					$result_list[] = $v;
					$count++;
					
					if($count >= 100) break;
				}
				$this->rm_vars('sql');
				$this->vars('excute_sql',$sql);
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
		$paginator = new \org\rhaco\Paginator(20,$this->in_vars('page'));
		$order = $this->in_vars('order','-id');
		$list = array();
		try{
			$sbd = $this->smtp_blackhole_dao;
			$list = $sbd::find_all(Q::match($this->in_vars('q')),$paginator,Q::select_order($order,$this->in_vars('porder')));
		}catch(\Exception $e){}
		$this->vars('q',$this->in_vars('q'));
		$this->vars('object_list',$list);
		$this->vars('paginator',$paginator->cp(array('q'=>$this->in_vars('q'),'order'=>$order)));
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
		foreach(\org\rhaco\Conf::all() as $p => $confs){
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
		$this->vars('session_module_name',\org\rhaco\net\Session::get_module_name());
		$this->vars('umask',sprintf('%04o',umask()));
	}
	/**
	 * エントリ一覧
	 * @automap
	 */
	public function entry_list(){
		$trace = debug_backtrace(false);
		$entry = array_pop($trace);
		$entry_list = array();
		foreach(new \DirectoryIterator(dirname($entry['file'])) as $f){
			if($f->isFile() && substr($f->getPathname(),-4) == '.php' && substr($f->getFilename(),0,1) != '_'){
				$src = file_get_contents($f->getPathname());
				if(strpos($src,'Flow') !== false && strpos($src,'patterns') !== false && strpos($src,'output') !== false && strpos($src,'class Rhaco3') === false){
					$maps = \org\rhaco\Flow::get_maps($f->getFilename());
					$dev_index = null;
					foreach($maps as $m){
						if(isset($m['class']) && isset($m['method']) 
							&& $m['class'] == 'org.rhaco.flow.parts.Developer' && $m['method'] == 'index'
						){
							$dev_index = $m['format'];
							break;
						}
					}
					list($entry,$name,$summary,$description) = $this->entry_desc($f->getFilename());
					$obj = new \org\rhaco\Object();
					$obj->entry = $entry;
					$obj->name = $name;
					$obj->summary = $summary;
					$obj->url = $dev_index;
					
					if($this->search_str($obj->entry,$obj->name,$obj->summary)) $entry_list[] = $obj;
				}
			}
		}
		$this->vars('object_list',$entry_list);
	}
	/**
	 * モジュールの一覧
	 * @automap
	 */
	public function module_list(){
		$list = array();
		foreach(\org\rhaco\Man::libs() as $package => $info){
			$i = \org\rhaco\Man::class_info($package);
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
}
