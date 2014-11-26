<?php
/**
 * dao data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/dump.ddj';
}
\org\rhaco\io\File::write($file,'');

foreach(\org\rhaco\Dt::classes('\org\rhaco\store\db\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	
	if($r->getParentClass()->getName() == 'org\rhaco\store\db\Dao'){
		\cmdman\Std::println_info('Find '.$r->getName());
		\org\rhaco\io\File::append($file,'[['.$r->getName().']]'.PHP_EOL);

		$find = call_user_func(array($r->getName(),'find'));
		foreach($find as $obj){
			\org\rhaco\io\File::append($file,json_encode($obj->props()).PHP_EOL);
		}
	}
}
\cmdman\Std::println_success('Written '.$file);
