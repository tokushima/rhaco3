<?php
namespace org\rhaco\lang;
/**
 * 文字列を操作する
 * @author tokushima
 */
class Text{
	/**
	 * ヒアドキュメントのようなテキストを生成する
	 * １行目のインデントに合わせてインデントが消去される
	 * @param string $text 対象の文字列
	 * @return string
	 */
	final public static function plain($text){
		if(!empty($text)){
			$lines = explode("\n",$text);
			if(sizeof($lines) > 2){
				if(trim($lines[0]) == '') array_shift($lines);
				if(trim($lines[sizeof($lines)-1]) == '') array_pop($lines);
				return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode("\n",$lines)) : implode("\n",$lines);
			}
		}
		return $text;
	}
	/**
	 * HTMLデコードした文字列を返す
	 * @param string $value 対象の文字列
	 * @return string
	 */
	static public function htmldecode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,'UTF-8',mb_detect_encoding($value));
			$value = preg_replace_callback("/&#[xX]([0-9a-fA-F]+);/u",function($m){return '&#'.hexdec($m[1]).';';},$value);
			$value = mb_decode_numericentity($value,array(0x0,0x10000,0,0xfffff),"UTF-8");
			$value = html_entity_decode($value,ENT_QUOTES,"UTF-8");
			$value = str_replace(array("\\\"","\\'","\\\\"),array("\"","\'","\\"),$value);
		}
		return $value;
	}
	/**
	 * htmlエンコードをする
	 * @param string $value 対象の文字列
	 * @return string
	 */
	final static public function htmlencode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			return htmlentities($value,ENT_QUOTES,"UTF-8");
		}
		return $value;
	}
	/**
	 * 改行コードをLFに統一する
	 * @param string $src 対象の文字列
	 * @return string
	 */
	static public function uld($src){
		return str_replace(array("\r\n","\r"),"\n",$src);
	}
	/**
	 * 文字数を返す
	 * @param string $str 対象の文字列
	 * @param string $enc 文字エンコード
	 * @return integer
	 */
	final static public function length($str,$enc=null){
		if(is_array($str)){
			$length = 0;
			foreach($str as $value){
				if($length < self::length($value,$enc)) $length = self::length($value,$enc);
			}
			return $length;
		}
		return mb_strlen($str,empty($enc) ? mb_detect_encoding($str) : $enc);
	}
	/**
	 * 文字列の部分を返す
	 * @param string $str 対象の文字列
	 * @param integer $start 開始位置
	 * @param integer $length 最大長
	 * @param string $enc 文字コード
	 * @return string
	 */
	final static public function substring($str,$start,$length=null,$enc=null){
		return mb_substr($str,$start,empty($length) ? self::len($str) : $length,empty($enc) ? mb_detect_encoding($str) : $enc);
	}
	/**
	 * フォーマット文字列 $str に基づき生成された文字列を返します。
	 *
	 * @param string $str 対象の文字列
	 * @param mixed[] $params フォーマット中に現れた置換文字列{1},{2}...を置換する値
	 * @return string
	 */
	final static public function fstring($str,$params){
		if(preg_match_all("/\{([\d]+)\}/",$str,$match)){
			$params = func_get_args();
			array_shift($params);
			if(is_array($params[0])) $params = $params[0];
			foreach($match[1] as $key => $value){
				$i = ((int)$value) - 1;
				$str = str_replace($match[0][$key],isset($params[$i]) ? $params[$i] : '',$str);
			}
		}
		return $str;
	}

	/**
	 * 出力する
	 * @param string $value
	 */
	static public function println($value,$indent=0){
		if($indent > 0) $value = str_repeat(' ',$indent).implode(PHP_EOL.str_repeat(' ',$indent),explode(PHP_EOL,$value));
		print($value.PHP_EOL);
	}
	/**
	 * PHPドキュメントから内容を取得
	 * @param string $doc
	 * @return string
	 */
	static public function doctrim($doc){
		return trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array('/'.'**','*'.'/'),'',$doc)));		
	}
}