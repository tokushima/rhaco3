<?php
namespace org\rhaco\lang;
/**
 * ANSIエスケープ シーケンス
 * @author tokushima
 *
 */
class AnsiEsc{
	/**
	 * 色装飾
	 * @param string $value
	 * @param mixed $fmt
	 */
	static public function color($value,$fmt=null){
		if(substr(PHP_OS,0,3) == 'WIN'){
			$value = mb_convert_encoding($value,'UTF-8','SJIS');
		}else if($fmt !== null){
			$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
			$value = "\033[".$fmt.'m'.$value."\033[0m";
		}
		return $value;
	}
	/**
	 * バックスペース
	 * @param integer $len
	 */
	static public function backspace($len){
		print("\033[".$len.'D'."\033[0K");
	}
	/**
	 * 出力する
	 * @param string $value
	 * @param mixed $fmt
	 */
	static public function println($value,$fmt=null){
		print(self::color($value,$fmt).PHP_EOL);
	}	
}