<?php
namespace org\rhaco\flow\parts\Developer;

class Helper{
	public function basename($p){
		return basename(str_replace("\\",'/',$p));
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
			$master = $this->prop_anon($name,'master');
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				if($master[0] !== "\\") $master = "\\".$master;
				$r = new \ReflectionClass($master);
				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				foreach($master::find(Q::eq(key($primarys),$obj->{$name}())) as $dao){
					$options[] = sprintf('<option value="%s">%s</option>',$id,$dao->str());
				}
			}			
			return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
		}else if($obj->anon($name,'save',true)){
			switch($obj->prop_anon($name,'type')){
				case 'serial': return sprintf('<input name="%s" type="hidden" value="%s" /><spn class="hidden">&nbsp;%s</span>',$name,$obj->{$name}(),$obj->{$name}());
				case 'text': return sprintf('<textarea name="%s">%s</textarea>',$name,$obj->{$name}());
				case 'boolean':
					$options = array();
					if(!$object->a($name,"require")) $options[] = '<option value=""></option>';
					foreach(array('true','false') as $choice) $options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					return sprintf('<select name="%s">%s</select>',$name,implode("",$options));
				case 'choice':
					$options = array();
					if(!$obj->prop_anon($name,'require')) $options[] = '<option value=""></option>';
					foreach($obj->prop_anon($name,'choices') as $choice) $options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
				default:
					$value = $obj->{$name}();
					if(!empty($value)){
						switch($obj->prop_anon($name,'type')){
							case 'timestamp':
								$value = empty($value) ? null : date('Y/m/d H:i:s',$value);
								break;
							case 'date':
								$value = empty($value) ? null : date('Y/m/d',$value);
								break;
							case 'time':
								if(!empty($value)){
									$h = floor($value / 3600);
									$i = floor(($value - ($h * 3600)) / 60);
									$s = (int)($value - ($h * 3600) - ($i * 60));
									$m = str_replace('0.','',$value - ($h * 3600) - ($i * 60) - $s);
									$value = (($h == 0) ? '' : $h.':').(($i == 0) ? '' : sprintf('%02d',$i).':').sprintf("%02d",$s).(($m == 0) ? '' : '.'.$m);
								}
								break;
						}
					}
					return sprintf('<input name="%s" type="text" value="%s" />',$name,$value);
			}
		}
	}
	public function pre($value){
		$value = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value);
		$value = preg_replace("/!!!(.+?)!!!/ms","<span class=\"notice\">\\1</span>",$value);
		$value = str_replace("\t","&nbsp;&nbsp;",$value);
		return $value;
	}
	public function htmlspecialchars($src){
		return htmlspecialchars($src);
	}
}
