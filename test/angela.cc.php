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
					$target = array();
					$sql = 'select id,file_path,covered_line,uncovered_line from coverage_info';
					$ps = $db->prepare($sql);
					$ps->execute(array());
					
					while($resultset = $ps->fetch(\PDO::FETCH_ASSOC)){
						$target[$resultset['file_path']] = $resultset;
					}
					foreach(xdebug_get_code_coverage() as $file_path => $lines){
						if(isset($target[$file_path])){
							$t = $target[$file_path];
							$covered_line = empty($t['covered_line']) ? array() : explode(',',$t['covered_line']);
							$uncovered_line = empty($t['uncovered_line']) ? array() : explode(',',$t['uncovered_line']);
					
							foreach($lines as $line => $status){
								if($status == 1){
									$covered_line[] = $line;
								}else{
									$uncovered_line[] = $line;
								}
							}
							$covered_line = array_unique($covered_line);
							$uncovered_line = array_diff(array_unique($uncovered_line),$covered_line);
					
							$ps = $db->prepare('update coverage_info set covered_line=?,uncovered_line=?,exec=1 where id=?');
							$ps->execute(array(implode(',',$covered_line),implode(',',$uncovered_line),$t['id']));
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