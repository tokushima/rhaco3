<?php
list($value,$params) = array(isset($_ENV['value'])?$_ENV['value']:null,isset($_ENV['params'])?$_ENV['params']:array());

if(isset($params['create_database']) && !empty($params['create_database'])){
	$dbc = new \org\rhaco\store\db\Dbc('{"type":"org.rhaco.store.db.module.Sqlite","dbname":"'.$params['create_database'].'"}');
}else if(isset($params['create_table']) && !empty($params['create_table'])){
	$dbc = new \org\rhaco\store\db\Dbc('{"type":"org.rhaco.store.db.module.Sqlite","dbname":"'.$params['create_database'].'"}');	
//	$dbc->query($params['create_table']);
//		$sql = 'create table '.$this->quotation($name).'(';
//		$columndef = $primary = array();
//		foreach($columns as $column_name => $column){
//			$column_str = '';
//			switch($column['type']){
//				case 'mixed':
//				case 'string': $column_str = $this->quotation($column_name).' TEXT'; break;
//				case 'text': $column_str = $this->quotation($column_name).' BLOB'; break;
//				case 'number': $column_str = $this->quotation($column_name).' REAL'; break;
//				case 'serial': $column_str = $this->quotation($column_name).' INTEGER PRIMARY KEY'; break;
//				case 'boolean': $column_str = $this->quotation($column_name).' INTEGER'; break;
//				case 'timestamp': $column_str = $this->quotation($column_name).' INTEGER'; break;
//				case 'date': $column_str = $this->quotation($column_name).' BLOB'; break;
//				case 'time': $column_str = $this->quotation($column_name).' INTEGER'; break;
//				case 'intdate':
//				case 'integer': $column_str = $this->quotation($column_name).' INTEGER'; break;
//				case 'email': $column_str = $this->quotation($column_name).' TEXT'; break;
//				case 'alnum': $column_str = $this->quotation($column_name).' BLOB'; break;
//				case 'choice': $column_str = $this->quotation($column_name).' BLOB'; break;
//				default: throw new InvalidArgumentException("undefined type `".$column['type']."`");
//			}
//			$column_str .= (($column['require']) ? ' not' : '').' null ';
//			if($column['primary'] || $column['type'] == "serial") $primary[] = $this->quotation($column_name);
//			$columndef[] = $column_str;
//		}
//		$sql .= implode(",",$columndef);
//		$sql .= "\n)";
}


