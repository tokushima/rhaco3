<?php
namespace org\rhaco\lang;
/**
 * 環境変数
 * @author tokushima
 */
class Env{
	/**
	 * 値があれば返す
	 * @param string $name
	 */
	public function get($name){
		return (isset($_ENV[$name]) && !empty($_ENV[$name])) ? $_ENV[$name] : (isset($_SERVER[$name]) ? $_SERVER[$name] : null);
	}
}