<?php
list($value,$params) = array(isset($_ENV['value'])?$_ENV['value']:null,isset($_ENV['params'])?$_ENV['params']:array());

$quote = function($name){
	return '`'.$name.'`';
};
if(isset($params['create_table'])){
	$primary = $columndef = array();
	$package = $params['create_table'];
	if(!is_subclass_of('\\'.str_replace('.','\\',$package),'\org\rhaco\store\db\Dao')) throw new \RuntimeException('not Dao');

	$to_column_type = function($dao,$type,$name) use($quote){
		switch($type){
			case '':
			case 'mixed':
			case 'string':
				return $quote($name).' varchar('.$dao->prop_anon($name,'max',255).')';
			case 'alnum':
			case 'text':
				return $quote($name).(($dao->prop_anon($name,'max') !== null) ? ' varchar('.$dao->prop_anon($name,'max').')' : ' text');
			case 'number':
				return $quote($name).' '.(($dao->prop_anon($name,'decimal_places') !== null) ? sprintf('numeric(%d,%d)',26-$dao->prop_anon($name,'decimal_places'),$dao->prop_anon($name,'decimal_places')) : 'double');
			case 'serial': return $quote($name).' int auto_increment';
			case 'boolean': return $quote($name).' int(1)';
			case 'timestamp': return $quote($name).' timestamp';
			case 'date': return $quote($name).' date';
			case 'time': return $quote($name).' int';
			case 'intdate': 
			case 'integer': return $quote($name).' int';
			case 'email': return $quote($name).' varchar(255)';
			case 'choice': return $quote($name).' varchar(255)';
			default: throw new InvalidArgumentException('undefined type `'.$type.'`');
		}
	};
	$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
	$dao = $r->newInstance();
	
	$sql = 'create table '.$quote($dao->table()).'('.PHP_EOL;	
	foreach($dao->props() as $prop_name => $v){
		if($dao->prop_anon($prop_name,'extra') !== true){
			$column_str = '  '.$to_column_type($dao,$dao->prop_anon($prop_name,'type'),$prop_name);
			$column_str .= (($dao->prop_anon($prop_name,'require') === true) ? ' not' : '').' null ';
			
			$columndef[] = $column_str;
			if($dao->prop_anon($prop_name,'primary') === true || $dao->prop_anon($prop_name,'type') == 'serial') $primary[] = $quote($prop_name);
		}
	}
	$sql .= implode(','.PHP_EOL,$columndef).PHP_EOL;
	if(!empty($primary)) $sql .= ' ,primary key ( '.implode(',',$primary).' ) '.PHP_EOL;
	$sql .= ' ) engine = InnoDB character set utf8 collate utf8_general_ci;'.PHP_EOL;
	
	print($sql.PHP_EOL);
}
	