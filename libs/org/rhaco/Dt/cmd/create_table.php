<?php
/**
 * Developer tool
 */
if(isset($params['create_table'])){
	$package = $params['create_table'];
	$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
	$dao = $r->newInstance();
	
	$con = \org\rhaco\Dt::get_dao_connection($dao);
	$sql = $con->connection_module()->create_table_sql($dao);
	print($sql.PHP_EOL);
			
	if(isset($params['commit'])){
		print('commit'.PHP_EOL);
		$con->query($sql);
	}
}
	