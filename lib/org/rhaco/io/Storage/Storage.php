<?php
namespace org\rhaco\io;
/**
 * ストレージへの操作
 * @author tokushima
 * @conf string[] $save_nodes ノードのパス配列
 * @conf string $service_name サービス名（フォルダ名）
 */
class Storage{
	/**
	 * ランダムなノードのパスを返す
	 * ディレクトリが存在しなかった場合はディレクトリを作成する
	 * @param string $type
	 * @return string ノード/サービス名/タイプ/Y/md/H
	 */
	static public function get_save_path($type='mixed'){
		$nodes = \org\rhaco\Conf::get('save_nodes');
		if(empty($nodes)) throw new \org\rhaco\io\Storage\StorageException('ノードが設定されていません');
		$service = \org\rhaco\Conf::get('service_name','new_service');
		$dir = \org\rhaco\net\Path::slash($nodes[rand(1,sizeof($nodes))-1],false,true).$service.'/'.$type.'/'.date('Y/md/H');
		\org\rhaco\io\File::mkdir(self::get_path($dir),0777);
		return $dir;
	}
	/**
	 * 書き込みテストを行う
	 * @throws RuntimeException
	 */
	static public function test(){
		$nodes = \org\rhaco\Conf::get('save_nodes');
		if(empty($nodes)) throw new \org\rhaco\io\Storage\StorageException('ノードが設定されていません');
		$service = \org\rhaco\Conf::get('service_name','new_service');
		foreach($nodes as $node){
			try{
				$file = self::get_path($node.'/node_con_test');
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
	static public function get_save_fullpath($type='mixed'){
		return self::get_path(self::get_save_path($type));
	}
	/**
	 * 指定したノードのパスを返す
	 * @param string $node
	 * @param string $type
	 */
	static public function get_select_path($node,$type='mixed'){
		$service = \org\rhaco\Conf::get('service_name','new_service');
		$dir = $node.'/'.$service.'/'.$type;
		return $dir;
	}
	/**
	 * 指定したノードのフルパスを返す
	 * @param string $node
	 * @param string $type
	 */
	static public function get_select_fullpath($node,$type='mixed'){
		return self::get_path(self::get_select_path($node,$type));
	}
	/**
	 * ファイルの実際のパスを返す
	 * @conf string $base_path ストレージのベースパス
	 * @param string $path ベースパスからの相対パス
	 * @return string
	 */
	static public function get_path($path){
		$base_path = \org\rhaco\Conf::get('base_path');
		if(empty($base_path)) throw new \org\rhaco\io\Storage\StorageException('ベースパスが指定されていません');
		return \org\rhaco\net\Path::absolute(\org\rhaco\net\Path::slash($base_path,null,true),$path);
	}	
}