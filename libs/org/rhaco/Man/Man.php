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
		$src = implode(array_slice(file($r->getFileName()),$r->getStartLine(),($r->getEndLine()-$r->getStartLine()-1)));
		$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$r->getDocComment())));
		$extends = ($r->getParentClass() === false) ? null : $r->getParentClass()->getName();

		$methods = $static_methods = array();
		foreach($r->getMethods() as $method){
			if($method->getDeclaringClass()->getFileName() == $r->getFileName()){
				if(substr($method->getName(),0,1) != '_' && $method->isPublic()){
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
					if($method->isStatic()){
						if($method->getDeclaringClass()->getName() == $r->getName()){
							$static_methods[$method->getName()] = $method_description;
						}
					}else{
						$methods[$method->getName()] = $method_description;
					}
				}
			}
		}
		$tasks = array();
		if(preg_match_all("/TODO[\040\t](.+)/",$src,$match)){
			foreach($match[1] as $t) $tasks[] = trim($t);
		}
		
		// TODO 
		$modules = array();
		if(preg_match_all("/->object_module\(([\"\'])(.+)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $v){
				$name = $v[0];
				$doc = substr($src,0,$v[1]);
				$doc_end = strrpos($doc,'*'.'/');
				if($doc_end !== false && substr_count(substr($src,$doc_end,$v[1]-$doc_end),"\n") === 1){
					$doc_start = strrpos($doc,'/'.'**');
					$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",substr($doc,$doc_start,$doc_end-$doc_start+2))));
					if(preg_match_all("/@param\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){

					}
				}
			}
		}
		if(preg_match_all("/::module\(([\"\'])(.+)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[1] as $t) $modules[] = trim($t);
		}
		
		
		
		$properties = array();
		$ref = new \ReflectionClass('\\'.str_replace(array('.','/'),array('\\','\\'),$class));
		$d = '';
		while(true){
			$d = $ref->getDocComment().$d;
			if(($ref = $ref->getParentClass()) === false) break;
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$d));
		foreach($r->getProperties() as $prop){
			if(!$prop->isPrivate()){
				$name = $prop->getName();
				if($name[0] != '_' && !$prop->isStatic()){
					$properties[$name] = array('mixed',null,null);
				}
			}
		}
		if(preg_match_all("/@var\s([\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$d,$m)){
			foreach($m[2] as $k => $n){
				if(isset($properties[$n])){
					$dec = preg_replace('/^(.*?)@.*$/','\\1',$m[3][$k]);
					$anon = json_decode(preg_replace('/^.*?@(.*)$/','\\1',$m[3][$k]),true);
					$hash = !(isset($anon['hash']) && $anon['hash'] === false);
					$properties[$n] = array($m[1][$k],$dec,$hash);
				}
			}
		}
		$description = trim(preg_replace('/@.+/','',$document));
		return array(
				'filename'=>$r->getFileName(),'extends'=>$extends,'static_methods'=>$static_methods,'methods'=>$methods
				,'properties'=>$properties,'tasks'=>$tasks,'package'=>$class,'description'=>$description
				);
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 */
	static public function method_info($class,$method){
		$ref = new \ReflectionMethod('\\'.str_replace(array('.','/'),array('\\','\\'),$class),$method);
		$src = implode(array_slice(file($ref->getDeclaringClass()->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
		$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."**","*"."/"),"",$ref->getDocComment())));
		$params = array();
		$return = array();
		
		if(preg_match("/@return\s+([^\s]+)(.*)/",$document,$match)){
			// type, summary
			$return = array(trim($match[1]),trim($match[2]));
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
					$params[$match[2][$k]][0] = $match[1][$k];
					$params[$match[2][$k]][4] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
		}
		$request = $context = array();
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
					$request[$match[2][$k]][0] = $match[1][$k];
					$request[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
				if(isset($context[$match[2][$k]])){
					$context[$match[2][$k]][0] = $match[1][$k];
					$context[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
		}
		if(preg_match_all("/@context\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
			foreach($match[0] as $k => $v){
				if(isset($context[$match[2][$k]])){
					$context[$match[2][$k]][0] = $match[1][$k];
					$context[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
		}
		$args = array();
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
		$description = trim(preg_replace('/@.+/','',$document));
		return array(
				'package'=>$class,'method_name'=>$method,'params'=>$params,'request'=>$request,'context'=>$context
				,'args'=>$args,'return'=>$return,'description'=>$description
				,'is_post'=>(strpos($src,'$this->is_post()') !== false)
				);
	}
	/**
	 * ライブラリ一覧
	 * @return array
	 */
	static public function libs(){
		$result = array();
		if(is_dir(\Rhaco3::libs())){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\Rhaco3::libs(),\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST) as $e){
				if(strpos($e->getPathname(),'/.') === false){
					if(ctype_upper(substr($e->getFilename(),0,1)) && substr($e->getFilename(),-4) == '.php'
						&& (strpos($e->getPathname(),'/_') === false || strpos($e->getPathname(),'/_vendors') !== false)
					){
						try{
							include_once($e->getPathname());
						}catch(\Exeption $ex){}
					}else if($e->getFilename() == 'vendors.phar'){
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
				if((!$r->isInterface() && !$r->isAbstract()) && preg_match("/(.*)\\\\[A-Z][^\\\\]+$/",$class,$m) && preg_match("/^[^A-Z]+$/",$m[1])){
					$f = null;
					$d = false;
					$n = str_replace('\\','/',$r->getName());
					$p = \Rhaco3::libs($n);
					if(is_file($f=$p.'.php')){
					}else if(is_file($f=$p.'/'.basename($p).'.php')){
						$d = true;
					}else{
						$p = \Rhaco3::libs('_vendors/'.$n);
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
}
