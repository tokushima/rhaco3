<?php
\org\rhaco\Dt\Man::classes();

/**
 * create table 
 * @param string $model
 */
if($has('model')){
	$model = str_replace('.','\\',$in_value('model'));
	if(empty($model)){
		throw new LogicException('model required');
	}
	if(substr($model,0,1) !== '\\') $model = '\\'.$model;
	if(call_user_func(array($model,'create_table'))){
		print('created: '.$model.PHP_EOL);
	}else{
		print('exists: '.$model.PHP_EOL);		
	}
}else{
	foreach(get_declared_classes() as $class){
		$r = new \ReflectionClass($class);
	
		if((!$r->isInterface() && !$r->isAbstract()) && is_subclass_of($class,'\\org\\rhaco\store\\db\\Dao')){
			if(call_user_func(array($r->getName(),'create_table'))){
				print('created '.$r->getName().PHP_EOL);
			}
		}
	}
}
