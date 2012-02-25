<?php
if(isset($params['create_table'])){
	$primary = $columndef = array();
	$package = $params['create_table'];
	if(!is_subclass_of('\\'.str_replace('.','\\',$package),'\org\rhaco\store\db\Dao')) throw new \RuntimeException('not Dao');

	$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
	$dao = $r->newInstance();

	$connections = \org\rhaco\store\db\Dao::connections();
	$conf = explode("\\",$r->getName());

	while(!isset($connections[implode('.',$conf)]) && !empty($conf)) array_pop($conf);

	if(empty($conf)){
		if(!array_search('*',$keys)) throw new \RuntimeException($package.' connection not found');
		$conf = array('*');
	}
	$conf = implode('.',$conf);

	foreach($connections as $k => $con){
		if($k == $conf){
			$sql = $con->connection_module()->create_table_sql($dao);
			
			print($sql.PHP_EOL);
			
			if(isset($params['commit'])){
				print('commit'.PHP_EOL);
				$con->query($sql);
			}
			break;
		}
	}
}
	