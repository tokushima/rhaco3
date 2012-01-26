<?php
/**
 * Database model
 */
$package = 'model';
if(isset($params['table'])){
	// TODO 指定のパッケージのcreate tableのサンプル出力
}else if(isset($params['export'])){
	// TODO 全データを指定のファイルにinsert文で出力
}else if(isset($params['create'])){
	$package = $params['create'];
	$p = explode('.',$package);
	
	$class = array_pop($p);
	$namespace = implode('\\',$p);
	$path = Rhaco3::lib_dir().implode('/',$p).'/'.$class.'.php';
	
$model_src = <<<'__SRC__'
<?php
namespace %s;
/**
 * document
 * @var serial $id
 * @var string $value
 * @var timestamp $create_date @{"auto_now_add":true}
 * @var timestamp $update_date @{"auto_now":true}
 */
class %s extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $value;
	protected $create_date;
	protected $update_date;
}

__SRC__;
	
	if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
	file_put_contents($path,sprintf($model_src,$namespace,$class));
	print('Writen: '.$path.PHP_EOL.PHP_EOL);
}
print('Connection sample:'.PHP_EOL);
print(' \org\rhaco\Conf::set("org.rhaco.store.db.Dao","'.$package.'",\'{"type":"org.rhaco.store.db.module.Mysql","dbname":"sample","encode":"utf8","user":"root","password":"root"}\');'.PHP_EOL);
