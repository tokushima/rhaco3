<?php
namespace org\rhaco\io;
/**
 * ストレージへの操作
 * @author tokushima
 */
class Storage{
	/**
	 * ランダムなノードのパスを返す
	 * ディレクトリが存在しなかった場合はディレクトリを作成する
	 * @param string $type
	 * @conf string[] $save_nodes ノードのパス配列
	 * @conf string $service_name サービス名（フォルダ名）
	 * @return string
	 */
	public function get_save_path($type='mixed'){
		$nodes = \org\rhaco\Conf::get_array('save_nodes');
		if(empty($nodes)) throw new \org\rhaco\io\Storage\StorageException('ノードが設定されていません');
		$service = \org\rhaco\Conf::get('service_name','new_service');
		$dir = \org\rhaco\net\Path::slash($nodes[rand(1,sizeof($nodes))-1],false,true).$service.'/'.$type.'/'.date('Y/md/H');
		\org\rhaco\io\File::mkdir($this->get_path($dir));
		return $dir;
	}
	/**
	 * 書き込みテストを行う
	 * @throws RuntimeException
	 */
	static public function test(){
		$nodes = \org\rhaco\Conf::get_array('save_nodes');
		if(empty($nodes)) throw new \org\rhaco\io\Storage\StorageException('ノードが設定されていません');
		$service = \org\rhaco\Conf::get('service_name','new_service');
		foreach($nodes as $node){
			try{
				$file = $this->get_path($node.'/node_con_test');
				\org\rhaco\io\File::write($file,__CLASS__);
				\org\rhaco\io\File::read($file);
				\org\rhaco\io\File::rm($file);
			}catch(\Exception $e){
				\org\rhaco\Exceptions::add($e,$node);
			}
		}
		\org\rhaco\Exceptions::throw_over();
	}	
	/**
	 * ランダムノードのフルパスを返す
	 * @param string $type
	 */
	public function get_save_fullpath($type='mixed'){
		return $this->get_path($this->get_save_path($type));
	}
	/**
	 * 指定したノードのパスを返す
	 * @param string $node
	 * @param string $type
	 */
	public function get_select_path($node,$type='mixed'){
		$nodes = \org\rhaco\Conf::get_array('save_nodes');
		if(empty($nodes)) throw new \org\rhaco\io\Storage\StorageException('ノードが設定されていません');
		if(!in_array($node,$nodes)) throw new \org\rhaco\io\Storage\StorageException('指定のノードが設定されていません `'.$node.'`');		
		$service = \org\rhaco\Conf::get('service_name','new_service');
		$dir = $node.'/'.$service.'/'.$type;
		\org\rhaco\io\File::mkdir($this->get_path($dir));
		return $dir;
	}
	/**
	 * 指定したノードのフルパスを返す
	 * @param string $node
	 * @param string $type
	 */
	public function get_select_fullpath($node,$type='mixed'){
		return $this->get_path($this->get_select_path($node,$type));
	}
	/**
	 * ファイルの実際のパスを返す
	 * @conf string $base_path ストレージのベースパス
	 * @param string $path ベースパスからの相対パス
	 * @return string
	 */
	public function get_path($path){
		$base_path = \org\rhaco\Conf::get('base_path');
		if(empty($base_path)) throw new \org\rhaco\io\Storage\StorageException('ベースパスが指定されていません');
		return \org\rhaco\net\Path::absolute(\org\rhaco\net\Path::slash($base_path,null,true),$path);
	}
	
}