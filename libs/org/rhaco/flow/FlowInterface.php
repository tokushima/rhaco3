<?php
namespace org\rhaco\flow;
/**
 * Flowと連携するインタフェース
 * @author tokushima
 */
interface FlowInterface{
	public function get_theme();
	public function get_block();
	public function set_args($arg);
	public function set_maps($maps);
	public function set_select_map_name($name);
	public function before();
	public function after();
	public function exception();
}
