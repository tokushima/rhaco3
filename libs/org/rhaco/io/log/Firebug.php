<?php
namespace org\rhaco\io\log;
/**
 * consoleにログを出力するLogモジュール
 * @author tokushima
 */
class Firebug{
	/**
	 * 
	 * @module org.rhaco.Log
	 * @param org.rhaco.Log[] $logs
	 * @param string $id
	 * @param boolean $stdout
	 */
	public function flush($logs,$id,$stdout){
		if(php_sapi_name() != 'cli' && $stdout){
			print('<script>');
			foreach($logs as $log){
				print(sprintf('console.%s("[%s:%d]",%s);',$log->fm_level(),$log->file(),$log->line(),str_replace("\n","\\n",json_encode($log->value()))));
			}
			print('</script>');
		}
	}
}
