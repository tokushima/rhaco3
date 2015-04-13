<?php
namespace org\rhaco\flow\module;
/**
 * テンプレートで利用するヘルパ
 * @author tokushima
 */
class Helper{
	private $app_url;
	private $media_url;
	private $name;
	private $map_name;
	private $url_pattern = array();
	private $current_entry_dir;
	private $current_entry;
	
	private $is_login = false;
	private $user;

	public function __construct($app_url=null,$media_url=null,$name=null,$num=0,$current_entry_file=null,$map=array(),$obj=null){
		$this->app_url = $app_url;
		$this->media_url = $media_url;
		$this->name = $name;
		$this->map_name = $name.'#'.$num;
		$this->current_entry_dir = dirname($current_entry_file);
		$this->current_entry = substr(basename($current_entry_file),0,-4);
		$secure = false;

		foreach($map as $p => $m){
			$this->url_pattern[$this->current_entry][$m['name'].'#'.$m['num']] = $m;
			if($m['name'] === $this->name){
				$secure = $m['secure'];
			}
		}
		if($secure){
			$this->app_url = str_replace('http://','https://',$this->app_url);
			$this->media_url = str_replace('http://','https://',$this->media_url);
		}
		if($obj instanceof \org\rhaco\flow\parts\RequestFlow){
			$this->is_login = $obj->is_login();
			$this->user = $obj->user();
		}
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
		
		if(strpos($name,'::') === false){
			$entry = $this->current_entry;
		}else{
			list($entry,$name) = explode('::',$name,2);
		}
		if(!isset($this->url_pattern[$entry]) && is_file($f=($this->current_entry_dir.'/'.$entry.'.php'))){
			foreach(\org\rhaco\Flow::get_maps($f) as $m){
				$this->url_pattern[$entry][$m['name'].'#'.$m['num']] = $m;
			}
		}
		$n = $name.'#'.sizeof($args);
		if(isset($this->url_pattern[$entry][$n])){
			return vsprintf($this->url_pattern[$entry][$n]['pattern'],$args);
		}
	}
	/**
	 * POSTされたか
	 * @return boolean
	 */
	public function is_post(){
		return  (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST');
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
	 * 現在のURLを返す
	 * @return string
	 */
	public function current_url(){
		return \org\rhaco\Request::current_url();
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
		if(isset($this->url_pattern[$this->current_entry][$this->map_name]) && isset($this->url_pattern[$this->current_entry][$this->map_name]['@'])){
			$p = $this->url_pattern[$this->current_entry][$this->map_name];
			$n = sizeof($args);
			
			foreach($this->url_pattern[$this->current_entry] as $m){
				if(isset($m['@']) && $m['pkg_id'] == $p['pkg_id'] && $m['method'] == $name && $m['num'] == $n){
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
		return ($cond !== false && !empty($cond)) ? $true : $false;
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
	 * アプリケーションのURLを返す
	 * @param string $url ベースのURLに続く相対パス
	 * @retunr string
	 */
	public function app_url($url=null){
		return \org\rhaco\net\Path::absolute($this->app_url,$url);
	}
	/**
	 * ゼロを桁数分前に埋める
	 * @param integer $int 対象の値
	 * @param $dig 0埋めする桁数
	 * @return string
	 */
	public function zerofill($int,$dig=0){
		return sprintf("%0".$dig."d",$int);
	}
	/**
	 * 数字を千位毎にグループ化してフォーマットする
	 * @param number $number 対象の値
	 * @param integer $dec 小数点以下の桁数
	 * @return string
	 */
	public function number_format($number,$dec=0){
		return number_format($number,$dec,".",",");
	}
	/**
	 * フォーマットした日付を返す
	 * @param string $format フォーマット文字列 ( http://jp2.php.net/manual/ja/function.date.php )
	 * @param integer $value 時間
	 * @return string
	 */
	public function df($format="Y/m/d H:i:s",$value=null){
		if(empty($value)) $value = time();
		return date($format,$value);
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
	 * @param boolean $nl2br 改行コードを<br />にするか
	 * @return string
	 */
	public function html($value,$length=0,$lines=0,$postfix=null,$nl2br=true){
		$value = str_replace(array("\r\n","\r"),"\n",$value);
		if($length > 0){
			$det = mb_detect_encoding($value);
			$value = mb_substr($value,0,$length,$det).((mb_strlen($value,$det) > $length) ? $postfix : null);
		}
		if($lines > 0){
			$ln = array();
			$l = explode("\n",$value);
			for($i=0;$i<$lines;$i++){
				if(!isset($l[$i])) break;
				$ln[] = $l[$i];
			}
			$value = implode("\n",$ln).((sizeof($l) > $lines) ? $postfix : null);
		}
		$value = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value);
		return ($nl2br) ? nl2br($value,true) : $value;
	}
	/**
	 * 改行文字の前に HTML の改行タグを挿入する
	 * @param string $value
	 * @return string
	 */
	public function nl2br($value){
		return nl2br($value,true);
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
	 * Json文字列にして返す
	 * @param mixed $value
	 * @return string
	 */
	public function json($value){
		return \org\rhaco\lang\Json::encode($value);
	}
	/**
	 * ==
	 * @param mixed $a
	 * @param mixed $b
	 * @return boolean
	 */
	public function eq($a,$b){
		return ($a == $b);
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
	/**
	 * ある範囲の整数を有する配列を作成します。
	 * @param mixed  $start
	 * @param mixed  $end
	 * @param number $step
	 * @return multitype:
	 */
	public function range($start,$end,$step=1){
		if(ctype_digit((string)$start) && ctype_digit((string)$end)){
			$start = (int)$start;
			$end = (int)$end;
		}
		$array = range($start,$end,$step);
		return array_combine($array,$array);
	}
	/**
	 * 配列を逆順にして返す
	 * @param mixed $array
	 * @return array
	 */
	public function reverse($array){
		if(is_object($array) && ($array instanceof \Traversable)){
			$list = array();
			foreach($array as $v) $list[] = $v;
			$array = $list;
		}
		if(is_array($array)){
			rsort($array);
			return $array;
		}
		return array();
	}
}