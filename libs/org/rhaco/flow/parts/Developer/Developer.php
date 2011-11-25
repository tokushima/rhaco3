<?php
namespace org\rhaco\flow\parts;
use \org\rhaco\Exceptions;
use \org\rhaco\store\db\Q;
/**
 * マップ情報、モデル情報、パッケージ情報を表示
 * @author tokushima
 * @class @{"maps":["index","classes","class_src","class_info","method_info","do_create","do_detail","do_drop","do_find","do_update","mail_list","mail_detail","conf_list"]}
 * @login @{"has_require":true}
 */
class Developer extends \org\rhaco\flow\parts\RequestFlow{
	protected function __init__(){
		$name = $summary = $description = null;
		$d = debug_backtrace(false);
		$d = array_pop($d);

		if(preg_match('/\/\*\*[^\*].+?\*\//s',file_get_contents($d['file']),$m)){
			$doc = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$m[0])));
			$name = (preg_match('/@name\s(.+)/',$doc,$m)) ? trim($m[1]) : null;
			$summary = (preg_match('/@summary\s(.+)/',$doc,$m)) ? trim($m[1]) : null;
			$description = trim(preg_replace('/@.+/','',$doc));
		}
		if(is_dir(\Rhaco3::libs())){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\Rhaco3::libs(),\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST) as $e){
				if(strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false){
					if(ctype_upper(substr($e->getFilename(),0,1)) && substr($e->getFilename(),-4) == '.php'){
						try{
							include_once($e->getPathname());
						}catch(Exeption $ex){}
					}else if($e->getFilename() == 'vendors.phar'){
						$p = new \Phar($e->getPathname());
						foreach(new \RecursiveIteratorIterator($p) as $v){
							if(ctype_upper(substr($v->getFilename(),0,1)) && substr($v->getFilename(),-4) == '.php'){
								try{
									include_once($v->getPathname());
								}catch(Exeption $ex){}
							}
						}
					}
				}
			}
		}
		$models = array();
		foreach(get_declared_classes() as $class){
			$r = new \ReflectionClass($class);
			if((!$r->isInterface() && !$r->isAbstract()) && is_subclass_of($class,'\\org\\rhaco\store\\db\\Dao')) $models[] = $class;
		}
		sort($models);
		$this->vars('app_name',(empty($name) ? 'App' : $name));
		$this->vars('app_summary',$summary);
		$this->vars('app_description',$description);
		$this->vars('models',$models);
		$this->vars('f',new Developer\Helper());
		$this->vars('is_smtp_blackhole',in_array('org\rhaco\net\mail\module\SmtpBlackholeDao',$models));
	}
	public function get_template_modules(){
		return new \org\rhaco\flow\module\TwitterBootstrapPagination();
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
			if($this->search_str($q) && (!isset($m['class']) || $m['class'] != $self_name)) $maps[$k] = $m;
		}
		$this->vars('maps',$maps);
	}
	private function search_str(){
		$query = str_replace('　',' ',trim($this->in_vars('q')));
		if(!empty($query)){
			$args = func_get_args();
			foreach(explode(' ',$query) as $q){
				if(stripos(implode(' ',$args),$q) === false) return false;
			}
		}
		return true;
	}
	/**
	 * ライブラリの一覧
	 */
	public function classes(){
		$libs = array();
		foreach(get_declared_classes() as $class){
			$r = new \ReflectionClass($class);
			if(strpos($r->getFileName(),\Rhaco3::libs()) !== false && strpos($r->getFileName(),\Rhaco3::libs('_')) === false 
				&& (!$r->isInterface() && !$r->isAbstract()) && preg_match("/(.*)\\\\[A-Z][^\\\\]+$/",$class,$m) && preg_match("/^[^A-Z]+$/",$m[1])){
				$class_doc = $r->getDocComment();
				$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$class_doc))));
				list($summary) = explode("\n",$document);
				if($this->search_str($class,$document)){
					$src = file_get_contents($r->getFileName());
					$c = new \org\rhaco\Object();
					$c->summary = $summary;
					$c->usemail = (strpos($src,"\\org\\rhaco\\net\\mail\\Mail") !== false);
					$libs[$class] = $c;
				}
			}
		}
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
			}catch(\Exception $e){}
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
			}catch(\Exception $e){}
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
		$this->vars('query',$this->in_vars('query'));
		$this->vars('object_list',\org\rhaco\net\mail\module\SmtpBlackholeDao::find_all(Q::match($this->in_vars('query')),$paginator,Q::select_order($order,$this->in_vars('porder'))));
		$this->vars('paginator',$paginator->cp(array('query'=>$this->in_vars('query'),'order'=>$order)));
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
				$obj->class = $p;
				$obj->name = $n;
				$obj->type = $conf[0];
				$obj->summary = $conf[1];
				$obj->exists = \org\rhaco\Conf::exists($p,$n);
				if($this->search_str($obj->class,$obj->name,$obj->summary)) $list[$p.'@'.$n] = $obj;
			}
		}
		ksort($list);
		$this->vars('object_list',$list);
	}
}
