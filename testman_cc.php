<?php
if(extension_loaded('xdebug')){
	$coverage_vars = isset($_POST['_testman_coverage_vars_']) ? $_POST['_testman_coverage_vars_'] : 
						(isset($_GET['_testman_coverage_vars_']) ? $_GET['_testman_coverage_vars_'] : array());	
	if(isset($_POST['_testman_coverage_vars_'])) unset($_POST['_testman_coverage_vars_']);
	if(isset($_GET['_testman_coverage_vars_'])) unset($_GET['_testman_coverage_vars_']);

	if(!empty($coverage_vars) && isset($coverage_vars['savedb']) && is_file($coverage_vars['savedb'])){
		register_shutdown_function(function() use($coverage_vars){
			if($db = new PDO('sqlite:'.$coverage_vars['savedb'])){
				$get_prepare = function($db,$sql){
					$ps = $db->prepare($sql);
					if($ps === false) throw new LogicException($sql);
					return $ps;
				};
				$db->beginTransaction();
				$getid_ps = $get_prepare($db,'select id,covered_line from coverage where file_path=?');
				$insert_ps = $get_prepare($db,'insert into coverage(file_path,covered_line,file_len,covered_len,src) values(?,?,?,?,?)');
				$update_ps = $get_prepare($db,'update coverage set covered_line=?,covered_len=? where id=?');
				$insert_exe_ps = $get_prepare($db,'insert into coverage_covered(file_path,covered_line,test_path) values(?,?,?)');
				
				foreach(xdebug_get_code_coverage() as $filepath => $lines){
					if(strpos($filepath,'phar://') !== 0 && strpos($filepath,'/_') === false && is_file($filepath)){
						$bool = empty($coverage_vars['target_dir']);
						if(!$bool){
							foreach($coverage_vars['target_dir'] as $dir){
								if(strpos($filepath,$dir) === 0){
									$bool = true;
									break;
								}
							}
						}						
						if($bool){
							$p = str_replace($coverage_vars['base_dir'],'',$filepath);
							$pre_id = $pre_line = null;

							$getid_ps->execute(array($p));
							
							while($resultset = $getid_ps->fetch(PDO::FETCH_ASSOC)){
								$pre_id = $resultset['id'];
								$pre_line = $resultset['covered_line'];
							}
							if(!isset($pre_id)){
								$insert_ps->execute(array($p,json_encode(array_keys($lines)),sizeof(file($filepath)),sizeof($lines),file_get_contents($filepath)));
							}else{
								$line_array = array_flip(json_decode($pre_line,true));
								foreach($lines as $k => $v) $line_array[$k] = $k;
								$covered_line = array_keys($line_array);
								
								$update_ps->execute(array(json_encode($covered_line),sizeof($covered_line),$pre_id));
							}
							$insert_exe_ps->execute(array(
									$p,
									implode(',',array_keys($lines)),
									$coverage_vars['current_name']
							));
						}
					}
				}
				$db->commit();
				xdebug_stop_code_coverage();
			}
		});
		xdebug_start_code_coverage();
	}
}
?>