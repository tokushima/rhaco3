<?php
namespace org\rhaco\Dt;

class Helper{
	private $html_replace_prefix;
	private $html_replace_map_url;
	
	public function set_html_replace_var($map_url,$prefix=null){
		$this->html_replace_map_url = $map_url;
		$this->html_replace_prefix = $prefix;
	}
	public function package_name($p){
		$p = str_replace(array('/','\\'),array('.','.'),$p);
		if(substr($p,0,1) == '.') $p = substr($p,1);
		return $p;
	}
	public function type($class){
		if(preg_match('/[A-Z]/',$class)){
			switch(substr($class,-2)){
				case "{}":
				case "[]": $class = substr($class,0,-2);
			}
			$class = str_replace('\\','.',$class);
			if(substr($class,0,1) == '.') $class = substr($class,1);
			return $class;
		}
		return null;
	}
	/**
	 * アクセサ
	 * @param Dao $obj
	 * @param string $prop_name
	 * @param string $ac
	 */
	public function acr(\org\rhaco\store\db\Dao $obj,$prop_name,$ac='fm'){
		return $obj->{$ac.'_'.$prop_name}();
	}
	/**
	 * プロパティ一覧
	 * @param Dao $obj
	 * @param integer $len 表示数
	 */
	public function props(\org\rhaco\store\db\Dao $obj,$len=null){
		$result = array();
		$i = 0;
		$r = new \ReflectionClass($obj);
		foreach($r->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $p){
			if(!$p->isStatic() && $obj->prop_anon($p->getName(),'extra') !== true && $obj->prop_anon($p->getName(),'cond') === null && substr($p->getName(),0,1) != '_'){
				if($len !== null && $len < $i) break;
				$result[] = $p->getName();
				$i++;
			}
		}
		return $result;
	}
	public function primary_query(\org\rhaco\store\db\Dao $obj){
		$result = array();
		foreach($this->props($obj) as $prop){
			if($obj->prop_anon($prop,'primary') === true && $obj->prop_anon($prop,'extra') !== true && $obj->prop_anon($prop,'cond') === null){
				$result[] = "primary[".$prop."]=".$obj->{$prop}();
			}
		}
		return implode("&",$result);
	}
	public function primary_hidden(\org\rhaco\store\db\Dao $obj){
		$result = array();
		foreach($this->props($obj) as $prop){
			if($obj->prop_anon($prop,'primary') === true && $obj->prop_anon($prop,'extra') !== true && $obj->prop_anon($prop,'cond') === null){
				$result[] = '<input type="hidden" name="primary['.$prop.']" value="'.$obj->{$prop}().'" />';
			}
		}
		return implode("&",$result);
	}
	public function is_primary($obj,$name){
		return $obj->prop_anon($name,'primary');
	}
	public function form(\org\rhaco\store\db\Dao $obj,$name){
		if(method_exists($obj,'form_'.$name)){
			return $obj->{'form_'.$name}();
		}else if($obj->prop_anon($name,'master') !== null){
			$options = array();
			if(!$obj->prop_anon($name,'require')) $options[] = '<option value=""></option>';
			$master = $obj->prop_anon($name,'master');
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				if($master[0] !== "\\") $master = "\\".$master;

				try{
					$r = new \ReflectionClass($master);
				}catch(\ReflectionException $e){
					$self = new \ReflectionClass(get_class($obj));
					$r = new \ReflectionClass("\\".$self->getNamespaceName().$master);
				}
				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				if(sizeof($primarys) != 1) return sprintf('<input name="%s" type="text" />',$name);
				foreach($primarys as $primary) break;
				$pri = $primary->name();
				foreach(call_user_func_array(array($mo,'find'),array()) as $dao){
					$options[] = sprintf('<option value="%s">%s</option>',$dao->{$pri}(),(string)$dao);
				}
			}			
			return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
		}else if($obj->anon($name,'save',true)){
			switch($obj->prop_anon($name,'type')){
				case 'serial': return sprintf('<input name="%s" type="text" disabled="disabled" /><input name="%s" type="hidden" />',$name,$name);
				case 'text': return sprintf('<textarea name="%s"></textarea>',$name);
				case 'boolean':
					$options = array();
					if(!$obj->prop_anon($name,'require')) $options[] = '<option value=""></option>';
					foreach(array('true','false') as $choice) $options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
				case 'choice':
					$options = array();
					if(!$obj->prop_anon($name,'require')) $options[] = '<option value=""></option>';
					foreach($obj->prop_anon($name,'choices') as $v) $options[] = sprintf('<option value="%s">%s</option>',$v,$v);
					return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
				default:
					return sprintf('<input name="%s" type="text" format="%s" />',$name,$obj->prop_anon($name,'type'));
			}
		}
	}
	public function filter(\org\rhaco\store\db\Dao $obj,$name){
		if($obj->prop_anon($name,'master') !== null){
			$options = array();
			$options[] = '<option value=""></option>';
			$master = $obj->prop_anon($name,'master');
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				if($master[0] !== "\\") $master = "\\".$master;
				$r = new \ReflectionClass($master);
				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				if(sizeof($primarys) != 1) return sprintf('<input name="%s" type="text" />',$name);
				foreach($primarys as $primary) break;
				$pri = $primary->name();
				foreach($master::find() as $dao){
					$options[] = sprintf('<option value="%s">%s</option>',$dao->{$pri}(),(string)$dao);
				}
			}			
			return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
		}else{
			$type = $obj->prop_anon($name,'type');
			switch($type){
				case 'boolean':
					$options = array();
					$options[] = '<option value=""></option>';
					foreach(array('true','false') as $choice) $options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					return sprintf('<select name="search_%s_%s">%s</select>',$type,$name,implode('',$options));
				case 'choice':
					$options = array();
					$options[] = '<option value=""></option>';
					foreach($obj->prop_anon($name,'choices') as $v) $options[] = sprintf('<option value="%s">%s</option>',$v,$v);
					return sprintf('<select name="search_%s_%s">%s</select>',$type,$name,implode('',$options));
				case 'timestamp':
				case 'date':
					return sprintf('<input name="search_%s_from_%s" type="text" class="span2" />',$type,$name).' : '.sprintf('<input name="search_%s_to_%s" type="text" class="span2" />',$type,$name);
				default:
					return sprintf('<input name="search_%s_%s" type="text"　/>',$type,$name);
			}
		}
	}
	public function htmlspecialchars($src){
		return htmlspecialchars($src);
	}
	
	/**
	 * export用ダミー
	 * @param string $package
	 * @param string $method
	 * @return string
	 */
	public function method_html_filename($package,$method){
		$html = $package.'__'.$method.'.html';
		return (empty($this->html_replace_map_url)) ? $html : sprintf('{$t.map_url(\'%s\',\'%s%s\')}',$this->html_replace_map_url,$this->html_replace_prefix,$html);
	}
	/**
	 * export用ダミー
	 * @param string $package
	 * @param string $method
	 * @return string
	 */
	public function module_html_filename($package,$method){
		$html = $package.'___'.$method.'.html';
		return (empty($this->html_replace_map_url)) ? $html : sprintf('{$t.map_url(\'%s\',\'%s%s\')}',$this->html_replace_map_url,$this->html_replace_prefix,$html);
	}
	/**
	 * export用ダミー
	 * @param string $package
	 * @param string $method
	 * @return string
	 */
	public function class_html_filename($package){
		$html = $package.'.html';
		return (empty($this->html_replace_map_url)) ? $html : sprintf('{$t.map_url(\'%s\',\'%s%s\')}',$this->html_replace_map_url,$this->html_replace_prefix,$html);
	}
	
	/**
	 * ダミー
	 * @param string $path
	 */
	public function docimg($path){
	}
}
