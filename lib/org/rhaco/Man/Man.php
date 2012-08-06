<?php
namespace org\rhaco;
/**
 * ドキュメントの取得
 * @author tokushima
 */
class Man{
	/**
	 * クラスのドキュメント
	 * @param string $class
	 */
	static public function class_info($class){
		$r = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$class));
		if($r->getFilename() === false || !is_file($r->getFileName())) throw new \InvalidArgumentException('`'.$class.'` file not found.');
		$src = implode(array_slice(file($r->getFileName()),$r->getStartLine(),($r->getEndLine()-$r->getStartLine()-1)));
		$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$r->getDocComment())));
		$extends = ($r->getParentClass() === false) ? null : $r->getParentClass()->getName();
		$updated = filemtime($r->getFilename());
		if(substr(basename($r->getFilename()),0,-4) === basename(dirname($r->getFilename()))){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname($r->getFilename()),\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS)) as $f){
				if(($u = filemtime($f->getPathname())) > $updated) $updated = $u;
			}
		}
		$methods = $static_methods = $protected_methods = $protected_static_methods = array(array(),array());
		$module_method = array();
		foreach($r->getMethods() as $method){
			if(substr($method->getName(),0,1) != '_' && ($method->isPublic() || $method->isProtected())){
				$method_document = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$method->getDocComment()));
				list($method_description) = explode("\n",trim(preg_replace('/@.+/','',$method_document)));
				if(strpos($method_description,'non-PHPdoc') !== false){
					if(preg_match("/@see\s+(.*)/",$method_document,$match)){
						$method_description = str_replace("\\",'.',trim($match[1]));
						if(preg_match("/^.+\/([^\/]+)$/",$method_description,$m)) $method_description = trim($m[1]);
						if(substr($method_description,0,1) == '.') $method_description = substr($method_description,1);
						if(strpos($method_description,'::') !== false){
							list($c,$m) = explode('::',str_replace(array('(',')'),'',$method_description));
							try{
								$i = self::method_info($c,$m);
								list($method_description) = explode("\n",$i['description']);
							}catch(\Exception $e){
								$method_description = '@see '.$method_description;
							}
						}
					}
				}
				if(preg_match_all("/@module\s+([\w\.\\\\]+)/",$method_document,$match)){
					foreach($match[1] as $v) $module_method[trim($v)][] = $method->getName();
				}

				$dec = ($method->getDeclaringClass()->getFileName() == $r->getFileName()) ? 0 : 1;
				if($method->isStatic()){
					if($method->getDeclaringClass()->getName() == $r->getName()){
						if($method->isPublic()){
							$static_methods[$dec][$method->getName()] = $method_description;
						}else{
							$protected_static_methods[$dec][$method->getName()] = $method_description;								
						}
					}
				}else{
					if($method->isPublic()){
						$methods[$dec][$method->getName()] = $method_description;
					}else{
						$protected_methods[$dec][$method->getName()] = $method_description;
					}
				}
			}
		}
		$tasks = array();
		if(preg_match_all("/TODO[\040\t](.+)/",$src,$match)){
			foreach($match[1] as $t) $tasks[] = trim($t);
		}
		$modules = array();
		if(preg_match_all("/->object_module\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v) self::get_desc($modules,$match,$k,$v[0],$src,$class);
		}
		if(preg_match_all("/::module\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v) self::get_desc($modules,$match,$k,$v[0],$src,$class);
		}		
		$properties = array();
		$ref = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$class));
		$d = '';
		while(true){
			$d = $ref->getDocComment().$d;
			if(($ref = $ref->getParentClass()) === false) break;
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$d));
		$anon = \org\rhaco\Object::anon_decode($d,'var',$r->getNamespaceName(),'summary');
		foreach($r->getProperties() as $prop){
			if(!$prop->isPrivate()){
				$name = $prop->getName();
				if($name[0] != '_' && !$prop->isStatic()){
					$properties[$name] = array(
											(isset($anon[$name]['type']) ? self::type($anon[$name]['type'],$class) : 'mixed')
											,(isset($anon[$name]['summary']) ? $anon[$name]['summary'] : null)
											,!(isset($anon[$name]['hash']) && $anon[$name]['hash'] === false)
										);
				}
			}
		}
		$description = trim(preg_replace('/@.+/','',$document));
		ksort($static_methods[0]);
		ksort($methods[0]);
		ksort($protected_methods[0]);
		ksort($protected_static_methods[0]);
		ksort($static_methods[1]);
		ksort($methods[1]);
		ksort($protected_methods[1]);
		ksort($protected_static_methods[1]);
		ksort($properties);
		ksort($modules);
		return array(
				'filename'=>$r->getFileName(),'extends'=>$extends,'abstract'=>$r->isAbstract(),'version'=>date('Ymd',$updated)
				,'static_methods'=>$static_methods[0],'methods'=>$methods[0],'protected_static_methods'=>$protected_static_methods[0],'protected_methods'=>$protected_methods[0]
				,'inherited_static_methods'=>$static_methods[1],'inherited_methods'=>$methods[1],'inherited_protected_static_methods'=>$protected_static_methods[1],'inherited_protected_methods'=>$protected_methods[1]
				,'module_method'=>$module_method
				,'properties'=>$properties,'tasks'=>$tasks,'package'=>$class,'description'=>$description
				,'modules'=>$modules
				);
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 */
	static public function method_info($class,$method){
		$ref = new \ReflectionMethod('\\'.str_replace(array('.','/'),array('\\','\\'),$class),$method);
		$params = $return = $modules = $see = $request = $context = $args = $throws =array();
		$document = $src = null;
		$deprecated = false;
		
		if(is_file($ref->getDeclaringClass()->getFileName())){
			$src = implode(array_slice(file($ref->getDeclaringClass()->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
			$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$ref->getDocComment())));
			$deprecated = (strpos($ref->getDocComment(),'@deprecated') !== false);
			
			if(preg_match("/@return\s+([^\s]+)(.*)/",$document,$match)){
				// type, summary
				$return = array(self::type(trim($match[1]),$class),trim($match[2]));
			}
			foreach($ref->getParameters() as $p){
				$params[$p->getName()] = array(
								// type, is_ref, has_default, default, summary
								'mixed'
								,$p->isPassedByReference()
								,$p->isDefaultValueAvailable()
								,($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null)
								,null
							);
			}
			if(preg_match_all("/@param\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					if(isset($params[$match[2][$k]])){
						$params[$match[2][$k]][0] = self::type($match[1][$k],$class);
						$params[$match[2][$k]][4] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					}
				}
			}
			if(preg_match_all('/->in_vars\((["\'])(.+?)\\1/',$src,$match)){
				foreach($match[2] as $n) $request[$n] = $context[$n] = array("mixed",null);
			}
			if(preg_match_all('/\$this->rm_vars\((["\'])(.+?)\\1/',$src,$match)){
				foreach($match[2] as $n){
					if(isset($context[$n])) unset($context[$n]);
				}
			}
			if(strpos($src,'$this->rm_vars()') !== false){
				$context = array();
			}
			if(preg_match_all('/\$this->vars\((["\'])(.+?)\\1/',$src,$match)){				
				foreach($match[2] as $n) $context[$n] = array("mixed",null);
			}
			if(preg_match_all("/@request\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					if(isset($request[$match[2][$k]])){
						$request[$match[2][$k]][0] = self::type($match[1][$k],$class);
						$request[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					}
					if(isset($context[$match[2][$k]])){
						$context[$match[2][$k]][0] = self::type($match[1][$k],$class);
						$context[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					}
				}
			}
			if(preg_match_all("/@context\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					$context[$match[2][$k]][0] = self::type($match[1][$k],$class);
					$context[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
			if(preg_match_all('/\$this->(map_arg|redirect_by_map)\((["\'])(.+?)\\2/',$src,$match)){
				foreach($match[3] as $n) $args[$n] = "";
			}
			if(preg_match_all("/@arg\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					if(isset($args[$match[2][$k]])){
						$args[$match[2][$k]] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					}
				}
			}			
			if(preg_match_all("/throw\s+new\s+([\\\\\w]+)\(([\"\'])(.+)\\2/",$src,$match)){
				foreach($match[1] as $k => $n) $throws[md5($n.$match[3][$k])] = array($n,trim($match[3][$k]));
			}
			if(preg_match_all("/@throws\s+([^\s]+)(.*)/",$document,$match)){
				foreach($match[1] as $k => $n) $throws[md5($n.$match[2][$k])] = array($n,trim($match[2][$k]));
			}
			ksort($throws);
	
			if(preg_match_all("/@module\s+([\w\.\\\\]+)/",$document,$match)){
				foreach($match[1] as $v) $modules[trim($v)] = true;
			}
			$modules = array_keys($modules);
			sort($modules);
			
			if(preg_match_all("/@see\s+([\w\.\:\\\\]+)/",$document,$match)){
				foreach($match[1] as $v){
					$class = $v = trim($v);
					$method = null;
					if(strpos($v,'::') !== false){
						list($class,$method) = explode('::',$v,2);
					}
					$see[$v] = array($class,$method);
				}
			}
			ksort($see);
		}
		$description = trim(preg_replace('/@.+/','',$document));
		return array(
				'package'=>$class,'method_name'=>$method,'params'=>$params,'request'=>$request,'context'=>$context
				,'args'=>$args,'return'=>$return,'description'=>$description,'throws'=>$throws
				,'is_post'=>((strpos($src,'$this->is_post()') !== false) && (strpos($src,'!$this->is_post()') === false))
				,'deprecated'=>$deprecated,'modules'=>$modules,'see'=>$see
				);
	}
	/**
	 * ライブラリ一覧
	 * @return array
	 */
	static public function classes(){
		$libdir = \org\rhaco\Conf::libdir();
		$result = array();
		if(!empty($libdir) && is_dir($libdir)){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($libdir,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS),\RecursiveIteratorIterator::SELF_FIRST) as $e){
				if(strpos($e->getPathname(),'/.') === false){
					if(ctype_upper(substr($e->getFilename(),0,1)) && substr($e->getFilename(),-4) == '.php'
						&& (strpos($e->getPathname(),'/_') === false || strpos($e->getPathname(),'/_vendor') !== false)
					){
						try{
							include_once($e->getPathname());
						}catch(\Exeption $ex){}
					}else if($e->getFilename() == 'vendor.phar'){
						$p = new \Phar($e->getPathname());
						foreach(new \RecursiveIteratorIterator($p) as $v){
							if(ctype_upper(substr($v->getFilename(),0,1)) && substr($v->getFilename(),-4) == '.php'){
								try{
									include_once($v->getPathname());
								}catch(\Exeption $ex){}
							}
						}
					}
				}
			}
			foreach(get_declared_classes() as $class){
				$r = new \ReflectionClass($class);
				if(!$r->isInterface() && preg_match("/(.*)\\\\[A-Z][^\\\\]+$/",$class,$m) && preg_match("/^[^A-Z]+$/",$m[1])){
					$f = null;
					$d = false;
					$n = str_replace('\\','/',$r->getName());
					$p = $libdir.$n;
					if(is_file($f=$p.'.php')){
					}else if(is_file($f=$p.'/'.basename($p).'.php')){
						$d = true;
					}else{
						$p = $libdir.'_vendor/'.$n;
						if(is_file($f=$p.'.php')){
						}else if(is_file($f=$p.'/'.basename($p).'.php')){
							$d = true;
						}
					}
					if(is_file($f)){
						$result[str_replace('/','.',$n)] = array('filename'=>$f,'dir'=>$d,'class'=>'\\'.$class);
					}
				}
			}
		}
		return $result;
	}
	static private function type($type,$class){
		if($type == 'self' || $type == '$this') $type = $class;
		$type = str_replace('\\','.',$type);
		if(substr($type,0,1) == '.') $type = substr($type,1);
		return $type;
	}
	static private function	get_desc(&$modules,$match,$k,$name,$src,$class){
		if(!isset($modules[$name])) $modules[$name] = array(null,array(),array());
		$doc = substr($src,0,$match[0][$k][1]);
		$doc = trim(substr($doc,0,strrpos($doc,"\n")));
		if(substr($doc,-2) == '*'.'/'){
			$doc = substr($doc,strrpos($doc,'/'.'**'));
			$doc = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$doc)));
			if(preg_match_all("/@param\s+([^\s]+)\s+\\$(\w+)(.*)/",$doc,$m)){
				foreach($m[2] as $n => $p) $modules[$name][1][$m[2][$n]] = array($m[2][$n],self::type($m[1][$n],$class),trim($m[3][$n]));
			}
			if(preg_match("/@return\s+([^\s]+)(.*)/",$doc,$m)){
				$modules[$name][2] = array(self::type(trim($m[1]),$class),trim($m[2]));
			}
			$modules[$name][0] = trim(preg_replace('/@.+/','',$doc));
		}
		return $modules;
	}
}
