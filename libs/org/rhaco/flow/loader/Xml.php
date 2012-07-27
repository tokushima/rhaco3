<?php
namespace org\rhaco\flow\loader;

class Xml{
	public function flow_map_loader($xml_file){
		$app = array();
		$src = file_get_contents($xml_file);
		if(\org\rhaco\Xml::set($xml,$src,'app')){
			foreach($xml->in('patterns') as $pattern){
				foreach($pattern->in('map') as $map){
					$app['pattenrs'][$map->in_attr('url')] = array(
						'mode'=>$map->in_attr('mode'),
						'action'=>$map->in_attr('action'),
					);
				}
			}
		}
		return $app;
	}
}


