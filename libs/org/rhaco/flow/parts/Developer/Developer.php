<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Q;
/**
 * マップ情報、モデル情報、パッケージ情報を表示
 * @author tokushima
 * @class @{"maps":["index","classes","class_src","class_info","method_info","do_create","do_detail","do_drop","do_find","do_update","mail_list","mail_detail","conf_list","model_list","class_module_info","entry_list","module_list"]}
 * @login @{"has_require":true}
 */
class Developer extends \org\rhaco\flow\parts\RequestFlow{
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

		list($name,$summary,$description) = $this->entry_desc($d['file']);
		$this->vars('app_name',(empty($name) ? 'App' : $name));
		$this->vars('app_summary',$summary);
		$this->vars('app_description',$description);
		$this->vars('f',new Developer\Helper());

	}
	public function get_template_modules(){
		return array(
					new \org\rhaco\flow\module\TwitterBootstrapPagination()
					,new \org\rhaco\flow\module\Exceptions()
				);
	}
	/**
	 * Daoモデルの一覧
	 */
	public function model_list(){
		$list = $errors = $model_list = array();
		foreach(\org\rhaco\Man::libs() as $package => $info){
			if(is_subclass_of($info['class'],'\org\rhaco\store\db\Dao')) $model_list[] = $info['class'];
		}
		foreach($model_list as $m){
			if($this->search_str($m)){
				$r = new \ReflectionClass($m);
				$class_doc = $r->getDocComment();
				$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
				list($summary) = explode("\n",$document);
				$list[$m] = $summary;
				$errors[$m] = null;
				try{
					\org\rhaco\store\db\Dao::start_record();
					$m::find_get();
					\org\rhaco\store\db\Dao::stop_record();
				}catch(\org\rhaco\store\db\exception\NotfoundDaoException $e){
				}catch(\Exception $e){
					$errors[$m] = $e->getMessage();
					\org\rhaco\Log::error(\org\rhaco\store\db\Dao::recorded_query());
				}
			}
		}
		$this->vars('dao_models',$list);
		$this->vars('dao_model_errors',$errors);
	}
	/**
	 * アプリケーションのマップ一覧
	 */
	public function index(){
		$maps = array();
		$self_name = str_replace("\\",'.',__CLASS__);
		foreach($this->maps() as $k => $m){
			$q = '';
			foreach(array('name','class','method','url','template') as $n){
				if(isset($m[$n])) $q .= $m[$n];
			}
			if($this->search_str($q) && (!isset($m['class']) || $m['class'] != $self_name)){
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
				$maps[$k] = $m;
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
	 */
	public function classes(){
		$libs = array();
		foreach(\org\rhaco\Man::libs() as $info){
			$r = new \ReflectionClass($info['class']);
			$class_doc = $r->getDocComment();
			$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
			list($summary) = explode("\n",$document);
			if($this->search_str($info['class'],$document)){
				$src = file_get_contents($r->getFileName());
				$c = new \org\rhaco\Object();
				$c->summary = $summary;
				$c->usemail = (strpos($src,'\org'.'\rhaco'.'\net'.'\mail'.'\Mail') !== false);
				$libs[$info['class']] = $c;
			}
		}
		ksort($libs);
		$this->vars('packages',$libs);
	}
	/**
	 * クラスのソース表示
	 * @param string $class
	 */
	public function class_src($class){
		$info = \org\rhaco\Man::class_info($class);
		foreach(\org\rhaco\Man::class_info($class) as $k => $v){
			$this->vars($k,$v);
		}
		$this->vars('class_src',file_get_contents($info['filename']));
	}
	/**
	 * クラスのドキュメント
	 * @param string $class
	 */
	public function class_info($class){
		foreach(\org\rhaco\Man::class_info($class) as $k => $v){
			$this->vars($k,$v);
		}
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
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
		$r = new \ReflectionClass('\\'.str_replace('/','\\',$name));
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
	public function do_find($name){
		$name = '\\'.str_replace('/','\\',$name);
		$order = \org\rhaco\lang\Sorter::order($this->in_vars('order'),$this->in_vars('porder'));
		$paginator = new \org\rhaco\Paginator(20,$this->in_vars('page'));
		$this->vars('q',$this->in_vars('q'));
		$this->vars('object_list',$name::find_all(Q::match($this->in_vars('q')),$paginator,Q::select_order($order,$this->in_vars('porder'))));
		$this->vars('paginator',$paginator->cp(array('q'=>$this->in_vars('q'),'order'=>$order)));
		$this->vars('model',new $name());
		$this->vars('model_name',$name);
	}
	/**
	 * 詳細
	 * @param string $name モデル名
	 */
	public function do_detail($name){
		$obj = $this->get_model($name);
		$this->vars('object',$obj);
		$this->vars('model',$obj);
		$this->vars('model_name',$name);
	}
	/**
	 * 削除
	 * @param string $name モデル名
	 */
	public function do_drop($name){
		if($this->is_post()){
			$this->get_model($name)->delete();
			\org\rhaco\net\http\Header::redirect_referer();
		}
	}
	/**
	 * 更新
	 * @param string $name モデル名
	 */
	public function do_update($name){
		if($this->is_post()){
			try{
				$obj = $this->get_model($name,false);
				$obj->set_props($this);
				$obj->save();
				
				if($this->is_vars('save_and_add_another')){
					$this->redirect_by_method('do_create',$name);
				}else{
					$this->redirect_by_method('do_find',$name);
				}
			}catch(\Exception $e){
				\org\rhaco\Log::error($e);
			}
		}else{
			$obj = $this->get_model($name);
		}
		$this->vars('model',$obj);
		$this->vars('model_name',$name);
	}
	/**
	 * 作成
	 * @param string $name モデル名
	 */
	public function do_create($name){
		if($this->is_post()){
			try{
				$obj = $this->get_model($name,false);
				$obj->set_props($this);
				$obj->save();
				
				if($this->is_vars('save_and_add_another')){
					$this->redirect_by_method('do_create',$name);
				}else{
					$this->redirect_by_method('do_find',$name);
				}
			}catch(\Exception $e){
				\org\rhaco\Log::error($e);
			}
		}else{
			$obj = $this->get_model($name,false);
		}
		$this->vars('model',$obj);
		$this->vars('model_name',$name);
	}
	/**
	 * メールの一覧
	 */
	public function mail_list(){
		$paginator = new \org\rhaco\Paginator(20,$this->in_vars('page'));
		$order = $this->in_vars('order','-id');
		$list = array();
		try{
			$list = \org\rhaco\net\mail\module\SmtpBlackholeDao::find_all(Q::match($this->in_vars('q')),$paginator,Q::select_order($order,$this->in_vars('porder')));
		}catch(\Exception $e){}
		$this->vars('q',$this->in_vars('q'));
		$this->vars('object_list',$list);
		$this->vars('paginator',$paginator->cp(array('q'=>$this->in_vars('q'),'order'=>$order)));
	}
	/**
	 * メールの詳細
	 * @param integer $id
	 */
	public function mail_detail($id){
		$model = \org\rhaco\net\mail\module\SmtpBlackholeDao::find_get(Q::eq('id',$id));
		$this->vars('obj',$model);
	}
	/**
	 * Confの一覧
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
	}
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
