<?php
namespace org\rhaco\flow\module;
/**
 * テンプレートで利用するヘルパ
 * @author tokushima
 */
class Helper{
	private $media_url;
	private $name;
	private $url_pattern = array();
	private $is_login = false;
	private $user;

	public function __construct($media_url=null,$name=null,$map=array(),$obj=null){
		$this->media_url = $media_url;
		$this->name = $name;

		foreach($map as $p => $m){
			if(isset($m['name'])) $this->url_pattern[$m['name']] = $m;
		}
		if($obj instanceof \org\rhaco\flow\parts\RequestFlow){
			$this->is_login = $obj->is_login();
			$this->user = $obj->user();
		}
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function is_login(){
		return $this->is_login;
	}
	/**
	 * ログインユーザを返す
	 * @return mixed
	 */
	public function user(){
		return $this->user;
	}
	/**
	 * handlerのマップ名を呼び出しているURLを生成する
	 * 引数を与える事も可能
	 * @param string $name マップ名
	 * @return string
	 */
	public function map_url($name){
		$args = func_get_args();
		array_shift($args);
		if(isset($this->url_pattern[$name]) && $this->url_pattern[$name]['num'] == sizeof($args)) return vsprintf($this->url_pattern[$name]['pattern'],$args);
	}
	/**
	 * handlerでpackageを呼び出してる場合にメソッド名でURLを生成する
	 * 引数を与える事も可能
	 * @param string $name メソッド名
	 * @return string
	 */
	public function package_method_url($name){
		$args = func_get_args();
		array_shift($args);
		if(isset($this->url_pattern[$this->name()]) && isset($this->url_pattern[$this->name()]['='])){
			$p = $this->url_pattern[$this->name()];
			$n = sizeof($args);
			foreach($this->url_pattern as $m){
				if(isset($m['=']) && $m['class'] == $p['class'] && $m['method'] == $name && $m['num'] == $n){
					return call_user_func_array(array($this,'map_url'),array_merge(array($m['name']),$args));
				}
			}
		}
	}
	/**
	 * マッチしたパターン（名）を返す
	 * @return string
	 */
	public function name(){
		return $this->name;
	}
	/**
	 * マッチしたパターンと$patternが同じなら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function match_pattern_switch($pattern,$true='on',$false=''){
		return ($this->name() == $pattern) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで前方一致なら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function startswith_pattern_switch($pattern,$true='on',$false=''){
		return (strpos($this->name(),$pattern) === 0) ? $true : $false;
	}
	/**
	 * マッチしたパターンが$patternで後方一致なら$trueを、違うなら$falseを返す
	 * @param string $pattern 比較する文字列
	 * @param string $true 一致した場合に返す文字列
	 * @param string $false 一致しなかった場合に返す文字列
	 * @return string
	 */
	public function endswith_pattern_switch($pattern,$true='on',$false=''){
		return (strrpos($this->name(),$pattern) === (strlen($this->name())-strlen($pattern))) ? $true : $false;
	}
	/**
	 * 真偽値により$trueまたは$falseを返す
	 * @param boolean $cond 真偽値
	 * @param string $true 真の場合に返す文字列
	 * @param string $false 偽の場合に返す文字列
	 * @return string
	 */
	public function cond_switch($cond,$true='on',$false=''){
		return ($cond === true) ? $true : $false;
		/***
			$t = new self();
			eq('on',$t->cond_switch(true,'on','off'));
			eq('off',$t->cond_switch(false,'on','off'));
			eq('off',$t->cond_switch(1,'on','off'));
		*/
	}
	/**
	 * @param mixed $cond 空または0またはfalseの場合に偽
	 * @param string $true 真の場合に返す文字列
	 * @param string $false 偽の場合に返す文字列
	 * @return string
	 */
	public function has_switch($cond,$true='on',$false=''){
		return empty($cond) ? $false : $true;
		/***
			$t = new self();
			eq('off',$t->has_switch('','on','off'));
			eq('off',$t->has_switch(0,'on','off'));
			eq('off',$t->has_switch(false,'on','off'));
			eq('off',$t->has_switch(array(),'on','off'));
			eq('on',$t->has_switch('1','on','off'));
			eq('on',$t->has_switch(1,'on','off'));
			eq('on',$t->has_switch(true,'on','off'));
			eq('on',$t->has_switch(array(1),'on','off'));
		*/
	}
	/**
	 * アプリケーションのメディアのURLを返す
	 * @param string $url ベースのURLに続く相対パス
	 * @return string
	 */
	public function media($url=null){
		return \org\rhaco\net\Path::absolute($this->media_url,$url);
	}
	/**
	 * ゼロを桁数分前に埋める
	 * @param integer $int 対象の値
	 * @param $dig 0埋めする桁数
	 * @return string
	 */
	public function zerofill($int,$dig=0){
		return sprintf("%0".$dig."d",$int);
		/***
			$t = new self();
			eq("00005",$t->zerofill(5,5));
			eq("5",$t->zerofill(5));
		 */
	}
	/**
	 * 数字を千位毎にグループ化してフォーマットする
	 * @param number $number 対象の値
	 * @param integer $dec 小数点以下の桁数
	 * @return string
	 */
	public function number_format($number,$dec=0){
		return number_format($number,$dec,".",",");
		/***
			$t = new self();
			eq("123,456,789",$t->number_format("123456789"));
			eq("123,456,789.020",$t->number_format("123456789.02",3));
			eq("123,456,789",$t->number_format("123456789.02"));
		 */
	}
	/**
	 * フォーマットした日付を返す
	 * @param integer $value 時間
	 * @param string $format フォーマット文字列 ( http://jp2.php.net/manual/ja/function.date.php )
	 * @return string
	 */
	public function df($value,$format="Y/m/d H:i:s"){
		return date($format,$value);
		/***
			$t = new self();
			$time = time();
			eq(date("YmdHis",$time),$t->df($time,"YmdHis"));
		 */
	}
	/**
	 * 改行を削除(置換)する
	 *
	 * @param string $value 対象の文字列
	 * @param string $glue 置換後の文字列
	 * @return string
	 */
	public function one_liner($value,$glue=" "){
		return str_replace(array("\r\n","\r","\n","<br>","<br />"),$glue,$value);
		/***
			$t = new self();
			eq("a bc    d ef  g ",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>"));
			eq("abcdefg",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>",""));
			eq("a-bc----d-ef--g-",$t->one_liner("a\nbc\r\n\r\n\n\rd<br>ef<br /><br />g<br>","-"));
		 */
	}
	/**
	 * 文字列を丸める
	 * @param string $str 対象の文字列
	 * @param integer $width 指定の幅
	 * @param string $postfix 文字列がまるめられた場合に末尾に接続される文字列
	 * @return string
	 */
	public function trim_width($str,$width,$postfix=''){
		$rtn = "";
		$cnt = 0;
		$len = mb_strlen($str);
		for($i=0;$i<$len;$i++){
			$c = mb_substr($str,$i,1);
			$cnt += (mb_strwidth($c) > 1) ? 2 : 1;
			if($width < $cnt) break;
			$rtn .= $c;
		}
		if($len > mb_strlen($rtn)) $rtn .= $postfix;
		return $rtn;
		/***
			$t = new self();
			$str = "あいうえお12345かきくけこ";
			eq("あいう",$t->trim_width($str,7));
			
			$t = new self();
			$str = "あいうえお12345かきくけこ";
			eq("あいう...",$t->trim_width($str,7,"..."));
			
			$t = new self();
			$str = "あいうえお12345かきくけこ";
			eq("あいうえお123...",$t->trim_width($str,13,"..."));

			$t = new self();
			$str = "あいうえお12345かきくけこ";
			eq("あいうえお12345かきくけこ",$t->trim_width($str,50,"..."));	
			
			$t = new self();
			$str = "あいうえお12345かきくけこ";
			eq("あいうえお12345かきくけこ",$t->trim_width($str,30,"..."));
		 */
	}
	/**
	 * 何もしない
	 * @param mixed $var そのまま返す値
	 * @return mixed
	 */
	public function noop($var){
		return $var;
	}
	/**
	 * HTMLエスケープされた文字列を返す
	 * @param string $value 対象の文字列
	 * @param integer $length 取得する文字列の最大長
	 * @param integer $lines 取得する文字列の最大行数
	 * @param string $postfix 文字列が最大長または最大行数を超えた場合に末尾に接続される文字列
	 * @return string
	 */
	public function html($value,$length=0,$lines=0,$postfix=null){
		$value = self::cdata(str_replace(array("\r\n","\r"),"\n",$value));
		if($length > 0){
			$det = mb_detect_encoding($value);
			$value = mb_substr($value,0,$length,$det).((mb_strlen($value,$det) > $length) ? $postfix : null);
		}
		if($lines > 0){
			$ln = array();
			$l = explode("\n",$value);
			for($i=0;$i<$lines;$i++) $ln[] = $l[$i];
			$value = implode("\n",$ln).((sizeof($l) > $lines) ? $postfix : null);
		}
		return nl2br(str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value));
	}
	/**
	 * CDATA形式から値を取り出す
	 * @param string $value 対象の文字列
	 * @return string
	 */
	static public function cdata($value){
		if(preg_match_all("/<\!\[CDATA\[(.+?)\]\]>/ims",$value,$match)){
			foreach($match[1] as $key => $v) $value = str_replace($match[0][$key],$v,$value);
		}
		return $value;
		/***
			eq("<abc />",self::cdata("<![CDATA[<abc />]]>"));
		 */
	}
	/**
	 * 全てのタグを削除した文字列を返す
	 * @param string $value 対象の文字列
	 * @param integer $length 取得する文字列の最大長
	 * @param integer $lines 取得する文字列の最大行数
	 * @param string $postfix 文字列が最大長または最大行数を超えた場合に末尾に接続される文字列
	 * @return string
	 */
	public function text($value,$length=0,$lines=0,$postfix=null){
		return self::html(preg_replace("/<.+?>/","",$value),$length,$lines,$postfix);
	}
	/**
	 * aがbより小さい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function lt($a,$b){
		return ($a < $b);
	}
	/**
	 * aがbより小さいか等しい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function lte($a,$b){
		return ($a <= $b);
	}
	/**
	 * aがbより大きい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function gt($a,$b){
		return ($a > $b);
	}
	/**
	 * aがbより大きいか等しい
	 * @param integer $a
	 * @param integer $b
	 * @return boolean
	 */
	public function gte($a,$b){
		return ($a >= $b);
	}
}