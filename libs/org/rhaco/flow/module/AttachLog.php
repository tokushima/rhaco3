<?php
namespace org\rhaco\flow\module;
/**
 * ダウンロードログのモデル
 * @author tokushima
 * @var serial $id
 * @var string $path
 * @var string $lang
 * @var string $agent
 * @var string $referer
 * @var timestamp $create_date @{"auto_now_add":true}
 * @sql 
 */
class AttachLog extends \org\rhaco\store\db\Dao implements \IteratorAggregate{
/*	
CREATE TABLE `attach_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) DEFAULT NULL,
  `lang` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `create_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
*/
	protected $id;
	protected $path;
	protected $lang;
	protected $agent;
	protected $referer;
	protected $create_date;
	
	protected function __init__(){
		$this->lang = substr((isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null),0,140);
		$this->agent = substr((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null),0,140);
		$this->referer = substr((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null),0,140);
	}
	/**
	 * @see org.rhaco.flow.File
	 * @param string $path
	 * @conf string $ignore 除外対象文字列(カンマ区切り)
	 */
	public function before_attach($path){
		$ignore = \org\rhaco\Conf::get('ignore');
		if(!empty($ignore) && !empty($this->agent)){
			foreach(explode(',',$ignore) as $i){
				if(strpos($this->agent,$i) !== false){
					\org\rhaco\net\http\Header::end_status(404);
					exit;
				}
			}
		}
		$this->path(substr($path,0,140));
		$this->save();
	}
	public function getIterator(){
		return new \ArrayIterator($this->props());
	}
	public function view(){
		$result = array('object_list'=>array(),'paginator'=>null);
		$req = new \org\rhaco\Request();
		$order = \org\rhaco\lang\Sorter::order($req->in_vars('order','-id'),$req->in_vars('porder'));
		$paginator = new \org\rhaco\Paginator($req->in_vars('paginate_by',20),$req->in_vars('page',1));
		$result['object_list'] = static::find_all(\org\rhaco\store\db\Q::match($req->in_vars('query'),\org\rhaco\store\db\Q::IGNORE),$paginator,\org\rhaco\store\db\Q::select_order($order,$req->in_vars('porder')));
		$result['paginator'] = $paginator->cp(array('query'=>$req->in_vars('query'),'order'=>$order));
		$result['query'] = $req->in_vars('query');
		$result['order'] = $order;
		return $result;
	}
}