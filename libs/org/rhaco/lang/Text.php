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
		/***
			$text = self::plain('
							aaa
							bbb
						');
			eq("aaa\nbbb",$text);
		 */
		/***
			$text = self::plain("hoge\nhoge");
			eq("hoge\nhoge",$text);
		 */
		/***
			$text = self::plain("hoge\nhoge\nhoge\nhoge");
			eq("hoge\nhoge\nhoge\nhoge",$text);
		 */
	}
	/**
	 * HTMLデコードした文字列を返す
	 * @param string $value 対象の文字列
	 * @return string
	 */
	static public function htmldecode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,'UTF-8',mb_detect_encoding($value));
			$value = preg_replace("/&#[xX]([0-9a-fA-F]+);/eu","'&#'.hexdec('\\1').';'",$value);
			$value = mb_decode_numericentity($value,array(0x0,0x10000,0,0xfffff),"UTF-8");
			$value = html_entity_decode($value,ENT_QUOTES,"UTF-8");
			$value = str_replace(array("\\\"","\\'","\\\\"),array("\"","\'","\\"),$value);
		}
		return $value;
		/***
		 * eq("ほげほげ",self::htmldecode("&#12411;&#12370;&#12411;&#12370;"));
		 * eq("&gt;&lt;ほげ& ほげ",self::htmldecode("&amp;gt;&amp;lt;&#12411;&#12370;&amp; &#12411;&#12370;"));
		 */
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
		/***
			eq("&lt;abc aa=&#039;123&#039; bb=&quot;ddd&quot;&gt;あいう&lt;/abc&gt;",self::htmlencode("<abc aa='123' bb=\"ddd\">あいう</abc>"));
		 */
	}
	/**
	 * 改行コードをLFに統一する
	 * @param string $src 対象の文字列
	 * @return string
	 */
	static public function uld($src){
		/***
		 * eq("a\nb\nc\n",self::uld("a\r\nb\rc\n"));
		 */
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
		/***
			eq(3,self::length("abc"));
			eq(5,self::length(array("abc","defgh","i")));
		 */
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
		/***
			eq("def",self::substring("abcdefg",3,3));
		 */
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
		/***
			$params = array("A","B","C");
			eq("aAbBcCde",self::fstring("a{1}b{2}c{3}d{4}e",$params));
			eq("aAbBcAde",self::fstring("a{1}b{2}c{1}d{4}e",$params));
			eq("aAbBcAde",self::fstring("a{1}b{2}c{1}d{4}e","A","B","C"));
		 */
	}
	static public function println($value,$fmt=null){
		if(substr(PHP_OS,0,3) == 'WIN'){
			$value = mb_convert_encoding($value,'UTF-8','SJIS');
		}else if($fmt !== null){
			$fmt = ($fmt === true) ? '1;34' : (($fmt === false) ? '1;31' : $fmt);
			$value = "\033[".$fmt.'m'.$value."\033[0m";
		}
		print($value.PHP_EOL);
	}	
}