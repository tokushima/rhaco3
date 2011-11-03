<?php
namespace org\rhaco;
/**
 * PEARをインストールする
 * @author tokushima
 */
class Pear{
	/**
	 * PEARをインストールする
	 * @param string $package_path
	 * @param string $target_state
	 * @param string $output_path
	 * @throws \RuntimeException
	 */
	static public function install($package_path,$target_state,$output_path){
		if(!empty($output_path) && substr($output_path,-1) != '/') $output_path = $output_path.'/';
		list($domain,$package_name) = (strpos($package_path,'/')===false) ? array('pear.php.net',$package_path) : explode('/',$package_path,2);
		list($package_name,$package_version) = (strpos($package_name,'-')===false) ? array($package_name,null) : explode('-',$package_name,2);
		$download_path = null;

		try{
			$xml = simplexml_load_string(file_get_contents('http://'.$domain.'/channel.xml'));
			$url = (string)$xml->servers->primary->rest->baseurl;
			$channel = (substr($url,-1) == '/') ? substr($url,0,-1) : $url;
			$allreleases_xml = simplexml_load_string(file_get_contents($channel.'/r/'.strtolower($package_name).'/allreleases.xml'));
			$target_package = $package_name;
			$target_version = null;
			$state_no = array('stable'=>0,'beta'=>1,'alpha'=>2,'devel'=>3);

			foreach($allreleases_xml->children() as $r){
				$v = (string)$r->v;
				if(!empty($package_version)){
					if($package_version == $v) $target_version = $v;
				}else{
					$s = (string)$r->s;
					if(array_key_exists($s,$state_no) && $state_no[$s] <= $state_no[$target_state]) $target_version = $v;
				}
				if(!empty($target_version)) break;
			}
			if(empty($target_version)){
				$all = array();
				foreach($allreleases_xml->children() as $r){
					if($r->getName() == 'r') $all[] = $package_path.'-'.((string)$r->v).' '.((string)$r->s).'';
				}
				throw new \RuntimeException($package_path.' not found.'.PHP_EOL.(empty($all) ? '' : ' all versions: '.PHP_EOL.'  '.implode(PHP_EOL.'  ',$all)));
			}
			$download_base = $output_path.'_download/';
			$download_path = $download_base.str_replace(array('.','-'),'_',$domain).'_'.$target_package.'_'.strtr($target_version,'.','_');
			$download_url = 'http://'.$domain.'/get/'.$target_package.'-'.$target_version.'.tgz';
			if(!is_dir($download_path)){
				mkdir($download_path,0777,true);
				$fp = gzopen($download_url,'rb');
				$buf = null;
				while(!gzeof($fp)) $buf .= gzread($fp,4096);
				gzclose($fp);
				file_put_contents($download_path.'/'.$target_package.'-'.$target_version.'.tar',$buf);
				$phar = new \PharData($download_path.'/'.$target_package.'-'.$target_version.'.tar');
				$phar->extractTo($download_path,null,true);
			}
			$package = simplexml_load_file(is_file($download_path.'/package.xml') ? $download_path.'/package.xml' : $download_path.'/package2.xml');
			$attr = $package->attributes();

			switch($attr['version']){
				case '2.0':
					foreach($package->contents->children() as $dir){
						foreach($dir->children() as $file) self::copy($file,$target_package,$target_version,$download_path,$output_path);
					}
					foreach($package->dependencies->required->children() as $dep){
						if($dep->getName() == 'package') self::install(((string)$dep->channel).'/'.((string)$dep->name),$target_state);
					}
					break;
				case '1.0':
					foreach($package->release->filelist->children() as $file){
						if($file->getName() == 'file') self::copy($file,$target_package,$target_version,$download_path,$output_path);
					}
					foreach($package->release->deps->children() as $dep){
						if($dep->getName() == 'dep'){
							$dep_attr = $dep->attributes();
							if($dep_attr['type'] == 'pkg' && $dep_attr['optional'] == 'no'){
								self::install((string)$dep,$target_state);
							}
						}
					}
					break;
				default:
					throw new \RuntimeException("unknown package version");
			}
			self::rmdir($download_path);
		}catch(\Exception $e){
			self::rmdir($download_path);
			throw $e;
		}
	}
	static private function copy(\SimpleXMLElement $file,$target_package,$target_version,$download_path,$output_path){
		$attr = $file->attributes();
		$role = $attr['role'];
		$baseinstalldir = $output_path.$attr['baseinstalldir'];
		$name = $attr['name'];
		$src = $download_path.'/'.$target_package.'-'.$target_version.'/'.$name;
		$data = defined('__PEAR_DATA_DIR__') ? constant('__PEAR_DATA_DIR__') : $output_path.'data';

		if($role == 'php'){
			$dest = $baseinstalldir.'/'.$name;
			if(!is_dir(dirname($dest))) mkdir(dirname($dest),0777,true);
			copy($src,$dest);

			$det_src = file_get_contents($dest);
			if(preg_match_all("/[^\\\\]([\"']).*@data_dir@/",$det_src,$match)){
				foreach($match[0] as $k => $v){
					$det_src = str_replace($v,str_replace('@data_dir@',$match[1][$k].'.constant(\'__PEAR_DATA_DIR__\').'.$match[1][$k],$v),$det_src);
				}
				file_put_contents($dest,$det_src);
			}
		}else if($role == 'data'){
			$dest = $data.'/'.$target_package.'/'.$name;
			if(!is_dir(dirname($dest))) mkdir(dirname($dest),0777,true);
			copy($src,$dest);
		}
	}
	static private function rmdir($dir){
		if(is_dir($dir)){
			$scan = scandir($dir);
			foreach($scan as $f){ 
				if($f != '.' && $f != '..'){
					if(is_file($dir.'/'.$f)){
						unlink($dir.'/'.$f);
					}else{
						self::rmdir($dir.'/'.$f);
					}
				}
			}
			rmdir($dir);
		}
	}
}