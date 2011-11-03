<?php
/**
 * Cli
 * @author tokushima
 */
class CLi{
	/**
	 * $argvを分解する
	 * @param string[] $argv
	 * @return mixed[]
	 */
	static public function args($argv=null){
		if(!isset($argv)) $argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		if(empty($argv)) return array(null,array());
		array_shift($argv);
		$target = (isset($argv[0]) && $argv[0][0] != '-') ? array_shift($argv) : null;
		$op = array();
		$size = sizeof($argv);
		for($i=0;$i<$size;$i++){
			if($argv[$i][0] == '-'){
				if(isset($argv[$i+1]) && $argv[$i+1][0] != '-'){
					$k = substr($argv[$i],1);
					if(isset($op[$k])){
						if(!is_array($op[$k])) $op[$k] = array($op[$k]);
						$op[$k][] = $argv[$i+1];
					}else{
						$op[$k] = $argv[$i+1];
					}
					$i++;
				}else{
					$op[substr($argv[$i],1)] = '';
				}
			}
		}
		return array($target,$op);
	}
}