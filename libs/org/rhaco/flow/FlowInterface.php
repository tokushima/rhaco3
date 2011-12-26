<?php
namespace org\rhaco\flow;
/**
 * Flowと連携するインタフェース
 * @author tokushima
 */
interface FlowInterface{
	/**
	 * 
	 * テーマを取得する
	 * @return string
	 */
	public function get_theme();
	/**
	 * ブロックを取得する
	 * @return string
	 */
	public function get_block();
	/**
	 * map_argsをセットする
	 * @param array $arg
	 */
	public function set_args($arg);
	/**
	 * 定義されたマップをセットする
	 * @param array $maps
	 */
	public function set_maps($maps);
	/**
	 * 選択されたマップ名
	 * @param string $name
	 */
	public function set_select_map_name($name);
	/**
	 * 前処理
	 */
	public function before();
	/**
	 * 後処理
	 */
	public function after();
	/**
	 * 例外処理
	 */
	public function exception();
	/**
	 * テンプレートにセットするモジュールの取得
	 */
	public function get_template_modules();
}
