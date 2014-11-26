<?php
/**
 * dao data import
 * @param string $file
 */

if(empty($file)){
	$file = getcwd().'/dump.ddj';
}
$dao_list = array();
foreach(\org\rhaco\Dt::classes('\org\rhaco\store\db\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	
	if($r->getParentClass()->getName() == 'org\rhaco\store\db\Dao'){
		$dao_list[] = $r->getName();
	}
}

$current = null;

$fp = fopen($file,'rb');
\cmdman\Std::println_info('Load '.$file);

while(!feof($fp)){
	$line = fgets($fp);
	
	if(!empty($line)){
		if($line[0] == '['){
			$current = null;
			$class = preg_replace('/\[\[(.+)\]\]/','\\1',trim($line));
			
			if(in_array($class, $dao_list)){
				$ref = new \ReflectionClass($class);
				$current = $ref->newInstance();
				\cmdman\Std::println_success('Update '.get_class($current));
			}
		}else if($line[0] == '{' && !empty($current)){
			$obj = clone($current);
			$arr = json_decode($line,true);

			try{
				foreach($obj->props() as $k => $v){
					if(array_key_exists($k,$arr)){
						$func = call_user_func_array(array($obj,$k),array($arr[$k]));
						
						if($obj->prop_anon($k,'cond') == null && $obj->prop_anon($k,'extra',false) === false){
							$obj->prop_anon($k,'auto_now',false,true);
							$func;
						}
					}
				}
				$obj->save();
			}catch(\ebi\exception\BadMethodCallException $e){
				$current = null;
			}				
		}
	}
}


