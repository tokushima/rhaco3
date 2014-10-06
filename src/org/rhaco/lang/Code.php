<?php
namespace org\rhaco\lang;
/**
 * コード
 * @author tokushima
 *
 */
class Code{
	/**
	 * コードから数値に変換する
	 * @param string $codebase
	 * @param string $code
	 * @return integer
	 */
	public static function decode($codebase,$code){
		$base = strlen($codebase);
		$rtn = 0;
		$exp = strlen($code) - 1;
	
		for($i=0;$i<$exp;$i++){
			$p = strpos($codebase,$code[$i]);
			$rtn = $rtn + (pow($base,$exp-$i) * $p);
		}
		return $rtn + strpos($codebase,$code[$exp]);
	}
	/**
	 * 数値からコードに変換する
	 * @param string $codebase
	 * @param integer $num
	 * @return string
	 */
	public static function encode($codebase,$num){
		$base = strlen($codebase);
		$rtn = '';
		$exp = 1;
		while(pow($base,$exp) <= $num){
			$exp++;
		}
		for($i=$exp-1;$i>0;$i--){
			$y = pow($base,$i);
	
			$d = (int)($num / $y);
			$rtn = $rtn.$codebase[$d];
			$num = $num - ($y * $d);
		}
		return $rtn.$codebase[$num];
	}
	/**
	 * 指定桁で作成できる最大値
	 * @param string $codebase
	 * @param integer $length
	 * @return integer
	 */
	public static function max($codebase,$length){
		return pow(strlen($codebase),$length)-1;
	}
	/**
	 * 指定桁を作成する場合の最小値
	 * @param string $codebase
	 * @param integer $length
	 * @return integer
	 */
	public static function min($codebase,$length){
		return pow(strlen($codebase),$length-1);
	}
	/**
	 * 指定桁でランダムに作成する
	 * @param string $codebase
	 * @param integer $length
	 * @return string
	 */
	public static function rand($codebase,$length){
		$cl = strlen($codebase) - 1;
		$r = $codebase[rand(1,$cl)];
		for($i=1;$i<$length;$i++){
			$r = $r.$codebase[rand(0,$cl)];
		}
		return $r;
	}	
}