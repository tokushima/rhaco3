<?php
namespace org\rhaco\flow\module;
use \org\rhaco\Xml;
/**
 * loopの古い表現
 * @author tokushima
 */
class Loop{
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
	private function rtif($src){
		if(strpos($src,'rt:if') !== false){
			while(Xml::set($tag,$src,'rt:if')){
				$tag->escape(false);
				if(!$tag->is_attr('param')) throw new \LogicException('if');
				$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));
	
				if($tag->is_attr('value')){
					$arg2 = $this->parse_plain_variable($tag->in_attr('value'));
					if($arg2 == 'true' || $arg2 == 'false' || preg_match('/^-?[0-9]+$/',(string)$arg2)){
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
						."<?php }catch(\\Exception \$e){ if(!isset(\$_nes_) && \$_display_exception_){print(\$e->getMessage());} } ?>"
						,$src
				);
			}
		}
		return $src;
	}
	private function rtunit($src){
		if(strpos($src,'rt:unit') !== false){
			while(Xml::set($tag,$src,'rt:unit')){
				$tag->escape(false);
				$uniq = uniqid('');
				$param = $tag->in_attr('param');
				$var = '$'.$tag->in_attr('var','_var_'.$uniq);
				$offset = $tag->in_attr('offset',1);
				$total = $tag->in_attr('total','_total_'.$uniq);
				$cols = ($tag->is_attr('cols')) ? (ctype_digit($tag->in_attr('cols')) ? $tag->in_attr('cols') : $this->variable_string($this->parse_plain_variable($tag->in_attr('cols')))) : 1;
				$rows = ($tag->is_attr('rows')) ? (ctype_digit($tag->in_attr('rows')) ? $tag->in_attr('rows') : $this->variable_string($this->parse_plain_variable($tag->in_attr('rows')))) : 0;
				$value = $tag->value();
	
				$cols_count = '$_ucount_'.$uniq;
				$cols_total = '$'.$tag->in_attr('cols_total','_cols_total_'.$uniq);
				$rows_count = '$'.$tag->in_attr('counter','_counter_'.$uniq);
				$rows_total = '$'.$tag->in_attr('rows_total','_rows_total_'.$uniq);
				$ucols = '$_ucols_'.$uniq;
				$urows = '$_urows_'.$uniq;
				$ulimit = '$_ulimit_'.$uniq;
				$ufirst = '$_ufirst_'.$uniq;
				$ufirstnm = '_ufirstnm_'.$uniq;
	
				$ukey = '_ukey_'.$uniq;
				$uvar = '_uvar_'.$uniq;
	
				$src = str_replace(
						$tag->plain(),
						sprintf('<?php %s=%s; %s=%s; %s=%s=1; %s=null; %s=%s*%s; %s=array(); ?>'
								.'<rt:loop param="%s" var="%s" key="%s" total="%s" offset="%s" first="%s">'
								.'<?php if(%s <= %s){ %s[$%s]=$%s; } ?>'
								.'<rt:first><?php %s=$%s; ?></rt:first>'
								.'<rt:last><?php %s=%s; ?></rt:last>'
								.'<?php if(%s===%s){ ?>'
								.'<?php if(isset(%s)){ $%s=""; } ?>'
								.'<?php %s=sizeof(%s); ?>'
								.'<?php %s=ceil($%s/%s); ?>'
								.'%s'
								.'<?php %s=array(); %s=null; %s=1; %s++; ?>'
								.'<?php }else{ %s++; } ?>'
								.'</rt:loop>'
								,$ucols,$cols,$urows,$rows,$cols_count,$rows_count,$ufirst,$ulimit,$ucols,$urows,$var
								,$param,$uvar,$ukey,$total,$offset,$ufirstnm
								,$cols_count,$ucols,$var,$ukey,$uvar
								,$ufirst,$ufirstnm
								,$cols_count,$ucols
								,$cols_count,$ucols
								,$ufirst,$ufirstnm
								,$cols_total,$var
								,$rows_total,$total,$ucols
								,$value
								,$var,$ufirst,$cols_count,$rows_count
								,$cols_count
						)
						.($tag->is_attr('rows') ?
								sprintf('<?php for(;%s<=%s;%s++){ %s=array(); ?>%s<?php } ?>',$rows_count,$rows,$rows_count,$var,$value) : ''
						)
						,$src
				);
			}
		}
		return $src;
		/***
			# unit
			$src = pre('
						<rt:unit param="abc" var="unit_list" cols="3" offset="2" counter="counter">
						<rt:first>FIRST</rt:first>{$counter}{
						<rt:loop param="unit_list" var="a"><rt:first>first</rt:first>{$a}<rt:last>last</rt:last></rt:loop>
						}
						<rt:last>LAST</rt:last>
						</rt:unit>
					');
			$result = pre('
							FIRST1{
							first234last}
							2{
							first567last}
							3{
							first8910last}
							LAST
						');
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array(1,2,3,4,5,6,7,8,9,10));
			eq($result,$t->get($src));
		*/
		/***
			# rows_fill
			$src = pre('<rt:unit param="abc" var="abc_var" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
			$result = '[123][400][000]';
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));
		
			$src = pre('<rt:unit param="abc" var="abc_var" offset="3" cols="3" rows="3">[<rt:loop param="abc_var" var="a" limit="3"><rt:fill>0<rt:else />{$a}</rt:fill></rt:loop>]</rt:unit>');
			$result = '[340][000][000]';
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array(1,2,3,4));
			eq($result,$t->get($src));
		*/
	}
	private function rtloop($src){
		if(strpos($src,'rt:loop') !== false){
			while(Xml::set($tag,$src,'rt:loop')){
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
				while(Xml::set($subtag,$value,'rt:loop')){
					$value = $this->rtloop($value);
				}
				while(Xml::set($subtag,$value,'rt:first')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$first
							,(($subtag->in_attr('last') === 'false') ? sprintf(' && (%s !== 1) ',$total) : '')
							,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:middle')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(!isset(%s) && !isset(%s)){ ?>%s<?php } ?>',$first,$last
							,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:last')){
					$value = str_replace($subtag->plain(),sprintf('<?php if(isset(%s)%s){ ?>%s<?php } ?>',$last
							,(($subtag->in_attr('first') === 'false') ? sprintf(' && (%s !== 1) ',$vtotal) : '')
							,preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$this->rtloop($subtag->value()))),$value);
				}
				while(Xml::set($subtag,$value,'rt:fill')){
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
								." }"
								." ?>"
								."<?php }catch(\\Exception \$e){ if(!isset(\$_nes_) && \$_display_exception_){print(\$e->getMessage());} } ?>"
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
						)
						,$src
				);
			}
		}
		return $src;
		/***
			$src = pre('
						<rt:loop param="abc" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							1: A => 456
							2: B => 789
							3: C => 010
							hoge
						');
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010"));
			eq($result,$t->get($src));
		*/
		/***
			# loop
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$src = pre('
						<rt:loop param="abc" offset="2" limit="2" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							2: B => 789
							3: C => 010
							hoge
						');
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
			eq($result,$t->get($src));
		*/
		/***
			# limit
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$src = pre('
						<rt:loop param="abc" offset="{$offset}" limit="{$limit}" loop_counter="loop_counter" key="loop_key" var="loop_var">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = pre('
							2: B => 789
							3: C => 010
							4: D => 999
							hoge
						');
			$t = new \org\rhaco\Template();
			$t->set_object_module(new self());
			$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999","E"=>"111"));
			$t->vars("offset",2);
			$t->vars("limit",3);
			eq($result,$t->get($src));
		*/
		/***
		 # range
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop range="0,5" var="var">{$var}</rt:loop>');
		$result = pre('012345');
		eq($result,$t->get($src));
	
		$src = pre('<rt:loop range="0,6" range_step="2" var="var">{$var}</rt:loop>');
		$result = pre('0246');
		eq($result,$t->get($src));
	
		$src = pre('<rt:loop range="A,F" var="var">{$var}</rt:loop>');
		$result = pre('ABCDEF');
		eq($result,$t->get($src));
		*/
		/***
		 # multi
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop range="1,2" var="a"><rt:loop range="1,2" var="b">{$a}{$b}</rt:loop>-</rt:loop>');
		$result = pre('1112-2122-');
		eq($result,$t->get($src));
		*/
		/***
		 # empty
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc">aaa</rt:loop>');
		$result = pre('');
		$t->vars("abc",array());
		eq($result,$t->get($src));
		*/
		/***
		 # total
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" total="total">{$total}</rt:loop>');
		$result = pre('4444');
		$t->vars("abc",array(1,2,3,4));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" total="total" offset="2" limit="2">{$total}</rt:loop>');
		$result = pre('44');
		$t->vars("abc",array(1,2,3,4));
		eq($result,$t->get($src));
		*/
		/***
		 # evenodd
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop range="0,5" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>');
		$result = pre('1[odd]2[even]3[odd]4[even]5[odd]6[even]');
		eq($result,$t->get($src));
		*/
		/***
		 # first_last
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var" first="first" last="last">{$first}{$var}{$last}</rt:loop>');
		$result = pre('first12345last');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var" first="first" last="last" offset="2" limit="2">{$first}{$var}{$last}</rt:loop>');
		$result = pre('first23last');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F</rt:first>[<rt:middle>{$var}</rt:middle>]<rt:last>L</rt:last></rt:loop>');
		$result = pre('F[][3][]L');
		$t->vars("abc",array(1,2,3,4,5,6));
		eq($result,$t->get($src));
		*/
		/***
		 # first_last_block
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var" offset="2" limit="3"><rt:first>F<rt:if param="var" value="1">I<rt:else />E</rt:if><rt:else />nf</rt:first>[<rt:middle>{$var}<rt:else />nm</rt:middle>]<rt:last>L<rt:else />nl</rt:last></rt:loop>');
	
		$result = pre('FE[nm]nlnf[3]nlnf[nm]L');
		$t->vars("abc",array(1,2,3,4,5,6));
		eq($result,$t->get($src));
		*/
		/***
		 # first_in_last
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var"><rt:last>L</rt:last></rt:loop>');
		$t->vars("abc",array(1));
		eq("L",$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var"><rt:last first="false">L</rt:last></rt:loop>');
		$t->vars("abc",array(1));
		eq("",$t->get($src));
		*/
		/***
		 # last_in_first
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var"><rt:first>F</rt:first></rt:loop>');
		$t->vars("abc",array(1));
		eq("F",$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="var"><rt:first last="false">F</rt:first></rt:loop>');
		$t->vars("abc",array(1));
		eq("",$t->get($src));
		*/
		/***
		 # difi
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" limit="10" shortfall="difi" var="var">{$var}{$difi}</rt:loop>');
		$result = pre('102030405064');
		$t->vars("abc",array(1,2,3,4,5,6));
		eq($result,$t->get($src));
		*/
		/***
		 # empty
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc">aaaaaa<rt:else />EMPTY</rt:loop>');
		$result = pre('EMPTY');
		$t->vars("abc",array());
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc">aaaaaa<rt:else>EMPTY</rt:loop>');
		$result = pre('EMPTY');
		$t->vars("abc",array());
		eq($result,$t->get($src));
		*/
		/***
		 # fill
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
		$result = pre('F45hogehogeL');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" offset="4" limit="4"><rt:fill><rt:first>f</rt:first>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill><rt:else />empty</rt:loop>');
		$result = pre('fhogehogehogehogeL');
		$t->vars("abc",array());
		eq($result,$t->get($src));
		*/
		/***
		 # fill_no_limit
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a"><rt:fill>hoge<rt:last>L</rt:last><rt:else /><rt:first>F</rt:first>{$a}</rt:fill></rt:loop>');
		$result = pre('F12345');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
		*/
		/***
		 # fill_last
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = pre('45hogeLast');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = pre('123Last');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
	
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" offset="6" limit="3"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:last>Last</rt:last></rt:loop>');
		$result = pre('hogehogehogeLast');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
		*/
		/***
		 # fill_first
		$t = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" limit="3" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:first>First</rt:first></rt:loop>');
		$result = pre('4First5hoge');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
		*/
		/***
		 # fill_middle
		$template = new \org\rhaco\Template();
		$t->set_object_module(new self());
		$src = pre('<rt:loop param="abc" var="a" limit="4" offset="4"><rt:fill>hoge<rt:else />{$a}</rt:fill><rt:middle>M</rt:middle></rt:loop>');
		$result = pre('45MhogeMhoge');
		$t->vars("abc",array(1,2,3,4,5));
		eq($result,$t->get($src));
		*/
	}
	
	public function before_template(\org\rhaco\lang\String $obj){
		$src = $obj->get();
		$src = $this->rtunit($src);
		$src = $this->rtloop($src);
		$obj->set($src);
	}
}
