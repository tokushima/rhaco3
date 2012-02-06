<?php
namespace org\rhaco\flow\parts\Developer;

class Helper{
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
		}else if($obj->anon($name,'save',true)){
			switch($obj->prop_anon($name,'type')){
				case 'serial': return sprintf('<input name="%s" type="hidden"　/><spn class="hidden">&nbsp;{$%s}</span>',$name,$name);
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
	public function htmlspecialchars($src){
		return htmlspecialchars($src);
	}
}
