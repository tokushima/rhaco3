<?php
if(extension_loaded('xdebug')){
	$coverage_vars = isset($_POST['_coverage_vars_']) ? $_POST['_coverage_vars_'] : 
						(isset($_GET['_coverage_vars_']) ? $_GET['_coverage_vars_'] : array());	
	if(isset($_POST['_coverage_vars_'])) unset($_POST['_coverage_vars_']);
	if(isset($_GET['_coverage_vars_'])) unset($_GET['_coverage_vars_']);

	if(isset($coverage_vars['savedb']) && is_file($coverage_vars['savedb'])){
		register_shutdown_function(function() use($coverage_vars){
			register_shutdown_function(function() use($coverage_vars){
				$savedb = $coverage_vars['savedb'];
				
				if(is_file($savedb) && $db = new \PDO('sqlite:'.$savedb)){
					$db->query('begin');
					foreach(xdebug_get_code_coverage() as $file_path => $lines){
						$sql = 'select id,covered_line,ignore_line,active_len from coverage_info where file_path = ?';
						$ps = $db->prepare($sql);
						$ps->execute(array($file_path));
						
						if($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
							$id = (int)$resultset['id'];
							$active_len = (int)$resultset['active_len'];
							$ignore_line = explode(',',$resultset['ignore_line']);
							
							$covered_line = empty($resultset['covered_line']) ? array() : explode(',',$resultset['covered_line']);
							$covered_line = array_merge(array_keys($lines),$covered_line);
							$covered_line = array_unique($covered_line);
							sort($covered_line);
							$coverd = implode(',',$covered_line);
							
							if($coverd !== $resultset['covered_line']){
								$covered_len = sizeof(array_diff($covered_line,$ignore_line));
								$percent = ($active_len === 0) ? 100 : (($covered_len === 0) ? 0 : (floor($covered_len / $active_len * 100)));
								if($percent > 100) $percent = 100;
								
								$ps = $db->prepare('update coverage_info set covered_line=?,percent=? where id=?');
								$ps->execute(array($coverd,$percent,$id));
							}
						}
					}
					$db->query('commit');
				}
				xdebug_stop_code_coverage();			
			});
		});
		xdebug_start_code_coverage();
	}
}