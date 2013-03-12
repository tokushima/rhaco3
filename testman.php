<?php
namespace testman{
	// common
	class Coverage{
		static private $base_dir;
		static private $target_dir = array();
		static private $start = false;
		static private $savedb;
	
		static public function has_started(&$vars){
			if(self::$start){
				$vars = array();
				$vars['savedb'] = self::$savedb;
				$vars['base_dir'] = self::$base_dir;
				$vars['target_dir'] = self::$target_dir;
				$vars['current_name'] = \testman\TestRunner::current_name();
					
				return true;
			}
			return false;
		}
		static public function start($savedb,$base_dir,$target_dir){
			if(extension_loaded('xdebug') && self::$start === false){
				xdebug_start_code_coverage();
				self::$start = true;
				self::$savedb = $savedb;
				$exist = (is_file(self::$savedb));
	
				if(!empty($target_dir) && !is_array($target_dir)) $target_dir = array($target_dir);
				self::$target_dir = empty($target_dir) ? array() : $target_dir;
				self::$base_dir = str_replace('\\','/',$base_dir);
				if(substr(self::$base_dir,-1) != '/') self::$base_dir = self::$base_dir.'/';
				
				if($db = new \PDO('sqlite:'.self::$savedb)){
					if(!$exist){
						$sql = 'create table coverage('.
								'id integer not null primary key,'.
								'parent_path text,'.
								'src text,'.
								'file_path text not null,'.
								'covered_line text not null,'.
								'ignore_line text,'.
								'covered_len integer,'.
								'active_len integer,'.
								'file_len integer,'.
								'percent integer'.
								')';
						if(false === $db->query($sql)) throw new \RuntimeException('failure create coverage table');
							
						$sql = 'create table coverage_info('.
								'id integer not null primary key,'.
								'create_date text,'.
								'test_path text,'.
								'result text'.
								')';
						if(false === $db->query($sql)) throw new \RuntimeException('failure create coverage_info table');
	
						$sql = 'create table coverage_covered('.
								'id integer not null primary key,'.
								'test_path text,'.
								'covered_line text,'.
								'file_path text'.
								')';
						if(false === $db->query($sql)) throw new \RuntimeException('failure create coverage_covered table');
							
						$sql = 'create table coverage_tree('.
								'id integer not null primary key,'.
								'parent_path text not null,'.
								'path text not null'.
								')';
						if(false === $db->query($sql)) throw new \RuntimeException('failure create coverage_tree table');
							
						$sql = 'create table coverage_tree_root('.
								'path text not null'.
								')';
						if(false === $db->query($sql)) throw new \RuntimeException('failure create coverage_tree_root table');
							
							
						$sql = 'insert into coverage_info(create_date) values(?)';
						$ps = $db->prepare($sql);
						$ps->execute(array(time()));
							
						$sql = 'insert into coverage_tree_root(path) values(?)';
						$ps = $db->prepare($sql);
						foreach(self::$target_dir as $path){
							$path = str_replace('\\','/',$path);
							if(substr($path,-1) == '/') $path = substr($path,0,-1);
							$ps->execute(array(basename($path)));
						}
					}
					register_shutdown_function(array(__CLASS__,'stop'));
				}
			}
		}
		static public function save($restart=true){
			if(self::$start){
				if($db = new \PDO('sqlite:'.self::$savedb)){
					$db->beginTransaction();
	
					$get_prepare = function($db,$sql){
						$ps = $db->prepare($sql);
						if($ps === false) throw new \LogicException($sql);
						return $ps;
					};
	
					$insert_ps = $get_prepare($db,'insert into coverage(file_path,covered_line,file_len,covered_len,src) values(?,?,?,?,?)');
					$getid_ps = $get_prepare($db,'select id,covered_line from coverage where file_path=?');
					$update_ps = $get_prepare($db,'update coverage set covered_line=?,covered_len=? where id=?');
					$insert_exe_ps = $get_prepare($db,'insert into coverage_covered(file_path,covered_line,test_path) values(?,?,?)');
	
					foreach(xdebug_get_code_coverage() as $file_path => $lines){
						if(
								strpos($file_path,'phar://') !== 0 &&
								strpos($file_path,'/_') === false &&
								is_file($file_path)
						){
							$bool = false;
	
							if(empty(self::$target_dir)){
								$bool = true;
							}else{
								foreach(self::$target_dir as $dir){
									if(strpos($file_path,$dir) === 0){
										$bool = true;
										break;
									}
								}
							}
							if($bool){
								$p = str_replace(self::$base_dir,'',$file_path);
	
								$pre_id = $pre_line = null;
								$getid_ps->execute(array($p));
								while($resultset = $getid_ps->fetch(\PDO::FETCH_ASSOC)){
									$pre_id = $resultset['id'];
									$pre_line = $resultset['covered_line'];
								}
								if(!isset($pre_id)){
									$insert_ps->execute(array(
											$p,
											json_encode(array_keys($lines)),
											sizeof(file($file_path)),
											sizeof($lines),
											file_get_contents($file_path)
									));
								}else{
									$line_array = array_flip(json_decode($pre_line,true));
									foreach($lines as $k => $v) $line_array[$k] = $k;
									$covered_line = array_keys($line_array);
	
									$update_ps->execute(array(
											json_encode($covered_line),
											sizeof($covered_line),
											$pre_id
									));
								}
								$insert_exe_ps->execute(array(
										$p,
										implode(',',array_keys($lines)),
										\testman\Testrunner::current_name()
								));
							}
						}
					}
					$db->commit();
	
					xdebug_stop_code_coverage();
					self::$start = false;
	
					if($restart){
						xdebug_start_code_coverage();
						self::$start = true;
					}
				}
			}
		}
		/**
		 * @param string $src
		 * @return array($active_count,$ignore_line,$src,count)
		 */
		static public function parse_line($src){
			if(empty($src)) return array(0,array(),0);
			$ignore_line = array();
	
			$ignore_line_func = function($c0,$c1,$src){
				$s = substr_count(substr($src,0,$c1),PHP_EOL);
				$e = substr_count($c0,PHP_EOL);
				return range($s+1,$s+1+$e);
			};
			$parse = function($src,&$ignore_line,$preg_pattern) use($ignore_line_func){
				if(preg_match_all($preg_pattern,$src,$m,PREG_OFFSET_CAPTURE)){
					foreach($m[1] as $c){
						$ignore_line = array_merge($ignore_line,$ignore_line_func($c[0],$c[1],$src));
					}
				}
			};
			$parse($src,$ignore_line,"/(\/\*.*?\*\/)/ms");
			$parse($src,$ignore_line,"/^((namespace|use|class)[\040\t].+)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*(final|static|protected|private|public|const)[\040\t].+)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*\/\/.+)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*#.+)$/m");
			$parse($src,$ignore_line,"/^([\s]*<\?php[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*\?>[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*try[\040\t]*\{[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t\}]*catch[\040\t]*\(.+\).+)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*switch[\040\t]*\(.+\).+)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*\}[\040\t]*else[\040\t]*\{[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*\{[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t]*\}[\040\t]*)$/m");
			$parse($src,$ignore_line,"/^([\040\t\(\)]+)$/m");
			$parse($src,$ignore_line,"/^([\s]*)$/ms");
			$parse($src,$ignore_line,"/(\n)$/s");
	
			$ignore_line = array_unique($ignore_line);
			sort($ignore_line);
			$src_count = substr_count($src,PHP_EOL) + 1;
			return array(($src_count-sizeof($ignore_line)),$ignore_line,$src_count);
		}
		static public function stop(){
			self::save(false);
			$dirlist = array();
	
			if(is_file(self::$savedb) && ($db = new \PDO('sqlite:'.self::$savedb))){
				$sql = 'select file_path,id,src,active_len,covered_line,covered_len from coverage order by file_path';
				$ps = $db->query($sql);
					
				$update_sql = 'update coverage set parent_path=?,active_len=?,ignore_line=?,covered_line=?,covered_len=?,percent=? where id=?';
				$update_ps = $db->prepare($update_sql);
				if($update_ps === false) throw new \LogicException($update_sql);
					
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$percent = 0;
					$dir = dirname($resultset['file_path']);
					list($active_len,$ignore_line,$src_count) = self::parse_line($resultset['src']);
					$covered_lines = array_unique(json_decode($resultset['covered_line'],true));
					foreach($covered_lines as $k => $v){
						if($v === 0 || $v > $src_count) unset($covered_lines[$k]);
					}
					sort($covered_lines);
	
					$covered_dup = sizeof(array_intersect($covered_lines,$ignore_line));
					$covered_len = sizeof($covered_lines) - $covered_dup;
					$percent = ($active_len === 0) ? 100 : (($covered_len === 0) ? 0 : (floor($covered_len / $active_len * 100)));
	
					$update_ps->execute(array($dir,$active_len,json_encode($ignore_line),json_encode($covered_lines),$covered_len,(int)$percent,$resultset['id']));
	
					while(strpos($dir,'/') !== false){
						$dirlist[$dir] = dirname($dir);
						$dir = dirname($dir);
					}
				}
				$cnt_sql = 'select count(path) as cnt from coverage_tree where parent_path=? and path=?';
				$cnt_ps = $db->prepare($cnt_sql);
				if($cnt_ps === false) throw new \LogicException($cnt_sql);
	
				$insert_sql = 'insert into coverage_tree(parent_path,path) values(?,?)';
				$insert_ps = $db->prepare($insert_sql);
				if($insert_ps === false) throw new \LogicException($insert_sql);
	
				foreach($dirlist as $dir => $parent_dir){
					$cnt_ps->execute(array($parent_dir,$dir));
					$resultset = $cnt_ps->fetch(\PDO::FETCH_ASSOC);
					if((int)$resultset['cnt'] === 0){
						$insert_ps->execute(array($parent_dir,$dir));
					}
				}
				$sql = 'update coverage_info set result=?, test_path=?';
				$ps = $db->prepare($sql);
				$ps->execute(array(
						json_encode(\testman\TestRunner::get()),
						json_encode(\testman\TestRunner::get_dir())
				));
			}
		}
		static public function test_result($savedb){
			if(is_file($savedb) && ($db = new \PDO('sqlite:'.$savedb))){
				$sql = 'select result,test_path from coverage_info';
				$ps = $db->prepare($sql);
				$ps->execute();
	
				$success = $fail = $none = 0;
				$failure = array();
					
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$result = json_decode($resultset['result'],true);
					$test_path = json_decode($resultset['test_path'],true);
					if(!is_array($result)) $result = array();
					if(!is_array($test_path)) $test_path = array();
					rsort($test_path);
	
					foreach($result as $file => $f){
						foreach($f as $class => $c){
							foreach($c as $method => $m){
								foreach($m as $line => $r){
									foreach($r as $l){
										$info = array_shift($l);
										foreach($test_path as $p) $file = str_replace(dirname($p),'',$file);
										$name_var = array('class'=>$class,'file'=>$file,'method'=>$method,'line'=>$line);
											
										switch(sizeof($l)){
											case 0: // success
												$success++;
												break;
											case 1: // none
												$none++;
												break;
											case 2: // fail
												$fail++;
												$result_a = $result_b = null;
													
												ob_start();
												var_dump($l[0]);
												$result_a .= ob_get_contents();
												ob_end_clean();
	
												ob_start();
												var_dump($l[1]);
												$result_b .= ob_get_contents();
												ob_end_clean();
													
												$failure[] = array('location'=>$name_var,'expected'=>$result_a,'actual'=>$result_b);
												break;
											case 4: // exception
												$fail++;
												$failure[] = array('location'=>$name_var,'expected'=>$l[0],'actual'=>$l[2].':'.$l[3]);
												break;
										}
									}
								}
							}
						}
					}
					return array($success,$fail,$none,$failure);
				}
			}
			return array(0,0,0,array());
		}
		static public function dir_list($savedb,$dir=null){
			$result_dir = $result_file = $avg = array();
			$avg = array('avg'=>0,'uncovered'=>100,'covered'=>0);
			$parent_path = null;
	
			if(is_file($savedb) && ($db = new \PDO('sqlite:'.$savedb))){
				if(empty($dir)){
					$sql = 'select path from coverage_tree_root order by path';
					$ps = $db->prepare($sql);
					$ps->execute();
	
					while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$result_dir[] = $resultset['path'];
					}
	
					$avg_sql = 'select avg(percent) as percent_avg from coverage';
					$avg_ps = $db->prepare($avg_sql);
					$avg_ps->execute();
	
					if($resultset = $avg_ps->fetch(\PDO::FETCH_ASSOC)){
						$avg['avg'] = floor($resultset['percent_avg']);
						$avg['uncovered'] = 100 - $resultset['percent_avg'];
						$avg['covered'] = 100 - $avg['uncovered'];
					}
				}else{
					$sql = 'select parent_path from coverage_tree where path=?';
					$ps = $db->prepare($sql);
					$ps->execute(array($dir));
					while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$parent_path = $resultset['parent_path'];
					}
	
					$sql = 'select path from coverage_tree where parent_path=? order by path';
					$ps = $db->prepare($sql);
					$ps->execute(array($dir));
					while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$result_dir[] = $resultset['path'];
					}
	
					$sql = 'select file_path,file_len,covered_len,active_len,percent from coverage where parent_path=? order by file_path';
					$ps = $db->prepare($sql);
					$ps->execute(array($dir));
	
					while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$resultset['uncovered'] = 100 - $resultset['percent'];
						$resultset['covered'] = 100 - $resultset['uncovered'];
						$result_file[] = $resultset;
					}
	
					$avg_sql = 'select avg(percent) as percent_avg from coverage where file_path like(?)';
					$avg_ps = $db->prepare($avg_sql);
					$avg_ps->execute(array($dir.'/%'));
	
					if($resultset = $avg_ps->fetch(\PDO::FETCH_ASSOC)){
						$avg['avg'] = floor($resultset['percent_avg']);
						$avg['uncovered'] = 100 - $resultset['percent_avg'];
						$avg['covered'] = 100 - $avg['uncovered'];
					}
				}
			}
			return array($result_dir,$result_file,$parent_path,$avg);
		}
	
		static public function all_file_list($savedb){
			$result_file = array();
			$avg = array('avg'=>0,'uncovered'=>100,'covered'=>0);
	
			if(is_file($savedb) && ($db = new \PDO('sqlite:'.$savedb))){
				$sql = 'select file_path,file_len,covered_len,active_len,percent from coverage order by file_path';
				$ps = $db->prepare($sql);
				$ps->execute();
	
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$resultset['uncovered'] = 100 - $resultset['percent'];
					$resultset['covered'] = 100 - $resultset['uncovered'];
					$result_file[] = $resultset;
				}
					
				$sql = 'select avg(percent) as percent_avg from coverage';
				$ps = $db->prepare($sql);
				$ps->execute();
					
				if($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$avg['avg'] = floor($resultset['percent_avg']);
					$avg['uncovered'] = 100 - $resultset['percent_avg'];
					$avg['covered'] = 100 - $avg['uncovered'];
				}
			}
			return array($result_file,$avg);
		}
	
		static public function file($savedb,$file_path){
			$result = array();
	
			if(is_file($savedb) && ($db = new \PDO('sqlite:'.$savedb))){
				$covered_line = array();
				$sql = 'select test_path,covered_line from coverage_covered where file_path=?';
				$ps = $db->prepare($sql);
				if($ps === false) throw new \LogicException($sql);
				$ps->execute(array($file_path));
					
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					foreach(explode(',',$resultset['covered_line']) as $line){
						if(!isset($covered_line[$line])) $covered_line[$line] = array();
						$covered_line[$line][$resultset['test_path']] = $resultset['test_path'];
					}
				}
	
				$sql = 'select file_path,covered_line,file_len,covered_len,ignore_line,src from coverage where file_path=?';
				$ps = $db->prepare($sql);
				if($ps === false) throw new \LogicException($sql);
				$ps->execute(array($file_path));
					
				while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
					$src_lines = explode(PHP_EOL,$resultset['src']);
					$covered_lines = array_flip(json_decode($resultset['covered_line'],true));
					$ignore_lines = array_flip(json_decode($resultset['ignore_line'],true));
					$view_src_lines = array();
	
					foreach($src_lines as $k => $v){
						$line_num = $k + 1;
						$class = isset($ignore_lines[$line_num]) ? 'ignore' : (isset($covered_lines[$line_num]) ? 'covered' : 'uncovered');
						$test_path = ($class == 'ignore') ? array() : (isset($covered_line[$line_num]) ? $covered_line[$line_num] : array());
						$view_src_lines[$k] = array('value'=>$v,'class'=>$class,'test_path'=>$test_path);
					}
					$resultset['view'] = $view_src_lines;
					return $resultset;
				}
			}
			throw new \InvalidArgumentException($file_path.' not found');
		}
	}
	class Util{
		static public function path_absolute($a,$b){
			if($b === '' || $b === null) return $a;
			if($a === '' || $a === null || preg_match("/^[a-zA-Z]+:/",$b)) return $b;
			if(preg_match("/^[\w]+\:\/\/[^\/]+/",$a,$h)){
				$a = preg_replace("/^(.+?)[".(($b[0] === '#') ? '#' : "#\?")."].*$/","\\1",$a);
				if($b[0] == '#' || $b[0] == '?') return $a.$b;
				if(substr($a,-1) != '/') $b = (substr($b,0,2) == './') ? '.'.$b : (($b[0] != '.' && $b[0] != '/') ? '../'.$b : $b);
				if($b[0] == '/' && isset($h[0])) return $h[0].$b;
			}else if($b[0] == '/'){
				return $b;
			}
			$p = array(array('://','/./','//'),array('#R#','/','/'),array("/^\/(.+)$/","/^(\w):\/(.+)$/"),array("#T#\\1","\\1#W#\\2",''),array('#R#','#T#','#W#'),array('://','/',':/'));
			$a = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$a));
			$b = preg_replace($p[2],$p[3],str_replace($p[0],$p[1],$b));
			$d = $t = $r = '';
			if(strpos($a,'#R#')){
				list($r) = explode('/',$a,2);
				$a = substr($a,strlen($r));
				$b = str_replace('#T#','',$b);
			}
			$al = preg_split("/\//",$a,-1,PREG_SPLIT_NO_EMPTY);
			$bl = preg_split("/\//",$b,-1,PREG_SPLIT_NO_EMPTY);
	
			for($i=0;$i<sizeof($al)-substr_count($b,'../');$i++){
				if($al[$i] != '.' && $al[$i] != '..') $d .= $al[$i].'/';
			}
			for($i=0;$i<sizeof($bl);$i++){
				if($bl[$i] != '.' && $bl[$i] != '..') $t .= '/'.$bl[$i];
			}
			$t = (!empty($d)) ? substr($t,1) : $t;
			$d = (!empty($d) && $d[0] != '/' && substr($d,0,3) != '#T#' && !strpos($d,'#W#')) ? '/'.$d : $d;
			return str_replace($p[4],$p[5],$r.$d.$t);
		}
		static public function parse_args(){
			$params = array();
			$value = null;
			if(isset($_SERVER['REQUEST_METHOD'])){
				$params = isset($_GET) ? $_GET : array();
			}else{
				$argv = array_slice($_SERVER['argv'],1);
				$value = (empty($argv)) ? null : array_shift($argv);
				$params = array();
	
				if(substr($value,0,1) == '-'){
					array_unshift($argv,$value);
					$value = null;
				}
				for($i=0;$i<sizeof($argv);$i++){
					if($argv[$i][0] == '-'){
						$k = substr($argv[$i],1);
						$v = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
						if(isset($params[$k]) && !is_array($params[$k])) $params[$k] = array($params[$k]);
						$params[$k] = (isset($params[$k])) ? array_merge($params[$k],array($v)) : $v;
					}
				}
			}
			return array($value,$params);
		}
	}
	class XmlIterator implements \Iterator{
		private $name = null;
		private $plain = null;
		private $tag = null;
		private $offset = 0;
		private $length = 0;
		private $count = 0;
	
		public function __construct($tag_name,$value,$offset,$length){
			$this->name = $tag_name;
			$this->plain = $value;
			$this->offset = $offset;
			$this->length = $length;
			$this->count = 0;
		}
		public function key(){
			$this->tag->name();
		}
		public function current(){
			$this->plain = substr($this->plain,0,$this->tag->cur()).substr($this->plain,$this->tag->cur() + strlen($this->tag->plain()));
			$this->count++;
			return $this->tag;
		}
		public function valid(){
			if($this->length > 0 && ($this->offset + $this->length) <= $this->count) return false;
			if(is_array($this->name)){
				$tags = array();
				foreach($this->name as $name){
					if(Xml::set($get_tag,$this->plain,$name)) $tags[$get_tag->cur()] = $get_tag;
				}
				if(empty($tags)) return false;
				ksort($tags,SORT_NUMERIC);
				foreach($tags as $this->tag) return true;
			}
			return \testman\Xml::set($this->tag,$this->plain,$this->name);
		}
		public function next(){
		}
		public function rewind(){
			for($i=0;$i<$this->offset;$i++){
				$this->valid();
				$this->current();
			}
		}
	}
	class Xml implements \IteratorAggregate{
		private $attr = array();
		private $plain_attr = array();
		private $name;
		private $value;
		private $close_empty = true;
	
		private $plain;
		private $pos;
		private $esc = true;
	
		public function __construct($name=null,$value=null){
			if($value === null && is_object($name)){
				$n = explode('\\',get_class($name));
				$this->name = array_pop($n);
				$this->value($name);
			}else{
				$this->name = trim($name);
				$this->value($value);
			}
		}
		/**
		 * (non-PHPdoc)
		 * @see IteratorAggregate::getIterator()
		 */
		public function getIterator(){
			return new \ArrayIterator($this->attr);
		}
		/**
		 * 値が無い場合は閉じを省略する
		 * @param boolean
		 * @return boolean
		 */
		final public function close_empty(){
			if(func_num_args() > 0) $this->close_empty = (boolean)func_get_arg(0);
			return $this->close_empty;
		}
		/**
		 * エスケープするか
		 * @param boolean $bool
		 */
		final public function escape($bool){
			$this->esc = (boolean)$bool;
			return $this;
		}
		/**
		 * setできた文字列
		 * @return string
		 */
		final public function plain(){
			return $this->plain;
		}
		/**
		 * 子要素検索時のカーソル
		 * @return integer
		 */
		final public function cur(){
			return $this->pos;
		}
		/**
		 * 要素名
		 * @return string
		 */
		final public function name($name=null){
			if(isset($name)) $this->name = $name;
			return $this->name;
		}
		private function get_value($v){
			if($v instanceof self){
				$v = $v->get();
			}else if(is_bool($v)){
				$v = ($v) ? 'true' : 'false';
			}else if($v === ''){
				$v = null;
			}else if(is_array($v) || is_object($v)){
				$r = '';
				foreach($v as $k => $c){
					if(is_numeric($k) && is_object($c)){
						$e = explode('\\',get_class($c));
						$k = array_pop($e);
					}
					if(is_numeric($k)) $k = 'data';
					$x = new self($k,$c);
					$x->escape($this->esc);
					$r .= $x->get();
				}
				$v = $r;
			}else if($this->esc && strpos($v,'<![CDATA[') === false && preg_match("/&|<|>|\&[^#\da-zA-Z]/",$v)){
				$v = '<![CDATA['.$v.']]>';
			}
			return $v;
		}
		/**
		 * 値を設定、取得する
		 * @param mixed
		 * @param boolean
		 * @return string
		 */
		final public function value(){
			if(func_num_args() > 0) $this->value = $this->get_value(func_get_arg(0));
			if(strpos($this->value,'<![CDATA[') === 0) return substr($this->value,9,-3);
			return $this->value;
		}
		/**
		 * 値を追加する
		 * ２つ目のパラメータがあるとアトリビュートの追加となる
		 * @param mixed $arg
		 */
		final public function add($arg){
			if(func_num_args() == 2){
				$this->attr(func_get_arg(0),func_get_arg(1));
			}else{
				$this->value .= $this->get_value(func_get_arg(0));
			}
			return $this;
		}
		/**
		 * アトリビュートを取得する
		 * @param string $n 取得するアトリビュート名
		 * @param string $d アトリビュートが存在しない場合の代替値
		 * @return string
		 */
		final public function in_attr($n,$d=null){
			return isset($this->attr[strtolower($n)]) ? ($this->esc ? htmlentities($this->attr[strtolower($n)],ENT_QUOTES,'UTF-8') : $this->attr[strtolower($n)]) : (isset($d) ? (string)$d : null);
		}
		/**
		 * アトリビュートから削除する
		 * パラメータが一つも無ければ全件削除
		 */
		final public function rm_attr(){
			if(func_num_args() === 0){
				$this->attr = array();
			}else{
				foreach(func_get_args() as $n) unset($this->attr[$n]);
			}
		}
		/**
		 * アトリビュートがあるか
		 * @param string $name
		 * @return boolean
		 */
		final public function is_attr($name){
			return array_key_exists($name,$this->attr);
		}
		/**
		 * アトリビュートを設定
		 * @return self $this
		 */
		final public function attr($key,$value){
			$this->attr[strtolower($key)] = is_bool($value) ? (($value) ? 'true' : 'false') : $value;
			return $this;
		}
		/**
		 * 値の無いアトリビュートを設定
		 * @param string $v
		 */
		final public function plain_attr($v){
			$this->plain_attr[] = $v;
		}
		/**
		 * XML文字列を返す
		 */
		public function get($encoding=null){
			if($this->name === null) throw new \LogicException('undef name');
			$attr = '';
			$value = ($this->value === null || $this->value === '') ? null : (string)$this->value;
			foreach($this->attr as $k => $v) $attr .= ' '.$k.'="'.$this->in_attr($k).'"';
			return ((empty($encoding)) ? '' : '<?xml version="1.0" encoding="'.$encoding.'" ?'.'>'.PHP_EOL)
			.('<'.$this->name.$attr.(implode(' ',$this->plain_attr)).(($this->close_empty && !isset($value)) ? ' /' : '').'>')
			.$this->value
			.((!$this->close_empty || isset($value)) ? sprintf('</%s>',$this->name) : '');
		}
		public function __toString(){
			return $this->get();
		}
		/**
		 * 文字列からXMLを探す
		 * @param mixed $x 見つかった場合にインスタンスがセットされる
		 * @param string $plain 対象の文字列
		 * @param string $name 要素名
		 * @return boolean
		 */
		static public function set(&$x,$plain,$name=null){
			return self::_set($x,$plain,$name);
		}
		static private function _set(&$x,$plain,$name=null,$vtag=null){
			$plain = (string)$plain;
			$name = (string)$name;
			if(empty($name) && preg_match("/<([\w\:\-]+)[\s][^>]*?>|<([\w\:\-]+)>/is",$plain,$m)){
				$name = str_replace(array("\r\n","\r","\n"),'',(empty($m[1]) ? $m[2] : $m[1]));
			}
			$qname = preg_quote($name,'/');
			if(!preg_match("/<(".$qname.")([\s][^>]*?)>|<(".$qname.")>/is",$plain,$parse,PREG_OFFSET_CAPTURE)) return false;
			$x = new self();
			$x->pos = $parse[0][1];
			$balance = 0;
			$attrs = '';
	
			if(substr($parse[0][0],-2) == '/>'){
				$x->name = $parse[1][0];
				$x->plain = empty($vtag) ? $parse[0][0] : preg_replace('/'.preg_quote(substr($vtag,0,-1).' />','/').'/',$vtag,$parse[0][0],1);
				$attrs = $parse[2][0];
			}else if(preg_match_all("/<[\/]{0,1}".$qname."[\s][^>]*[^\/]>|<[\/]{0,1}".$qname."[\s]*>/is",$plain,$list,PREG_OFFSET_CAPTURE,$x->pos)){
				foreach($list[0] as $arg){
					if(($balance += (($arg[0][1] == '/') ? -1 : 1)) <= 0 &&
							preg_match("/^(<(".$qname.")([\s]*[^>]*)>)(.*)(<\/\\2[\s]*>)$/is",
									substr($plain,$x->pos,($arg[1] + strlen($arg[0]) - $x->pos)),
									$match
							)
					){
						$x->plain = $match[0];
						$x->name = $match[2];
						$x->value = ($match[4] === '' || $match[4] === null) ? null : $match[4];
						$attrs = $match[3];
						break;
					}
				}
				if(!isset($x->plain)){
					return self::_set($x,preg_replace('/'.preg_quote($list[0][0][0],'/').'/',substr($list[0][0][0],0,-1).' />',$plain,1),$name,$list[0][0][0]);
				}
			}
			if(!isset($x->plain)) return false;
			if(!empty($attrs)){
				if(preg_match_all("/[\s]+([\w\-\:]+)[\s]*=[\s]*([\"\'])([^\\2]*?)\\2/ms",$attrs,$attr)){
					foreach($attr[0] as $id => $value){
						$x->attr($attr[1][$id],$attr[3][$id]);
						$attrs = str_replace($value,'',$attrs);
					}
				}
				if(preg_match_all("/([\w\-]+)/",$attrs,$attr)){
					foreach($attr[1] as $v) $x->attr($v,$v);
				}
			}
			return true;
		}
		/**
		 * 指定の要素を検索する
		 * @param string $tag_name 要素名
		 * @param integer $offset 開始位置
		 * @param integer $length 取得する最大数
		 * @return XmlIterator
		 */
		public function in($name,$offset=0,$length=0){
			return new \testman\XmlIterator($name,$this->value(),$offset,$length);
		}
		/**
		 * パスで検索する
		 * @param string $path 検索文字列
		 * @return mixed
		 */
		public function f($path){
			$arg = (func_num_args() == 2) ? func_get_arg(1) : null;
			$paths = explode('.',$path);
			$last = (strpos($path,'(') === false) ? null : array_pop($paths);
			$tag = clone($this);
			$route = array();
			if($arg !== null) $arg = (is_bool($arg)) ? (($arg) ? 'true' : 'false') : strval($arg);
	
			foreach($paths as $p){
				$pos = 0;
				$t = null;
				if(preg_match("/^(.+)\[([\d]+?)\]$/",$p,$matchs)) list($tmp,$p,$pos) = $matchs;
				foreach($tag->in($p,$pos,1) as $t);
				if(!isset($t) || !($t instanceof self)){
					$tag = null;
					break;
				}
				$route[] = $tag = $t;
			}
			if($tag instanceof self){
				if($arg === null){
					switch($last){
						case '': return $tag;
						case 'plain()': return $tag->plain();
						case 'value()': return $tag->value();
						default:
							if(preg_match("/^(attr|in)\((.+?)\)$/",$last,$matchs)){
								list($null,$type,$name) = $matchs;
								if($type == 'in'){
									return $tag->in(trim($name));
								}else if($type == 'attr'){
									return $tag->in_attr($name);
								}
							}
							return null;
					}
				}
				if($arg instanceof self) $arg = $arg->get();
				if(is_bool($arg)) $arg = ($arg) ? 'true' : 'false';
				krsort($route,SORT_NUMERIC);
				$ltag = $rtag = $replace = null;
				$f = true;
	
				foreach($route as $r){
					$ltag = clone($r);
					if($f){
						switch($last){
							case 'value()':
								$replace = $arg;
								break;
							default:
								if(preg_match("/^(attr)\((.+?)\)$/",$last,$matchs)){
									list($null,$type,$name) = $matchs;
									if($type == 'attr'){
										$r->attr($name,$arg);
										$replace = $r->get();
									}else{
										return null;
									}
								}
						}
						$f = false;
					}
					$r->value(empty($rtag) ? $replace : str_replace($rtag->plain(),$replace,$r->value()));
					$replace = $r->get();
					$rtag = clone($ltag);
				}
				$this->value(str_replace($ltag->plain(),$replace,$this->value()));
				return null;
			}
			return (!empty($last) && substr($last,0,2) == 'in') ? array() : null;
		}
		/**
		 * idで検索する
		 *
		 * @param string $name 指定のID
		 * @return self
		 */
		public function id($name){
			if(preg_match("/<.+[\s]*id[\s]*=[\s]*([\"\'])".preg_quote($name)."\\1/",$this->value(),$match,PREG_OFFSET_CAPTURE)){
				if(self::set($tag,substr($this->value(),$match[0][1]))) return $tag;
			}
			return null;
		}
		/**
		 * xmlとし出力する
		 * @param string $encoding エンコード名
		 * @param string $name ファイル名
		 */
		public function output($encoding=null,$name=null){
			header(sprintf('Content-Type: application/xml%s',(empty($name) ? '' : sprintf('; name=%s',$name))));
			print($this->get($encoding));
			exit;
		}
	}	
	
	
	
	
	// web
	class Helper{
		public function htmlencode($value){
			if(!empty($value) && is_string($value)){
				$value = mb_convert_encoding($value,'UTF-8',mb_detect_encoding($value));
				return htmlentities($value,ENT_QUOTES,'UTF-8');
			}
			return $value;
		}
		public function cond_switch($cond,$true='on',$false=''){
			return ($cond === true) ? $true : $false;
		}
		public function cond_pattern_switch($a,$b,$true='on',$false=''){
			return ($a == $b) ? $true : $false;
		}
		public function nl2ul($array){
			$ul = '<ul>';
			foreach($array as $v) $ul .= '<li>'.$v.'</li>';
			$ul .= '</ul>';
			return $ul;
		}
		public function nl2br($array){
			return str_replace(PHP_EOL,'',nl2br(implode(PHP_EOL,$array),true));
		}
	}
	class Template{
		private $file;
		private $selected_template;
		private $selected_src;
	
		private $secure = false;
		private $vars = array();
		private $put_block;
		private $template_super;
		private $media_url;
	
		public function __construct($media_url=null){
			if($media_url !== null) $this->media_url($media_url);
		}
		/**
		 * メディアファイルへのURLの基点を設定
		 * @param string $url
		 * @return $this
		 */
		public function media_url($url){
			$this->media_url = str_replace("\\",'/',$url);
			if(!empty($this->media_url) && substr($this->media_url,-1) !== '/') $this->media_url = $this->media_url.'/';
		}
		public function template_super($path){
			$this->template_super = $path;
		}
		public function put_block($path){
			$this->put_block = $path;
		}
		public function secure($bool){
			$this->secure = (boolean)$bool;
		}
		public function vars($key,$value){
			$this->vars[$key] = $value;
		}
		/**
		 * 出力する
		 * @param string $file
		 * @param string $template_name
		 */
		final public function output($file,$template_name=null){
			print($this->read($file,$template_name));
			exit;
		}
		/**
		 * ファイルを読み込んで結果を返す
		 * @param string $file
		 * @param string $template_name
		 * @return string
		 */
		final public function read($file,$template_name=null){
			if(!is_file($file) && strpos($file,'://') === false) throw new \InvalidArgumentException($file.' not found');
			$this->file = $file;
			$cname = md5($this->template_super.$this->put_block.$this->file.$this->selected_template);
	
			if(!empty($this->put_block)){
				$src = $this->read_src($this->put_block);
				if(strpos($src,'rt:extends') !== false){
					\testman\Xml::set($x,'<:>'.$src.'</:>');
					foreach($x->in('rt:extends') as $ext) $src = str_replace($ext->plain(),'',$src);
				}
				$src = sprintf('<rt:extends href="%s" />\n',$file).$src;
				$this->file = $this->put_block;
			}else{
				$src = $this->read_src($this->file);
			}
			$src = $this->replace($src,$template_name);
			return $this->execute($src);
		}
		private function cname(){
			return md5($this->put_block.$this->file.$this->selected_template);
		}
		/**
		 * 文字列から結果を返す
		 * @param string $src
		 * @param string $template_name
		 * @return string
		 */
		final public function get($src,$template_name=null){
			return $this->execute($this->replace($src,$template_name));
		}
		private function execute($src){
			$src = $this->exec($src);
			$src = str_replace(array('#PS#','#PE#'),array('<?','?>'),$this->html_reform($src));
			return $src;
		}
		private function replace($src,$template_name){
			$this->selected_template = $template_name;
			$src = preg_replace("/([\w])\->/","\\1__PHP_ARROW__",$src);
			$src = str_replace(array("\\\\","\\\"","\\'"),array('__ESC_DESC__','__ESC_DQ__','__ESC_SQ__'),$src);
			$src = $this->replace_xtag($src);
			$src = $this->rtcomment($this->rtblock($this->rttemplate($src),$this->file));
			$this->selected_src = $src;
			$src = $this->rtif($this->rtloop($this->html_form($this->html_list($src))));
			$src = str_replace('__PHP_ARROW__','->',$src);
			$src = $this->parse_print_variable($src);
			$php = array(' ?>','<?php ','->');
			$str = array('__PHP_TAG_END__','__PHP_TAG_START__','__PHP_ARROW__');
			$src = str_replace($php,$str,$src);
			$src = $this->parse_url($src,$this->media_url);
			$src = str_replace($str,$php,$src);
			$src = str_replace(array('__ESC_DQ__','__ESC_SQ__','__ESC_DESC__'),array("\\\"","\\'","\\\\"),$src);
			return $src;
		}
		private function exec($_src_){
			$this->vars('t',new \testman\Helper());
			ob_start();
			if(is_array($this->vars) && !empty($this->vars)) extract($this->vars);
			eval('?>'.$_src_);
			$_eval_src_ = ob_get_clean();
	
			if(strpos($_eval_src_,'Parse error: ') !== false){
				if(preg_match("/Parse error\:(.+?) in .+eval\(\)\'d code on line (\d+)/",$_eval_src_,$match)){
					list($msg,$line) = array(trim($match[1]),((int)$match[2]));
					$lines = explode("\n",$_src_);
					$plrp = substr_count(implode("\n",array_slice($lines,0,$line)),"<?php 'PLRP'; ?>\n");
					$this->error_msg($msg.' on line '.($line-$plrp).' [compile]: '.trim($lines[$line-1]));
	
					$lines = explode("\n",$this->selected_src);
					$this->error_msg($msg.' on line '.($line-$plrp).' [plain]: '.trim($lines[$line-1-$plrp]));
				}
			}
			$this->selected_src = null;
			return $_eval_src_;
		}
		public function error_msg($msg){
			print($msg);
		}
		private function error_handler($errno,$errstr,$errfile,$errline){
			throw new \ErrorException($errstr,0,$errno,$errfile,$errline);
		}
		private function replace_xtag($src){
			if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
				foreach($null[0] as $value) $src = str_replace($value,'#PS#'.substr($value,2,-2).'#PE#',$src);
			}
			return $src;
		}
		private function parse_url($src,$media=null){
			if(!empty($media) && substr($media,-1) !== '/') $media = $media.'/';
			$secure_base = ($this->secure) ? str_replace('http://','https://',$media) : null;
			if(preg_match_all("/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",$src,$match)){
				foreach($match[2] as $k => $p){
					$t = null;
					if(strtolower($p) === 'href') list($t) = (preg_split("/[\s]/",strtolower($match[1][$k])));
					$src = $this->replace_parse_url($src,(($this->secure && $t !== 'a') ? $secure_base : $media),$match[0][$k],$match[4][$k]);
				}
			}
			if(preg_match_all("/[^:]:[\040]*url\(([^\n]+?)\)/",$src,$match)){
				if($this->secure) $media = $secure_base;
				foreach($match[1] as $key => $param) $src = $this->replace_parse_url($src,$media,$match[0][$key],$match[1][$key]);
			}
			return $src;
		}
		private function replace_parse_url($src,$base,$dep,$rep){
			if(!preg_match("/(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\{\\$)|(^\w+:)|(^[#\?])/",$rep)){
				$src = str_replace($dep,str_replace($rep,\testman\Util::path_absolute($base,$rep),$dep),$src);
			}
			return $src;
		}
		private function read_src($filename){
			// TODO
			$src = file_get_contents($filename);
			return (preg_match('/^http[s]*\:\/\//',$filename)) ? $this->parse_url($src,dirname($filename)) : $src;
		}
		private function rttemplate($src){
			// TODO
			$values = array();
			$bool = false;
			while(\testman\Xml::set($tag,$src,'rt:template')){
				$src = str_replace($tag->plain(),'',$src);
				$values[$tag->in_attr('name')] = $tag->value();
				$src = str_replace($tag->plain(),'',$src);
				$bool = true;
			}
			if(!empty($this->selected_template)){
				if(!array_key_exists($this->selected_template,$values)) throw new \LogicException('undef rt:template '.$this->selected_template);
				return $values[$this->selected_template];
			}
			return ($bool) ? implode($values) : $src;
		}
		private function rtblock($src,$filename){
			if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
				$base_filename = $filename;
				$blocks = $paths = array();
				while(\testman\Xml::set($e,'<:>'.$this->rtcomment($src).'</:>','rt:extends') !== false){
					$href = \testman\Util::path_absolute(str_replace("\\",'/',dirname($filename)),$e->in_attr('href'));
					if(!$e->is_attr('href') || !is_file($href)) throw new \LogicException('href ('.$href.') not found '.$filename);
					if($filename === $href) throw new \LogicException('Infinite Recursion Error'.$filename);
					\testman\Xml::set($bx,'<:>'.$this->rtcomment($src).'</:>',':');
					foreach($bx->in('rt:block') as $b){
						$n = $b->in_attr('name');
						if(!empty($n) && !array_key_exists($n,$blocks)){
							$blocks[$n] = $b->value();
							$paths[$n] = $filename;
						}
					}
					// TODO
					$src = $this->rttemplate($this->replace_xtag($this->read_src($filename = $href)));
					$this->selected_template = $e->in_attr('name');
				}
				if(empty($blocks)){
					if(\testman\Xml::set($bx,'<:>'.$src.'</:>')){
						foreach($bx->in('rt:block') as $b) $src = str_replace($b->plain(),$b->value(),$src);
					}
				}else{
					if(!empty($this->template_super)) $src = $this->read_src(\testman\Util::path_absolute(str_replace("\\",'/',dirname($base_filename)),$this->template_super));
					while(\testman\Xml::set($b,$src,'rt:block')){
						$n = $b->in_attr('name');
						$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
					}
				}
				$this->file = $filename;
			}
			return $src;
		}
		private function rtcomment($src){
			while(\testman\Xml::set($tag,$src,'rt:comment')) $src = str_replace($tag->plain(),'',$src);
			return $src;
		}
		private function rtloop($src){
			if(strpos($src,'rt:loop') !== false){
				while(\testman\Xml::set($tag,$src,'rt:loop')){
					$tag->escape(false);
					$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
					$offset = ($tag->is_attr('offset')) ? (ctype_digit($tag->in_attr('offset')) ? $tag->in_attr('offset') : $this->variable_string($this->parse_plain_variable($tag->in_attr('offset')))) : 1;
					$limit = ($tag->is_attr('limit')) ? (ctype_digit($tag->in_attr('limit')) ? $tag->in_attr('limit') : $this->variable_string($this->parse_plain_variable($tag->in_attr('limit')))) : 0;
					if(empty($param) && $tag->is_attr('range')){
						list($range_start,$range_end) = explode(',',$tag->in_attr('range'),2);
						$range = ($tag->is_attr('range_step')) ? sprintf('range(%d,%d,%d)',$range_start,$range_end,$tag->in_attr('range_step')) :
						sprintf('range("%s","%s")',$range_start,$range_end);
						$param = sprintf('array_combine(%s,%s)',$range,$range);
					}
					$is_fill = false;
					$uniq = uniqid('');
					$even = $tag->in_attr('even_value','even');
					$odd = $tag->in_attr('odd_value','odd');
					$evenodd = '$'.$tag->in_attr('evenodd','loop_evenodd');
	
					$first_value = $tag->in_attr('first_value','first');
					$first = '$'.$tag->in_attr('first','_first_'.$uniq);
					$first_flg = '$__isfirst__'.$uniq;
					$last_value = $tag->in_attr('last_value','last');
					$last = '$'.$tag->in_attr('last','_last_'.$uniq);
					$last_flg = '$__islast__'.$uniq;
					$shortfall = '$'.$tag->in_attr('shortfall','_DEFI_'.$uniq);
	
					$var = '$'.$tag->in_attr('var','_var_'.$uniq);
					$key = '$'.$tag->in_attr('key','_key_'.$uniq);
					$total = '$'.$tag->in_attr('total','_total_'.$uniq);
					$vtotal = '$__vtotal__'.$uniq;
					$counter = '$'.$tag->in_attr('counter','_counter_'.$uniq);
					$loop_counter = '$'.$tag->in_attr('loop_counter','_loop_counter_'.$uniq);
					$reverse = (strtolower($tag->in_attr('reverse') === 'true'));
	
					$varname = '$_'.$uniq;
					$countname = '$__count__'.$uniq;
					$lcountname = '$__vcount__'.$uniq;
					$offsetname	= '$__offset__'.$uniq;
					$limitname = '$__limit__'.$uniq;
	
					$value = $tag->value();
					$empty_value = null;
					while(\testman\Xml::set($subtag,$value,'rt:loop')){
						$value = $this->rtloop($value);
					}
					while(\testman\Xml::set($subtag,$value,'rt:first')){
						$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$first
								,(($subtag->in_attr('last') === 'false') ? sprintf(' && (%s !== 1) ',$total) : '')
								,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
					}
					while(\testman\Xml::set($subtag,$value,'rt:middle')){
						$value = str_replace($subtag->plain(),sprintf('<?php if(!isset(%s) && !isset(%s)){ ?>%s<?php } ?>',$first,$last
								,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
					}
					while(\testman\Xml::set($subtag,$value,'rt:last')){
						$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$last
								,(($subtag->in_attr('first') === 'false') ? sprintf(' && (%s !== 1) ',$vtotal) : '')
								,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
					}
					while(\testman\Xml::set($subtag,$value,'rt:fill')){
						$is_fill = true;
						$value = str_replace($subtag->plain(),sprintf('<?php if(%s > %s){ ?>%s<?php } ?>',$lcountname,$total
								,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
					}
					$value = $this->rtif($value);
					if(preg_match("/^(.+)<rt\:else[\s]*.*?>(.+)$/ims",$value,$match)){
						list(,$value,$empty_value) = $match;
					}
					$src = str_replace(
							$tag->plain(),
							sprintf("<?php try{ ?>"
									."<?php "
									." %s=%s;"
									." if(is_array(%s)){"
									." if(%s){ krsort(%s); }"
									." %s=%s=sizeof(%s); %s=%s=1; %s=%s; %s=((%s>0) ? (%s + %s) : 0); "
									." %s=%s=false; %s=0; %s=%s=null;"
									." if(%s){ for(\$i=0;\$i<(%s+%s-%s);\$i++){ %s[] = null; } %s=sizeof(%s); }"
									." foreach(%s as %s => %s){"
									." if(%s <= %s){"
									." if(!%s){ %s=true; %s='%s'; }"
									." if((%s > 0 && (%s+1) == %s) || %s===%s){ %s=true; %s='%s'; %s=(%s-%s+1) * -1;}"
									." %s=((%s %% 2) === 0) ? '%s' : '%s';"
									." %s=%s; %s=%s;"
									." ?>%s<?php "
									." %s=%s=null;"
									." %s++;"
									." }"
									." %s++;"
									." if(%s > 0 && %s >= %s){ break; }"
									." }"
									." if(!%s){ ?>%s<?php } "
									." unset(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s);"
									." }"
									." ?>"
									."<?php }catch(\\Exception \$e){} ?>"
									,$varname,$param
									,$varname
									,(($reverse) ? 'true' : 'false'),$varname
									,$vtotal,$total,$varname,$countname,$lcountname,$offsetname,$offset,$limitname,$limit,$offset,$limit
									,$first_flg,$last_flg,$shortfall,$first,$last
									,($is_fill ? 'true' : 'false'),$offsetname,$limitname,$total,$varname,$vtotal,$varname
									,$varname,$key,$var
									,$offsetname,$lcountname
									,$first_flg,$first_flg,$first,str_replace("'","\\'",$first_value)
									,$limitname,$lcountname,$limitname,$lcountname,$vtotal,$last_flg,$last,str_replace("'","\\'",$last_value),$shortfall,$lcountname,$limitname
									,$evenodd,$countname,$even,$odd
									,$counter,$countname,$loop_counter,$lcountname
									,$value
									,$first,$last
									,$countname
									,$lcountname
									,$limitname,$lcountname,$limitname
									,$first_flg,$empty_value
									,$var,$counter,$key,$countname,$lcountname,$offsetname,$limitname,$varname,$first,$first_flg,$last,$last_flg
							)
							,$src
					);
				}
			}
			return $src;
		}
		private function rtif($src){
			if(strpos($src,'rt:if') !== false){
				while(\testman\Xml::set($tag,$src,'rt:if')){
					$tag->escape(false);
					if(!$tag->is_attr('param')) throw new \LogicException('if');
					$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));
	
					if($tag->is_attr('value')){
						$arg2 = $this->parse_plain_variable($tag->in_attr('value'));
						if($arg2 == 'true' || $arg2 == 'false' || ctype_digit((string)$arg2)){
							$cond = sprintf('<?php if(%s === %s || %s === "%s"){ ?>',$arg1,$arg2,$arg1,$arg2);
						}else{
							if($arg2 === '' || $arg2[0] != '$') $arg2 = '"'.$arg2.'"';
							$cond = sprintf('<?php if(%s === %s){ ?>',$arg1,$arg2);
						}
					}else{
						$uniq = uniqid('$I');
						$cond = sprintf("<?php try{ %s=%s; }catch(\\Exception \$e){ %s=null; } ?>",$uniq,$arg1,$uniq)
						.sprintf('<?php if(%s !== null && %s !== false && ( (!is_string(%s) && !is_array(%s)) || (is_string(%s) && %s !== "") || (is_array(%s) && !empty(%s)) ) ){ ?>',$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq);
					}
					$src = str_replace(
							$tag->plain()
							,'<?php try{ ?>'.$cond
							.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$tag->value())
							."<?php } ?>"
							."<?php }catch(\\Exception \$e){} ?>"
							,$src
					);
				}
			}
			return $src;
		}
		private function parse_print_variable($src){
			foreach($this->match_variable($src) as $variable){
				$name = $this->parse_plain_variable($variable);
				$value = '<?php try{ @print('.$name.'); ?>'
				."<?php }catch(\\Exception \$e){} ?>";
				$src = str_replace(array($variable.PHP_EOL,$variable),array($value."<?php 'PLRP'; ?>\n\n",$value),$src);
				$src = str_replace($variable,$value,$src);
			}
			return $src;
		}
		private function match_variable($src){
			$hash = array();
			while(preg_match("/({(\\$[\$\w][^\t]*)})/s",$src,$vars,PREG_OFFSET_CAPTURE)){
				list($value,$pos) = $vars[1];
				if($value == "") break;
				if(substr_count($value,'}') > 1){
					for($i=0,$start=0,$end=0;$i<strlen($value);$i++){
						if($value[$i] == '{'){
							$start++;
						}else if($value[$i] == '}'){
							if($start == ++$end){
								$value = substr($value,0,$i+1);
								break;
							}
						}
					}
				}
				$length	= strlen($value);
				$src = substr($src,$pos + $length);
				$hash[sprintf('%03d_%s',$length,$value)] = $value;
			}
			krsort($hash,SORT_STRING);
			return $hash;
		}
		private function parse_plain_variable($src){
			while(true){
				$array = $this->match_variable($src);
				if(sizeof($array) <= 0)	break;
				foreach($array as $v){
					$tmp = $v;
					if(preg_match_all("/([\"\'])([^\\1]+?)\\1/",$v,$match)){
						foreach($match[2] as $value) $tmp = str_replace($value,str_replace('.','__PERIOD__',$value),$tmp);
					}
					$src = str_replace($v,preg_replace('/([\w\)\]])\./','\\1->',substr($tmp,1,-1)),$src);
				}
			}
			return str_replace('[]','',str_replace('__PERIOD__','.',$src));
		}
		private function variable_string($src){
			return (empty($src) || isset($src[0]) && $src[0] == '$') ? $src : '$'.$src;
		}
		private function html_reform($src){
			if(strpos($src,'rt:aref') !== false){
				\testman\Xml::set($tag,'<:>'.$src.'</:>');
				foreach($tag->in('form') as $obj){
					if($obj->is_attr('rt:aref')){
						$bool = ($obj->in_attr('rt:aref') === 'true');
						$obj->rm_attr('rt:aref');
						$obj->escape(false);
						$value = $obj->get();
	
						if($bool){
							foreach($obj->in(array('input','select','textarea')) as $tag){
								if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
									switch(strtolower($tag->in_attr('type','text'))){
										case 'button':
										case 'submit':
										case 'file':
											break;
										default:
											$tag->attr('rt:ref','true');
											$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
									}
								}
							}
							$value = $this->exec($this->parse_print_variable($this->html_input($obj->get())));
						}
						$src = str_replace($obj->plain(),$value,$src);
					}
				}
			}
			return $src;
		}
		private function html_form($src){
			\testman\Xml::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in('form') as $obj){
				if($this->is_reference($obj)){
					$obj->escape(false);
					foreach($obj->in(array('input','select','textarea')) as $tag){
						if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
							switch(strtolower($tag->in_attr('type','text'))){
								case 'button':
								case 'submit':
									break;
								case 'file':
									$obj->attr('enctype','multipart/form-data');
									$obj->attr('method','post');
									break;
								default:
									$tag->attr('rt:ref','true');
									$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
							}
						}
					}
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
			return $this->html_input($src);
		}
		private function no_exception_str($value){
			return $value;
		}
		private function html_input($src){
			\testman\Xml::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in(array('input','textarea','select')) as $obj){
				if('' != ($originalName = $obj->in_attr('name',$obj->in_attr('id','')))){
					$obj->escape(false);
					$type = strtolower($obj->in_attr('type','text'));
					$name = $this->parse_plain_variable($this->form_variable_name($originalName));
					$lname = strtolower($obj->name());
					$change = false;
					$uid = uniqid();
	
					if(substr($originalName,-2) !== '[]'){
						if($type == 'checkbox'){
							if($obj->in_attr('rt:multiple','true') === 'true') $obj->attr('name',$originalName.'[]');
							$obj->rm_attr('rt:multiple');
							$change = true;
						}else if($obj->is_attr('multiple') || $obj->in_attr('multiple') === 'multiple'){
							$obj->attr('name',$originalName.'[]');
							$obj->rm_attr('multiple');
							$obj->attr('multiple','multiple');
							$change = true;
						}
					}else if($obj->in_attr('name') !== $originalName){
						$obj->attr('name',$originalName);
						$change = true;
					}
					if($obj->is_attr('rt:param') || $obj->is_attr('rt:range')){
						switch($lname){
							case 'select':
								$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" key="%s" offset="%s" limit="%s" reverse="%s" evenodd="%s" even_value="%s" odd_value="%s" range="%s" range_step="%s">'
								.'<option value="{$%s}">{$%s}</option>'
								.'</rt:loop>'
								,$obj->in_attr('rt:param'),$obj->in_attr('rt:var','loop_var'.$uid),$obj->in_attr('rt:counter','loop_counter'.$uid)
								,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
								,$obj->in_attr('rt:reverse','false')
								,$obj->in_attr('rt:evenodd','loop_evenodd'.$uid),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
								,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
								,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:var','loop_var'.$uid)
								);
								$obj->value($this->rtloop($value));
								if($obj->is_attr('rt:null')) $obj->value('<option value="">'.$obj->in_attr('rt:null').'</option>'.$obj->value());
						}
						$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd'
								,'rt:range','rt:range_step','rt:even_value','rt:odd_value');
						$change = true;
					}
					if($this->is_reference($obj)){
						switch($lname){
							case 'textarea':
								$obj->value($this->no_exception_str(sprintf('{$t.htmlencode(%s)}',((preg_match("/^{\$(.+)}$/",$originalName,$match)) ? '{$$'.$match[1].'}' : '{$'.$originalName.'}'))));
								break;
							case 'select':
								$select = $obj->value();
								foreach($obj->in('option') as $option){
									$option->escape(false);
									$value = $this->parse_plain_variable($option->in_attr('value'));
									if(empty($value) || $value[0] != '$') $value = sprintf("'%s'",$value);
									$option->rm_attr('selected');
									$option->plain_attr($this->check_selected($name,$value,'selected'));
									$select = str_replace($option->plain(),$option->get(),$select);
								}
								$obj->value($select);
								break;
							case 'input':
								switch($type){
									case 'checkbox':
									case 'radio':
										$value = $this->parse_plain_variable($obj->in_attr('value','true'));
										$value = (substr($value,0,1) != '$') ? sprintf("'%s'",$value) : $value;
										$obj->rm_attr('checked');
										$obj->plain_attr($this->check_selected($name,$value,'checked'));
										break;
									case 'text':
									case 'hidden':
									case 'password':
									case 'search':
									case 'url':
									case 'email':
									case 'tel':
									case 'datetime':
									case 'date':
									case 'month':
									case 'week':
									case 'time':
									case 'datetime-local':
									case 'number':
									case 'range':
									case 'color':
										$obj->attr('value',$this->no_exception_str(sprintf('{$t.htmlencode(%s)}',
										((preg_match("/^\{\$(.+)\}$/",$originalName,$match)) ?
										'{$$'.$match[1].'}' :
										'{$'.$originalName.'}'))));
										break;
								}
								break;
						}
						$change = true;
					}else if($obj->is_attr('rt:ref')){
						$obj->rm_attr('rt:ref');
						$change = true;
					}
					if($change){
						switch($lname){
							case 'textarea':
							case 'select':
								$obj->close_empty(false);
						}
						$src = str_replace($obj->plain(),$obj->get(),$src);
					}
				}
			}
			return $src;
		}
		private function check_selected($name,$value,$selected){
			return sprintf('<?php if('
					.'isset(%s) && (%s === %s '
					.' || (!is_array(%s) && ctype_digit((string)%s) && (string)%s === (string)%s)'
					.' || ((%s === "true" || %s === "false") ? (%s === (%s == "true")) : false)'
					.' || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? array() : array(%s))),true) '
					.') '
					.'){print(" %s=\"%s\"");} ?>'
					,$name,$name,$value
					,$name,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
					,$selected,$selected
			);
		}
		private function html_list($src){
			if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
				$tags = array();
				foreach($m[1] as $k => $v){
					if(\testman\Xml::set($tag,substr($src,$v[1]-1),$v[0])) $tags[] = $tag;
				}
				foreach($tags as $obj){
					$obj->escape(false);
					$name = strtolower($obj->name());
					$param = $obj->in_attr('rt:param');
					$null = strtolower($obj->in_attr('rt:null'));
					$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" '
							.'key="%s" offset="%s" limit="%s" '
							.'reverse="%s" '
							.'evenodd="%s" even_value="%s" odd_value="%s" '
							.'range="%s" range_step="%s" '
							.'shortfall="%s">'
							,$param,$obj->in_attr('rt:var','loop_var'),$obj->in_attr('rt:counter','loop_counter')
							,$obj->in_attr('rt:key','loop_key'),$obj->in_attr('rt:offset','0'),$obj->in_attr('rt:limit','0')
							,$obj->in_attr('rt:reverse','false')
							,$obj->in_attr('rt:evenodd','loop_evenodd'),$obj->in_attr('rt:even_value','even'),$obj->in_attr('rt:odd_value','odd')
							,$obj->in_attr('rt:range'),$obj->in_attr('rt:range_step',1)
							,$tag->in_attr('rt:shortfall','_DEFI_'.uniqid())
					);
					$rawvalue = $obj->value();
					if($name == 'table' && \testman\Xml::set($t,$rawvalue,'tbody')){
						$t->escape(false);
						$t->value($value.$this->table_tr_even_odd($t->value(),(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>');
						$value = str_replace($t->plain(),$t->get(),$rawvalue);
					}else{
						$value = $value.$this->table_tr_even_odd($rawvalue,(($name == 'table') ? 'tr' : 'li'),$obj->in_attr('rt:evenodd','loop_evenodd')).'</rt:loop>';
					}
					$obj->value($this->html_list($value));
					$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter','rt:offset','rt:limit','rt:null','rt:evenodd','rt:range'
							,'rt:range_step','rt:even_value','rt:odd_value','rt:shortfall');
					$src = str_replace($obj->plain(),
							($null === 'true') ? $this->rtif(sprintf('<rt:if param="%s">',$param).$obj->get().'</rt:if>') : $obj->get(),
							$src);
				}
			}
			return $src;
		}
		private function table_tr_even_odd($src,$name,$even_odd){
			\testman\Xml::set($tag,'<:>'.$src.'</:>');
			foreach($tag->in($name) as $tr){
				$tr->escape(false);
				$class = ' '.$tr->in_attr('class').' ';
				if(preg_match('/[\s](even|odd)[\s]/',$class,$match)){
					$tr->attr('class',trim(str_replace($match[0],' {$'.$even_odd.'} ',$class)));
					$src = str_replace($tr->plain(),$tr->get(),$src);
				}
			}
			return $src;
		}
		private function form_variable_name($name){
			return (strpos($name,'[') && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$match)) ?
			'{$'.$match[1].'["'.$match[2].'"]'.'}' : '{$'.$name.'}';
		}
		private function is_reference(&$tag){
			$bool = ($tag->in_attr('rt:ref') === 'true');
			$tag->rm_attr('rt:ref');
			return $bool;
		}
	}
	
	// cli
	class Http{
		private $resource;
		private $agent;
		private $timeout = 30;
		private $redirect_max = 20;
		private $redirect_count = 1;
	
		private $request_header = array();
		private $request_vars = array();
		private $request_file_vars = array();
		private $head;
		private $body;
		private $cookie = array();
		private $url;
		private $status;
	
		public function __construct($agent=null,$timeout=30,$redirect_max=20){
			$this->agent = $agent;
			$this->timeout = (int)$timeout;
			$this->redirect_max = (int)$redirect_max;
			$this->resource = curl_init();
		}
		public function redirect_max($redirect_max){
			$this->redirect_max = (integer)$redirect_max;
		}
		public function timeout($timeout){
			$this->timeout = (int)$timeout;
		}
		public function agent($agent){
			$this->agent = $agent;
		}
		public function __toString(){
			return $this->body();
		}
		public function header($key,$value=null){
			$this->request_header[$key] = $value;
		}
		public function vars($key,$value=null){
			if(is_bool($value)) $value = ($value) ? 'true' : 'false';
			$this->request_vars[$key] = $value;
			if(isset($this->request_file_vars[$key])) unset($this->request_file_vars[$key]);
		}
		public function file_vars($key,$value){
			$this->request_file_vars[$key] = $value;
			if(isset($this->request_vars[$key])) unset($this->request_vars[$key]);
		}
		public function setopt($key,$value){
			curl_setopt($this->resource,$key,$value);
		}
		public function head(){
			return $this->head;
		}
		public function body(){
			return ($this->body === null || is_bool($this->body)) ? '' : $this->body;
		}
		public function url(){
			return $this->url;
		}
		public function status(){
			return $this->status;
		}
		public function do_head($url){
			return $this->request('HEAD',$url);
		}
		public function do_put($url){
			return $this->request('PUT',$url);
		}
		public function do_delete(){
			return $this->request('DELETE',$url);
		}
		public function do_get($url){
			return $this->request('GET',$url);
		}
		public function do_post($url){
			return $this->request('POST',$url);
		}
		public function do_download($url,$download_path){
			return $this->request('GET',$url,$download_path);
		}
		public function do_post_download($url,$download_path){
			return $this->request('POST',$url,$download_path);
		}
		private function request($method,$url,$download_path=null){
			$url_info = parse_url($url);
			$cookie_base_domain = $url_info['host'].(isset($url_info['path']) ? $url_info['path'] : '');
			if(isset($url_info['query'])){
				parse_str($url_info['query'],$vars);
				foreach($vars as $k => $v){
					if(!isset($this->request_vars[$k])) $this->request_vars[$k] = $v;
				}
				list($url) = explode('?',$url,2);
			}
			switch($method){
				case 'POST': curl_setopt($this->resource,CURLOPT_POST,true); break;
				case 'GET': curl_setopt($this->resource,CURLOPT_HTTPGET,true); break;
				case 'HEAD': curl_setopt($this->resource,CURLOPT_NOBODY,true); break;
				case 'PUT': curl_setopt($this->resource,CURLOPT_PUT,true); break;
				case 'DELETE': curl_setopt($this->resource,CURLOPT_CUSTOMREQUEST,'DELETE'); break;
			}
			switch($method){
				case 'POST':
					if(!empty($this->request_file_vars)){
						$vars = array();
						if(!empty($this->request_vars)){
							foreach(explode('&',http_build_query($this->request_vars)) as $q){
								$s = explode('=',$q,2);
								$vars[urldecode($s[0])] = isset($s[1]) ? urldecode($s[1]) : null;
							}
						}						
						foreach(explode('&',http_build_query($this->request_file_vars)) as $q){
							$s = explode('=',$q,2);
							$vars[urldecode($s[0])] = isset($s[1]) ? '@'.urldecode($s[1]) : null;
						}
						curl_setopt($this->resource,CURLOPT_POSTFIELDS,$vars);
					}else{
						curl_setopt($this->resource,CURLOPT_POSTFIELDS,http_build_query($this->request_vars));						
					}
					break;
				case 'GET':
				case 'HEAD':
				case 'PUT':
				case 'DELETE':
					$url = $url.(!empty($this->request_vars) ? '?'.http_build_query($this->request_vars) : '');
			}
			curl_setopt($this->resource,CURLOPT_URL,$url);
			curl_setopt($this->resource,CURLOPT_FOLLOWLOCATION,false);
			curl_setopt($this->resource,CURLOPT_HEADER,false);
			curl_setopt($this->resource,CURLOPT_RETURNTRANSFER,false);
			curl_setopt($this->resource,CURLOPT_FORBID_REUSE,true);
			curl_setopt($this->resource,CURLOPT_FAILONERROR,false);
			curl_setopt($this->resource,CURLOPT_TIMEOUT,$this->timeout);
	
			if(!isset($this->request_header['Expect'])){
				$this->request_header['Expect'] = null;
			}
			if(!isset($this->request_header['Cookie'])){
				$cookies = '';
				foreach($this->cookie as $domain => $cookie_value){
					if(strpos($cookie_base_domain,$domain) === 0 || strpos($cookie_base_domain,(($domain[0] == '.') ? $domain : '.'.$domain)) !== false){
						foreach($cookie_value as $k => $v){
							if(!$v['secure'] || ($v['secure'] && substr($url,0,8) == 'https://')) $cookies .= sprintf('%s=%s; ',$k,$v['value']);
						}
					}
				}
				curl_setopt($this->resource,CURLOPT_COOKIE,$cookies);
			}
			if(!isset($this->request_header['User-Agent'])){
				curl_setopt($this->resource,CURLOPT_USERAGENT,
						(empty($this->agent) ?
								(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null) :
								$this->agent
						)
				);
			}
			if(!isset($this->request_header['Accept']) && isset($_SERVER['HTTP_ACCEPT'])){
				$this->request_header['Accept'] = $_SERVER['HTTP_ACCEPT'];
			}
			if(!isset($this->request_header['Accept-Language']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
				$this->request_header['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			}
			if(!isset($this->request_header['Accept-Charset']) && isset($_SERVER['HTTP_ACCEPT_CHARSET'])){
				$this->request_header['Accept-Charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
			}
	
			curl_setopt($this->resource,CURLOPT_HTTPHEADER,
					array_map(function($k,$v){
				return $k.': '.$v;
			}
			,array_keys($this->request_header)
			,$this->request_header
			)
			);
			curl_setopt($this->resource,CURLOPT_HEADERFUNCTION,function($c,$data){
				$this->head .= $data;
				return strlen($data);
			});
			if(empty($download_path)){
				curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,function($c,$data){
					$this->body .= $data;
					return strlen($data);
				});
			}else{
				if(!is_dir(dirname($download_path))) mkdir(dirname($download_path),0777,true);
				$fp = fopen($download_path,'wb');
	
				curl_setopt($this->resource,CURLOPT_WRITEFUNCTION,function($c,$data) use(&$fp){
					if($fp) fwrite($fp,$data);
					return strlen($data);
				});
			}
			$this->request_header = $this->request_vars = array();
			$this->head = $this->body = '';
			curl_exec($this->resource);
			if(!empty($download_path) && $fp){
				fclose($fp);
			}
	
			$this->url = trim(curl_getinfo($this->resource,CURLINFO_EFFECTIVE_URL));
			$this->status = curl_getinfo($this->resource,CURLINFO_HTTP_CODE);
	
			if($err_code = curl_errno($this->resource) > 0){
				if($err_code == 47) return $this;
				throw new \RuntimeException($err_code.': '.curl_error($this->resource));
			}
			if(preg_match_all('/Set-Cookie:[\s]*(.+)/i',$this->head,$match)){
				$unsetcookie = $setcookie = array();
				foreach($match[1] as $cookies){
					$cookie_name = $cookie_value = $cookie_domain = $cookie_path = $cookie_expires = null;
					$cookie_domain = $cookie_base_domain;
					$cookie_path = '/';
					$secure = false;
	
					foreach(explode(';',$cookies) as $cookie){
						$cookie = trim($cookie);
						if(strpos($cookie,'=') !== false){
							list($k,$v) = explode('=',$cookie,2);
							$k = trim($k);
							$v = trim($v);
							switch(strtolower($k)){
								case 'expires': $cookie_expires = ctype_digit($v) ? (int)$v : strtotime($v); break;
								case 'domain': $cookie_domain = preg_replace('/^[\w]+:\/\/(.+)$/','\\1',$v); break;
								case 'path': $cookie_path = $v; break;
								default:
									$cookie_name = $k;
									$cookie_value = $v;
							}
						}else if(strtolower($cookie) == 'secure'){
							$secure = true;
						}
					}
					$cookie_domain = substr(\testman\Util::path_absolute('http://'.$cookie_domain,$cookie_path),7);
					if($cookie_expires !== null && $cookie_expires < time()){
						if(isset($this->cookie[$cookie_domain][$cookie_name])) unset($this->cookie[$cookie_domain][$cookie_name]);
					}else{
						$this->cookie[$cookie_domain][$cookie_name] = array('value'=>$cookie_value,'expires'=>$cookie_expires,'secure'=>$secure);
					}
				}
			}
			if($this->redirect_count++ < $this->redirect_max){
				switch($this->status){
					case 300:
					case 301:
					case 302:
					case 303:
					case 307:
						if(preg_match('/Location:[\040](.*)/i',$this->head,$redirect_url)){
							return $this->request('GET',\testman\Util::path_absolute($url,$redirect_url[1]),$download_path);
						}
				}
			}
			$this->redirect_count = 1;
			return $this;
		}
		public function __destruct(){
			curl_close($this->resource);
		}
		private function info(){
			return curl_getinfo($this->resource);
		}
	}
	
	class TestRunner{
		static private $result = array();
		static private $current_entry;
		static private $current_class;
		static private $current_method;
		static private $current_file;
		static private $current_block_name;
		static private $current_block_label;
		static private $current_block_start_time;
		static private $start_time;
		static private $urls;
	
		static private $entry_dir;
		static private $test_dir;
		static private $lib_dir;
		static private $func_dir;
	
		static private $exec_file = array();
		static private $exec_file_exception = array();
	
		static private $ini_error_log;
		static private $ini_error_log_start_size;
	
		static public function output($type='stdout',$path=null){
			$error_report = null;
			switch($type){
				case 'xml':
					$source = self::result_xml('test_'.date('YmdHis'),self::error_report())->get('UTF-8');
					break;
				default:
					$source = self::result_str();
					$error_report = self::error_report();
			}
			if(!empty($path)){
				if(!empty($path)){
					$path = \testman\Util::path_absolute(getcwd(),$path);
					if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
					if(is_file($path)) unlink($path);
				}
				file_put_contents($path,$source);
			}else{
				print($source);
				if(!empty($error_report)) self::error_print($error_report);
			}
		}
		/**
		 * 結果を取得する
		 * @return string{}
		 */
		static public function get(){
			return self::$result;
		}
		/**
		 * テスト結果をXMLで取得する
		 */
		static public function result_xml($name=null,$system_err=null){
			$xml = new \testman\Xml('testsuites');
			if(!empty($name)) $xml->attr('name',$name);
	
			$count = $success = $fail = $none = $exception = 0;
			foreach(self::get() as $file => $f){
				$case = new \testman\Xml('testsuite');
				$case->close_empty(false);
				$case->attr('name',substr(basename($file),0,-4));
				$case->attr('file',$file);
	
				foreach($f as $class => $c){
					foreach($c as $method => $m){
						foreach($m as $line => $r){
							foreach($r as $l){
								$info = array_shift($l);
								$name = (($method != '@' && $method != $file) ? $method : '');
								$name .= (empty($name) ? '' : '_').((!empty($info[1]) && $info[1] != $file) ? $info[1] : ((!empty($info[0]) && $info[0] != $file) ? $info[0] : ''));
								$count++;
								$x = new \testman\Xml('testcase');
								$x->attr('name',$line.(empty($name) ? '' : '_').str_replace('\\','',$name));
								$x->attr('class',$class);
								$x->attr('file',$file);
								$x->attr('line',$line);
								$x->attr('time',$info[2]);
	
								switch(sizeof($l)){
									case 0:
										$success++;
										$case->add($x);
										break;
									case 1:
										$none++;
										break;
									case 2:
										$fail++;
										$failure = new \testman\Xml('failure');
										$failure->attr('line',$line);
										ob_start();
										var_dump($l[1]);
										$failure->value('Line. '.$line.' '.$method.': '."\n".ob_get_clean());
										$x->add($failure);
										$case->add($x);
										break;
									case 4:
										$exception++;
										$error = new \testman\Xml('error');
										$error->attr('line',$line);
										$error->value(
												'Line. '.$line.' '.$method.': '.$l[0]."\n".
												$l[1]."\n\n".$l[2].':'.$l[3]
										);
										$x->add($error);
										$case->add($x);
										break;
								}
							}
						}
					}
				}
				$xml->add($case);
			}
			$xml->attr('failures',$fail);
			$xml->attr('tests',$count);
			$xml->attr('errors',$exception);
			$xml->attr('skipped',$none);
			$xml->attr('time',round((microtime(true) - (float)self::$start_time),4));
			$xml->add(new \testman\Xml('system-out'));
			$xml->add(new \testman\Xml('system-err',$system_err));
			return $xml;
		}
		static public function result_str(){
			$result = '';
			$tab = '  ';
			$success = $fail = $none = 0;
	
			foreach(self::$result as $file => $f){
				foreach($f as $class => $c){
					$print_head = false;
	
					foreach($c as $method => $m){
						foreach($m as $line => $r){
							foreach($r as $l){
								$info = array_shift($l);
								switch(sizeof($l)){
									case 0:
										$success++;
										break;
									case 1:
										$none++;
										break;
									case 2:
										$fail++;
										if(!$print_head){
											$result .= "\n";
											$result .= (empty($class) ? "*****" : str_replace("\\",'.',(substr($class,0,1) == "\\") ? substr($class,1) : $class))." [ ".$file." ]\n";
											$result .= str_repeat("-",80)."\n";
											$print_head = true;
										}
										$result .= "[".$line."]".$method.": ".self::fcolor("fail","1;31")."\n";
										$result .= $tab.str_repeat("=",70)."\n";
										ob_start();
										var_dump($l[0]);
										$result .= self::fcolor($tab.str_replace("\n","\n".$tab,ob_get_contents()),"33");
										ob_end_clean();
										$result .= "\n".$tab.str_repeat("=",70)."\n";
	
										ob_start();
										var_dump($l[1]);
										$result .= self::fcolor($tab.str_replace("\n","\n".$tab,ob_get_contents()),"31");
										ob_end_clean();
										$result .= "\n".$tab.str_repeat("=",70)."\n";
										break;
									case 4:
										$fail++;
										if(!$print_head){
											$result .= "\n";
											$result .= (empty($class) ? "*****" : str_replace("\\",'.',(substr($class,0,1) == "\\") ? substr($class,1) : $class))." [ ".$file." ]\n";
											$result .= str_repeat("-",80)."\n";
											$print_head = true;
										}
										$color = ($l[0] == 'exception' || $l[0] == 'fail') ? 31 : 34;
										$result .= "[".$line."]".$method.": ".self::fcolor($l[0],"1;".$color)."\n";
										$result .= $tab.str_repeat("=",70)."\n";
										$result .= self::fcolor($tab.$l[1]."\n\n".$tab.$l[2].":".$l[3],$color);
										$result .= "\n".$tab.str_repeat("=",70)."\n";
										break;
								}
							}
						}
					}
				}
			}
			$result .= "\n";
			$result .= self::fcolor(" success: ".$success." ","7;32")." ".self::fcolor(" fail: ".$fail." ","7;31")." ".self::fcolor(" none: ".$none." ","7;35")
			.sprintf(' ( %s sec / %s MByte) ',round((microtime(true) - (float)self::$start_time),4),round(number_format((memory_get_usage() / 1024 / 1024),3),2));
			$result .= "\n";
			return $result;
		}
		public function __toString(){
			return self::stdout();
		}
		static public function init($entry_dir=null,$test_dir=null,$lib_dir=null,$func_dir=null){
			$path_format = function($path,$op=''){
				$path = empty($path) ? (str_replace('\\','/',getcwd()).'/'.$op) : $path;
				$path = str_replace('\\','/',$path);
				if(substr($path,-1) !== '/') $path = $path.'/';
				return $path;
			};
			self::$start_time = microtime(true);
			self::$ini_error_log = ini_get('error_log');
			self::$ini_error_log_start_size = (empty($ini_error_log) || !is_file($ini_error_log)) ? 0 : filesize($ini_error_log);
	
			self::$entry_dir = $path_format((empty($entry_dir) ? getcwd() : $entry_dir));
			self::$test_dir = empty($test_dir) ? self::$entry_dir.'test/' : $path_format($test_dir);
			self::$lib_dir = empty($lib_dir) ? self::$entry_dir.'lib/' : $path_format($lib_dir);
			self::$func_dir = empty($func_dir) ? self::$entry_dir.'func/' : $path_format($func_dir);
	
			set_include_path(get_include_path().PATH_SEPARATOR.self::$lib_dir);
			if(is_dir(self::$func_dir)){
				foreach(new \RecursiveDirectoryIterator(
						self::$func_dir,
						\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
				) as $f){
					if(substr($f->getFilename(),-4) == '.php' &&
							strpos($f->getPathname(),'/.') === false &&
							strpos($f->getPathname(),'/_') === false
					){
						try{
							include_once($f->getPathname());
						}catch(Exception $e){
						}
					}
				}
			}
		}
		static public function get_dir(){
			return array(static::$entry_dir,static::$test_dir,static::$lib_dir,static::$func_dir);
		}
		static public function info(){
			print(str_repeat(' ',2).'ENTRY_PATH:'.self::$entry_dir.PHP_EOL);
			print(str_repeat(' ',2).'LIB_PATH:'.self::$lib_dir.PHP_EOL);
			print(str_repeat(' ',2).'TEST_PATH:'.self::$test_dir.PHP_EOL);
			print(str_repeat(' ',2).'FUNC_PATH:'.self::$func_dir.PHP_EOL);
			print(str_repeat('-',80).PHP_EOL);
			print(str_repeat(' ',2).'INCLUDE_PATH:'.PHP_EOL);
			foreach(explode(PATH_SEPARATOR,get_include_path()) as $inc){
				print(str_repeat(' ',4).$inc.PHP_EOL);
			}
			print(str_repeat('-',80).PHP_EOL);
		}
		static public function error_report(){
			if(!empty($exceptions)){
				foreach($exceptions as $k => $e) self::error_print($k.': '.$e);
			}
			$ini_error_log_end_size = (empty($ini_error_log) || !is_file($ini_error_log)) ? 0 : filesize($ini_error_log);
			return ($ini_error_log_end_size != self::$ini_error_log_start_size) ? file_get_contents($ini_error_log,false,null,self::$ini_error_log_start_size) : null;
		}
		/**
		 * 現在実行中のエントリ
		 * @return string
		 */
		static public function current_entry(){
			return self::$current_entry;
		}
		/**
		 * 実行中のテスト名
		 */
		static public function current_name(){
			$dir = array(self::$entry_dir,self::$test_dir,self::$lib_dir);
			rsort($dir);
			$name = self::$current_file;
			foreach($dir as $f) $name = str_replace($f,'',$name);
			if(!empty(self::$current_class)) $name = $name.'@'.(self::$current_class);
			if(!empty(self::$current_method) && self::$current_method != '@') $name = $name.'#'.(self::$current_method);
			return $name;
		}
		static private function current_block_info(){
			return array(self::$current_block_name,self::$current_block_label,round((microtime(true) - (float)self::$current_block_start_time),4));
		}
		static private function expvar($var){
			if(is_numeric($var)) return strval($var);
			if(is_object($var)) $var = get_object_vars($var);
			if(is_array($var)){
				foreach($var as $key => $v){
					$var[$key] = self::expvar($v);
				}
			}
			return $var;
		}
		/**
		 * 判定を行う
		 * @param mixed $arg1 期待値
		 * @param mixed $arg2 実行結果
		 * @param boolean 真偽どちらで判定するか
		 * @param int $line 行番号
		 * @param string $file ファイル名
		 * @return boolean
		 */
		static public function equals($arg1,$arg2,$eq,$line,$file=null){
			$result = ($eq) ? (self::expvar($arg1) === self::expvar($arg2)) : (self::expvar($arg1) !== self::expvar($arg2));
			self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = ($result) ? array(self::current_block_info()) : array(self::current_block_info(),var_export($arg1,true),var_export($arg2,true));
			return $result;
		}
		/**
		 * メッセージを登録
		 * @param string $msg メッセージ
		 * @param int $line 行番号
		 * @param string $file ファイル名
		 */
		static public function notice($msg,$line,$file=null){
			self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = array(self::current_block_info(),'notice',$msg,$file,$line);
		}
		/**
		 * 失敗を登録
		 * @param string $msg メッセージ
		 * @param int $line 行番号
		 * @param string $file ファイル名
		 */
		static public function fail($line,$file=null){
			self::$result[(empty(self::$current_file) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = array(self::current_block_info(),'fail','failure',$file,$line);
		}
		static private function dir_run($tests_path,$path,$print_progress,$include_tests){
			if(is_dir($f=Util::path_absolute($tests_path,str_replace('.','/',$path)))){
				foreach(new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator(
								$f,
								\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
						),
						\RecursiveIteratorIterator::SELF_FIRST
				) as $e){
					if($e->isFile() && substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false){
						self::run($e->getPathname(),null,null,$print_progress,$include_tests);
					}
				}
				return true;
			}
			return false;
		}
		/**
		 * テストを実行する
		 * @param string $class_name クラス名
		 * @param string $method メソッド名
		 * @param string $block_name ブロック名
		 * @param boolean $print_progress 実行中のブロック名を出力するか
		 * @param boolean $include_tests testsディレクトリも参照するか
		 */
		static private function run($class_name,$method_name=null,$block_name=null,$print_progress=false,$include_tests=false){
			list($entry_path,$tests_path) = array(self::$entry_dir,self::$test_dir);
			if($class_name == __FILE__) return new self();
				
			if(is_file($class_name)){
				$doctest = (strpos($class_name,$tests_path) === false) ? self::get_entry_doctest($class_name) : self::get_unittest($class_name);
			}else if(is_file($f=Util::path_absolute($entry_path,$class_name.'.php'))){
				$doctest = self::get_entry_doctest($f);
			}else if(is_file($f=Util::path_absolute($tests_path,str_replace('.','/',$class_name).'.php'))){
				$doctest = self::get_unittest($f);
			}else if(is_file($f=Util::path_absolute($tests_path,$class_name))){
				$doctest = self::get_unittest($f);
			}else if(class_exists($f=((substr($class_name,0,1) != "\\") ? "\\" : '').str_replace('.',"\\",$class_name),true)
					|| interface_exists($f,true)
					|| (function_exists('trait_exists') && trait_exists($f,true))
			){
				if(empty($method_name)) self::dir_run($tests_path,$class_name,$print_progress,$include_tests);
				$doctest = self::get_doctest($f);
			}else if(function_exists($f)){
				self::dir_run($tests_path,$class_name,$print_progress,$include_tests);
				$doctest = self::get_func_doctest($f);
			}else if(self::dir_run($tests_path,$class_name,$print_progress,$include_tests)){
				return new self();
			}else{
				throw new \ErrorException($class_name.' test not found');
			}
			self::$current_file = $doctest['filename'];
			self::$current_class = ($doctest['type'] == 1) ? $doctest['name'] : null;
			self::$current_entry = ($doctest['type'] == 2 || $doctest['type'] == 3) ? $doctest['name'] : null;
			self::$current_method = null;
	
			foreach($doctest['tests'] as $test_method_name => $tests){
				if($method_name === null || $method_name === $test_method_name){
					self::$current_method = $test_method_name;
	
					if(empty($tests['blocks'])){
						self::$result[self::$current_file][self::$current_class][self::$current_method][$tests['line']][] = array(self::current_block_info(),'none');
					}else{
						foreach($tests['blocks'] as $test_block){
							list($name,$label,$block) = $test_block;
							$exec_block_name = ' #'.(($class_name == $name) ? '' : $name);
							self::$current_block_name = $name;
							self::$current_block_label = $label;
							self::$current_block_start_time = microtime(true);
	
							if($block_name === null || $block_name === $name){
								if($print_progress && substr(PHP_OS,0,3) != 'WIN') self::stdout($exec_block_name);
								try{
									ob_start();
									if($doctest['type'] == 3){
										self::include_setup_teardown($doctest['filename'],'__setup__.php');
										include($doctest['filename']);
										self::include_setup_teardown($doctest['filename'],'__teardown__.php');
									}else{
										if(isset($doctest['tests']['@']['__setup__'])) eval($doctest['tests']['@']['__setup__'][2]);
										eval($block);
										if(isset($doctest['tests']['@']['__teardown__'])) eval($doctest['tests']['@']['__teardown__'][2]);
									}
									$result = ob_get_clean();
									if(preg_match("/(Parse|Fatal) error:.+/",$result,$match)){
										$err = (preg_match('/syntax error.+code on line\s*(\d+)/',$result,$line) ?
												'Parse error: syntax error '.$doctest['filename'].' code on line '.$line[1]
												: $match[0]);
										throw new \ErrorException($err);
									}
								}catch(Exception $e){
									if(ob_get_level() > 0) $result = ob_get_clean();
									list($message,$file,$line) = array($e->getMessage(),$e->getFile(),$e->getLine());
									$trace = $e->getTrace();
									$eval = false;
	
									foreach($trace as $k => $t){
										if(isset($t['class']) && isset($t['function']) && ($t['class'].'::'.$t['function']) == __METHOD__ && isset($trace[$k-2])
												&& isset($trace[$k-1]['file']) && $trace[$k-1]['file'] == __FILE__ && isset($trace[$k-1]['function']) && $trace[$k-1]['function'] == 'eval'
										){
											$file = self::$current_file;
											$line = $trace[$k-2]['line'];
											$eval = true;
											break;
										}
									}
									if(!$eval && isset($trace[0]['file']) && self::$current_file == $trace[0]['file']){
										$file = $trace[0]['file'];
										$line = $trace[0]['line'];
									}
									self::$result[self::$current_file][self::$current_class][self::$current_method][$line][] = array(self::current_block_info(),'exception',$message,$file,$line);
								}
								if($print_progress && substr(PHP_OS,0,3) != 'WIN') self::stdout("\033[".strlen($exec_block_name).'D'."\033[0K");
							}
							self::$current_block_name = self::$current_block_label = null;
						}
					}
				}
			}
			if($include_tests && ($doctest['type'] == 1 || $doctest['type'] == 2)){
				$test_name = ($doctest['type'] == 1) ? str_replace("\\",'/',substr($doctest['name'],1)) : $doctest['name'];
				if(!empty($test_name) && is_dir($d=($tests_path.str_replace(array('.'),'/',$test_name)))){
					foreach(new \RecursiveDirectoryIterator($d,\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $e){
						if(substr($e->getFilename(),-4) == '.php' && strpos($e->getPathname(),'/.') === false && strpos($e->getPathname(),'/_') === false
								&& ($block_name === null || $block_name === substr($e->getFilename(),0,-4) || $block_name === $e->getFilename())
						){
							self::run($e->getPathname(),null,null,$print_progress,$include_tests);
						}
					}
				}
			}
			return new self();
		}
		static private function include_setup_teardown($test_file,$include_file){
			if(strpos($test_file,self::$test_dir) === 0){
				if(is_file(self::$test_dir.'__funcs__.php')) include_once(self::$test_dir.'__funcs__.php');
				$inc = array();
				$dir = dirname($test_file);
				while($dir.'/' != self::$test_dir){
					if(is_file($f=($dir.'/'.$include_file))) array_unshift($inc,$f);
					$dir = dirname($dir);
				}
				if(is_file($f=(self::$test_dir.$include_file))) array_unshift($inc,$f);
				foreach($inc as $i) include($i);
			}else if(is_file($f=(dirname($test_file).'/__setup__.php'))){
				include($f);
			}
		}
		static private function get_unittest($filename){
			$result = array();
			$result['@']['line'] = 0;
			$result['@']['blocks'][] = array($filename,null,$filename,0);
			$name = (preg_match("/^".preg_quote(self::$test_dir,'/')."(.+)\/[^\/]+\.php$/",$filename,$match)) ? $match[1] : null;
			return array('filename'=>$filename,'type'=>3,'name'=>$name,'tests'=>$result);
		}
		static private function get_entry_doctest($filename){
			$result = array();
			$entry = basename($filename,'.php');
			$src = file_get_contents($filename);
			if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$src,$doctests,PREG_OFFSET_CAPTURE)){
				foreach($doctests[0] as $doctest){
					if(isset($doctest[0][5]) && $doctest[0][5] != '*'){
						$test_start_line = sizeof(explode("\n",substr($src,0,$doctest[1]))) - 1;
						$test_block = str_repeat("\n",$test_start_line).preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array("/"."***","*"."/"),"",$doctest[0]));
						$test_block_name = preg_match("/^[\s]*#([^#].*)/",trim($test_block),$match) ? trim($match[1]) : null;
						$test_block_label = preg_match("/^[\s]*##(.+)/m",trim($test_block),$match) ? trim($match[1]) : null;
						if(trim($test_block) == '') $test_block = null;
						$result['@']['line'] = $test_start_line;
						$result['@']['blocks'][] = array($test_block_name,$test_block_label,$test_block,$test_start_line);
					}
				}
				self::merge_setup_teardown($result);
			}
			return array('filename'=>$filename,'type'=>2,'name'=>$entry,'tests'=>$result);
		}
		static private function get_func_doctest($func_name){
			$result = array();
			$r = new \ReflectionFunction($func_name);
			$filename = ($r->getFileName() === false) ? $func_name : $r->getFileName();
	
			if(is_string($r->getFileName())){
				$src_lines = file($filename);
				$func_src = implode('',array_slice($src_lines,$r->getStartLine()-1,$r->getEndLine()-$r->getStartLine(),true));
	
				if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$func_src,$doctests,PREG_OFFSET_CAPTURE)){
					foreach($doctests[0] as $doctest){
						if(isset($doctest[0][5]) && $doctest[0][5] != "*"){
							$test_start_line = $r->getStartLine() + substr_count(substr($func_src,0,$doctest[1]),"\n") - 1;
							$test_block = str_repeat("\n",$test_start_line).preg_replace("/([^\w_])self\(/ms","\\1".$func_name.'(',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array("/"."***","*"."/"),"",$doctest[0])));
							$test_block_name = preg_match("/^[\s]*#([^#].*)/",trim($test_block),$match) ? trim($match[1]) : null;
							$test_block_label = preg_match("/^[\s]*##(.+)/m",trim($test_block),$match) ? trim($match[1]) : null;
							if(trim($test_block) == '') $test_block = null;
							$result[$func_name]['line'] = $r->getStartLine();
							$result[$func_name]['blocks'][] = array($test_block_name,$test_block_label,$test_block,$test_start_line);
						}
					}
				}else if($func_name[0] != '_'){
					$result[$func_name]['line'] = $r->getStartLine();
					$result[$func_name]['blocks'] = array();
				}
			}
			return array('filename'=>$filename,'type'=>4,'name'=>null,'tests'=>$result);
		}
		static private function get_doctest($class_name){
			$result = array();
			$rc = new \ReflectionClass($class_name);
			$filename = $rc->getFileName();
			$class_src_lines = file($filename);
			$class_src = implode('',$class_src_lines);
	
			foreach($rc->getMethods() as $method){
				if($method->getDeclaringClass()->getName() == $rc->getName()){
					$method_src = implode('',array_slice($class_src_lines,$method->getStartLine()-1,$method->getEndLine()-$method->getStartLine(),true));
					$result = array_merge($result,self::get_method_doctest($rc->getName(),$method->getName(),$method->getStartLine(),$method->isPublic(),$method_src));
					$class_src = str_replace($method_src,str_repeat("\n",sizeof(explode("\n",$method_src)) - 1),$class_src);
				}
			}
			$result = array_merge($result,self::get_method_doctest($rc->getName(),'@',1,false,$class_src));
			self::merge_setup_teardown($result);
			return array('filename'=>$filename,'type'=>1,'name'=>$rc->getName(),'tests'=>$result);
		}
		static private function merge_setup_teardown(&$result){
			if(isset($result['@']['blocks'])){
				foreach($result['@']['blocks'] as $k => $block){
					if($block[0] == '__setup__' || $block[0] == '__teardown__'){
						$result['@'][$block[0]] = array($result['@']['blocks'][$k][3],null,$result['@']['blocks'][$k][2]);
						unset($result['@']['blocks'][$k]);
					}
				}
			}
		}
		static private function get_method_doctest($class_name,$method_name,$method_start_line,$is_public,$method_src){
			$result = array();
			if(preg_match_all("/\/\*\*"."\*.+?\*\//s",$method_src,$doctests,PREG_OFFSET_CAPTURE)){
				foreach($doctests[0] as $doctest){
					if(isset($doctest[0][5]) && $doctest[0][5] != "*"){
						$test_start_line = $method_start_line + substr_count(substr($method_src,0,$doctest[1]),"\n") - 1;
						$test_block = str_repeat("\n",$test_start_line).str_replace(array('self::','new self(','extends self{'),array($class_name.'::','new '.$class_name.'(','extends '.$class_name.'{'),preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/"."***","*"."/"),"",$doctest[0])));
						$test_block_name = preg_match("/^[\s]*#([^#].*)/",trim($test_block),$match) ? trim($match[1]) : null;
						$test_block_label = preg_match("/^[\s]*##(.+)/m",trim($test_block),$match) ? trim($match[1]) : null;
						if(trim($test_block) == '') $test_block = null;
						$result[$method_name]['line'] = $method_start_line;
						$result[$method_name]['blocks'][] = array($test_block_name,$test_block_label,$test_block,$test_start_line);
					}
				}
			}else if($is_public && $method_name[0] != '_'){
				$result[$method_name]['line'] = $method_start_line;
				$result[$method_name]['blocks'] = array();
			}
			return $result;
		}
		/**
		 * URL情報
		 * @return array
		 */
		static public function urls(){
			return (isset(self::$urls)) ? self::$urls : array();
		}
		/**
		 * URL情報を定義する
		 * @param array $urls
		 */
		static public function set_urls(array $urls){
			if(!isset(self::$urls)) self::$urls = $urls;
		}
	
	
		static private function fcolor($msg,$color='30'){
			return (php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg;
		}
	
		static public function verify_format($class_name,$m=null,$b=null,$include_tests=false){
			$f = ' '.$class_name.(isset($m) ? '::'.$m : '');
			self::stdout($f);
			$throw = null;
			$starttime = microtime(true);
			try{
				self::run($class_name,$m,$b,true,$include_tests);
			}catch(Exception $e){
				$throw = $e;
			}
			self::stdout('('.round((microtime(true) - (float)$starttime),4).' sec)'.PHP_EOL);
			if(isset($throw)) throw $throw;
			\testman\Coverage::save(true);
		}
		static public function error_print($msg,$color='1;31'){
			self::stdout(((php_sapi_name() == 'cli' && substr(PHP_OS,0,3) != 'WIN') ? "\033[".$color."m".$msg."\033[0m" : $msg).PHP_EOL);
		}
		static public function run_lib($on_disp){
			if(is_dir(self::$lib_dir)){
				foreach(new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator(
								self::$lib_dir,
								\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
						),
						\RecursiveIteratorIterator::SELF_FIRST
				) as $f){
					if(
							ctype_upper(substr($f->getFilename(),0,1))
							&& substr($f->getFilename(),-4) == '.php'
							&& strpos($f->getPathname(),'/.') === false
							&& strpos($f->getPathname(),'/_') === false
							&& !in_array($f->getPathname(),self::$exec_file)
					){
						$class_file = str_replace(self::$lib_dir,'',substr($f->getPathname(),0,-4));
						if(preg_match("/^(.*)\/(\w+)\/(\w+)\.php$/",$f->getPathname(),$m) && $m[2] == $m[3]) $class_file = dirname($class_file);
						if(!preg_match('/[A-Z]/',dirname($class_file))){
							$class_name = "\\".str_replace('/',"\\",$class_file);
	
							try{
								self::verify_format($class_name,null,null,false,$on_disp);
								self::$exec_file[] = $f->getPathname();
							}catch(Exception $e){
								self::$exec_file_exception[$class_name] = $e->getMessage().PHP_EOL.PHP_EOL.$e->getTraceAsString();
							}
						}
					}
				}
			}
		}
		static public function run_entry($on_disp=false){
			if(is_dir(self::$entry_dir)){
				$pre = getcwd();
				chdir(self::$entry_dir);
				foreach(new \RecursiveDirectoryIterator(
						self::$entry_dir,
						\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
				) as $f){
					if(substr($f->getFilename(),-4) == '.php' &&
							strpos($f->getPathname(),'/.') === false &&
							strpos($f->getPathname(),'/_') === false
					){
						$src = file_get_contents($f->getFilename());
						try{
							self::verify_format($f->getPathname(),null,null,false,$on_disp);
							self::$exec_file[] = $f->getPathname();
						}catch(Exception $e){
							self::$exec_file_exception[$f->getFilename()] = $e->getMessage().PHP_EOL.PHP_EOL.$e->getTraceAsString();
						}
					}
				}
				chdir($pre);
			}
		}
		static public function run_test($on_disp){
			if(is_dir(self::$test_dir)){
				foreach(new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator(
								self::$test_dir,
								\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
						),
						\RecursiveIteratorIterator::SELF_FIRST
				) as $f){
					if($f->isFile() &&
							substr($f->getFilename(),-4) == '.php' &&
							strpos($f->getPathname(),'/.') === false &&
							strpos($f->getPathname(),'/_') === false
					){
						try{
							self::verify_format($f->getPathname(),null,null,false,$on_disp);
							self::$exec_file[] = $f->getPathname();
						}catch(Exception $e){
							$exceptions[$f->getFilename()] = implode('',$e->getTrace());
						}
					}
				}
			}
		}
		static public function run_func($on_disp){
			$funcs = get_defined_functions();
			foreach($funcs['user'] as $func_name){
				$r = new \ReflectionFunction($func_name);
				if(dirname($r->getFileName()) != __DIR__){
					self::verify_format($func_name,null,null,false,$on_disp);
				}
			}
		}
		static public function run_all($on_disp){
			self::run_func($on_disp);
			self::run_lib($on_disp);
			self::run_test($on_disp);
			self::run_entry($on_disp);
		}
		static public function stdout($v){
			print($v);
		}
	}	
}



namespace{
	// common
	ini_set('display_errors','On');
	ini_set('html_errors','Off');
	ini_set('xdebug.var_display_max_children',-1);
	ini_set('xdebug.var_display_max_data',-1);
	ini_set('xdebug.var_display_max_depth',-1);
	ini_set('error_reporting',E_ALL);
	
	if(ini_get('date.timezone') == ''){
		date_default_timezone_set('Asia/Tokyo');
	}
	if(extension_loaded('mbstring')){
		if('neutral' == mb_language()) mb_language('Japanese');
		mb_internal_encoding('UTF-8');
	}
	set_error_handler(function($n,$s,$f,$l){
		throw new \ErrorException($s,0,$n,$f,$l);
	});	
	
	
	// test functions
	if(!function_exists('eq')){
		function r($obj){
			return $obj;
		}
		/**
		 *　等しい
		 * @param mixed $expectation 期待値
		 * @param mixed $result 実行結果
		 * @return boolean 期待通りか
		 */
		function eq($expectation,$result){
			list($debug) = debug_backtrace(false);
			return \testman\TestRunner::equals($expectation,$result,true,$debug["line"],$debug["file"]);
		}
		/**
		 * 等しくない
		 * @param mixed $expectation 期待値
		 * @param mixed $result 実行結果
		 * @return boolean 期待通りか
		 */
		function neq($expectation,$result){
			list($debug) = debug_backtrace(false);
			return \testman\TestRunner::equals($expectation,$result,false,$debug["line"],$debug["file"]);
		}
		/**
		 *　文字列中に指定した文字列がすべて存在していれば成功
		 * @param string $keyword スペース区切りで複数可能
		 * @param string $src
		 * @return boolean
		 */
		function meq($keyword,$src){
			list($debug) = debug_backtrace(false);
			foreach(explode(' ',$keyword) as $q){
				if(mb_strpos($src,$q) === false) return \testman\TestRunner::equals(true,false,true,$debug['line'],$debug['file']);
			}
			return \testman\TestRunner::equals(true,true,true,$debug['line'],$debug['file']);
		}
		/**
		 *　文字列中に指定した文字列がすべて存在していなければ成功
		 * @param string $keyword スペース区切りで複数可能
		 * @param string $src
		 * @return boolean
		 */
		function nmeq($keyword,$src){
			list($debug) = debug_backtrace(false);
			foreach(explode(' ',$keyword) as $q){
				if(mb_strpos($src,$q) !== false) return \testman\TestRunner::equals(true,false,true,$debug['line'],$debug['file']);
			}
			return \testman\TestRunner::equals(true,true,true,$debug['line'],$debug['file']);
		}
		/**
		 * 成功
		 */
		function success(){
			list($debug) = debug_backtrace(false);
			\testman\TestRunner::equals(true,true,true,$debug['line'],$debug['file']);
		}
		/**
		 * 失敗
		 */
		function fail($msg=null){
			list($debug) = debug_backtrace(false);
			\testman\TestRunner::fail($debug['line'],$debug['file']);
		}
		/**
		 * メッセージ
		 */
		function notice($msg=null){
			list($debug) = debug_backtrace(false);
			if(is_array($msg)){
				ob_start();
				var_dump($msg);
				$msg = ob_get_clean();
			}
			\testman\TestRunner::notice((($msg instanceof Exception) ? $msg->getMessage()."\n\n".$msg->getTraceAsString() : (string)$msg),$debug['line'],$debug['file']);
		}
		/**
		 * ユニークな名前でクラスを生成しインスタンスを返す
		 * @param string $class クラスのソース
		 * @return object
		 */
		function newclass($class){
			$class_name = '_';
			foreach(debug_backtrace() as $d) $class_name .= (empty($d['file'])) ? '' : '__'.basename($d['file']).'_'.$d['line'];
			$class_name = substr(preg_replace("/[^\w]/","",str_replace('.php','',$class_name)),0,100);
	
			for($i=0,$c=$class_name;;$i++,$c=$class_name.'_'.$i){
				if(!class_exists($c)){
					$args = func_get_args();
					array_shift($args);
					$doc = null;
					if(strpos($class,'-----') !== false){
						list($doc,$class) = preg_split("/----[-]+/",$class,2);
						$doc = "/**\n".trim($doc)."\n*/\n";
					}
					call_user_func(create_function('',$doc.vsprintf(preg_replace("/\*(\s+class\s)/","*/\\1",preg_replace("/class\s\*/",'class '.$c,trim($class))),$args)));
					return new $c;
				}
			}
		}
		/**
		 * ヒアドキュメントのようなテキストを生成する
		 * １行目のインデントに合わせてインデントが消去される
		 * @param string $text 対象の文字列
		 * @return string
		 */
		function pre($text){
			if(!empty($text)){
				$lines = explode("\n",$text);
				if(sizeof($lines) > 2){
					if(trim($lines[0]) == '') array_shift($lines);
					if(trim($lines[sizeof($lines)-1]) == '') array_pop($lines);
					return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode("\n",$lines)) : implode("\n",$lines);
				}
			}
			return $text;
		}
		/**
		 * mapに定義されたurlをフォーマットして返す
		 * @param string $name
		 * @return string
		 */
		function test_map_url($map_name){
			$urls = \testman\TestRunner::urls();
			$args = func_get_args();
			array_shift($args);
	
			if(empty($urls)){
				if(strpos($map_name,'::') !== false) throw new \RuntimeException($map_name.' not found');
				return 'http://localhost/'.basename(getcwd()).'/'.$map_name.'.php';
			}else{
				$map_name = (strpos($map_name,'::') === false) ? (preg_replace('/^([^\/]+)\/.+$/','\\1',\testman\TestRunner::current_entry()).'::'.$map_name) : $map_name;
				if(isset($urls[$map_name]) && substr_count($urls[$map_name],'%s') == sizeof($args)) return vsprintf($urls[$map_name],$args);
				throw new \RuntimeException($map_name.(isset($urls[$map_name]) ? '['.sizeof($args).']' : '').' not found');
			}
		}
		/**
		 * Httpリクエスト
		 * @return org.rhaco.net.Http
		 */
		function b($agent=null,$timeout=30,$redirect_max=20){
			$b = new \testman\Http($agent,$timeout,$redirect_max);
			return $b;
		}
		/**
		 * XMLで取得する
		 * @param $xml 取得したXmlオブジェクトを格納する変数
		 * @param $src 対象の文字列
		 * @param $name ノード名
		 * @return boolean
		 */
		function xml(&$xml,$src,$name=null){
			return \testman\Xml::set($xml,$src,$name);
		}
	}

	
	
	
	// mode select
	if(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_METHOD'])){
		// WEB
		$media_bin = array();
		$media_bin['/splash.jpg'] = <<< 'FILE'
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
/4JUsb+xhOWIOsyyvz2r6z9dEG5n/h8AbQYv
FILE;
		
		if(isset($_GET['media'])){
			$media = $_GET['media'];
			
			if(isset($media_bin[$media])){
				$mime = 'application/octet-stream';	
				if(preg_match('/.+\.(\w+)$/',$media,$m)){
					switch($m[1]){
						case 'jpg':
						case 'jpeg': $mime = 'image/jpeg;'; break;
						case 'html':  $mime = 'text/html'; break;
					}
					header('Content-Type: '.$mime.';');					
				}
				print(gzuncompress(base64_decode(trim(str_replace(PHP_EOL,'',$media_bin[$media])))));
				exit;
			}
			header('HTTP/1.1 404 Not Found');
			exit;			
		}
		
		$in_value = function($key,$default=null){
			$params = isset($_GET) ? $_GET : array();
			if(!isset($params[$key])) return $default;
			return $params[$key];
		};
		// TODO
		$report_dir = getcwd().'/report';
		$dblist = array();
		if(is_dir($report_dir)){
			foreach(new RecursiveDirectoryIterator(
					$report_dir,
					\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
			) as $e){
				if(preg_match('/^.+\.report$/',$e->getFilename())){
					$db_path = str_replace($report_dir.'/','',$e->getFilename());
					$dblist[$db_path] = $e->getMTime();
				}
			}
			if(!empty($dblist)){
				arsort($dblist);
				$dblist = array_combine(array_keys($dblist),array_keys($dblist));
			}
		}
		try{
			$uri = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
			$db = $in_value('db');

			if(empty($db) && !empty($dblist)) $db = current($dblist);
			$db_file = \testman\Util::path_absolute($report_dir,$db);
			$template = new \testman\Template($uri.'?media=');

			switch($in_value('view_mode')){
				case 'source':
					if($in_value('file') != ''){
						$template->vars('info',\testman\Coverage::file($db_file,$in_value('file')));
						$template->vars('file',$in_value('file'));
						$template_file = 'source.html';
					}
					break;
				case 'result':
					list($success,$fail,$none,$failure) = \testman\Coverage::test_result($db_file);
					$template->vars('success',$success);
					$template->vars('fail',$fail);
					$template->vars('none',$none);
					$template->vars('failure',$failure);
					$template_file = 'test_result.html';
					break;
				case 'all':
					list($file_list,$avg) = \testman\Coverage::all_file_list($db_file);
					$template->vars('file_list',$file_list);
					$template->vars('avg',$avg);
					$template_file = 'coverage.html';
					break;
				case 'tree':
					$path = $in_value('path');
					list($dir_list,$file_list,$parent_path,$avg) = \testman\Coverage::dir_list($db_file,$path);
						
					$template->vars('dir_list',$dir_list);
					$template->vars('file_list',$file_list);
					$template->vars('parent_path',$parent_path);
					$template->vars('avg',$avg);
					$template->vars('path',$path);
					$template_file = 'coverage.html';
					break;
				case 'help':
					$template_file = 'help.html';
					break;
			}
			if(empty($template_file)) $template_file = 'top.html';
			$template->vars('dblist',$dblist);
			$template->vars('db',$db);
			$template->vars('view_mode',$in_value('view_mode'));
			$template->output(__DIR__.'/templates/'.$template_file);
		}catch(Exception $e){
			die($e->getMessage());
		}
	}else{
		// CLI
		$argv = array_slice($_SERVER['argv'],1);
		$value = (empty($argv)) ? null : array_shift($argv);
		$params = array();
		
		if(substr($value,0,1) == '-'){
			array_unshift($argv,$value);
			$value = null;
		}
		for($i=0;$i<sizeof($argv);$i++){
			if($argv[$i][0] == '-'){
				$k = substr($argv[$i],1);
				$v = (isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : '';
				if(isset($params[$k]) && !is_array($params[$k])) $params[$k] = array($params[$k]);
				$params[$k] = (isset($params[$k])) ? array_merge($params[$k],array($v)) : $v;
			}
		}
		if(isset($params['encode'])){
			$p = realpath($params['encode']);
			if($p === false) throw \RuntimeException($params['encode'].' not found');
			$encode = chunk_split(base64_encode(gzcompress(file_get_contents($p))),100);
			print(PHP_EOL.$encode.PHP_EOL);
			exit;
		}
		
		
		
		
		if(is_file($f=getcwd().'/bootstrap.php') || is_file($f=getcwd().'/vendor/autoload.php')){
			ob_start();
				include_once($f);
			ob_end_clean();
		}
		spl_autoload_register(function($c){
				$cp = str_replace('\\','/',(($c[0] == '\\') ? substr($c,1) : $c));
				foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
					if(!empty($p) && ($r = realpath($p)) !== false && $p !== '.'){
			
						if(is_file($f=($r.'/'.$cp.'.php')) || is_file($f=($r.'/'.$cp.'/'.basename($cp).'.php'))){
							require_once($f);
							if(class_exists($c,false) || interface_exists($c,false)) return true;
						}
					}
				}
				return false;
			},true,false
		);
			
			
		$entry_dir = $test_dir = $lib_dir = $func_dir = null;
		$urls = array();
				
		if(is_file($f=__DIR__.'/testman_urls.php')){
			$urls = include($f);
			if(!is_array($urls)) throw new \RuntimeException('urls was array');
		}
		if(isset($params['entry_dir'])) $entry_dir = realpath($entry_dir);
		if(isset($params['test_dir'])) $test_dir = realpath($test_dir);
		if(isset($params['lib_dir'])) $lib_dir = realpath($lib_dir);
		if(isset($params['func_dir'])) $func_dir = realpath($func_dir);
		if(!isset($entry_dir)) $entry_dir = __DIR__;
		
		// TODO
		if(isset($params['report'])){
			if(!extension_loaded('xdebug')) die('xdebug extension not loaded');
			
			$db = getcwd().'/report';
			if(!is_dir($db)) mkdir($db,0777,true);
			$db = $db.'/'.date('Ymd_His').(empty($value) ? '' : '_'.str_replace(array('\\','/'),'_',$value));
			if(isset($params['m'])) $db = $db.'_'.$params['m'];
			if(isset($params['b'])) $db = $db.'_'.$params['b'];
			$db = $db.'.report';

			\testman\Coverage::start($db,$entry_dir,$lib_dir);
		}
		
		\testman\TestRunner::init($entry_dir,$test_dir,$lib_dir,$func_dir);
		\testman\TestRunner::set_urls($urls);
		\testman\TestRunner::info();
				
		if(isset($value)){
			\testman\TestRunner::verify_format(
					$value
					,(isset($params['m']) ? $params['m'] : null)
					,(isset($params['b']) ? $params['b'] : null)
					,true
			);
		}else{
			\testman\TestRunner::run_all(true);
		}
		\testman\TestRunner::output();
	}
}
