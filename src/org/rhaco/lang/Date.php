<?php
namespace org\rhaco\lang;
/**
 * 日付関係ユーティリティ
 * @author tokushima
 */
class Date{
	/**
	 * 指定した日時を加算したタイムスタンプを取得
	 *
	 * @param int $time
	 * @param int $seconds
	 * @param int $minutes
	 * @param int $hours
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	static public function add($time,$seconds=0,$minutes=0,$hours=0,$day=0,$month=0,$year=0){
		$dateList = getdate(intval(self::parse_date($time)));
		return mktime($dateList["hours"] + $hours,
						$dateList["minutes"] + $minutes,
						$dateList["seconds"] + $seconds,
						$dateList["mon"] + $month,
						$dateList["mday"] + $day,
						$dateList["year"] + $year
					);
	}

	/**
	 * 日を加算する
	 *
	 *
	 * @param int $time
	 * @param int $int
	 * @return int
	 */
	static public function add_day($add,$time=null){
		return self::add((($time === null) ? time() : $time),0,0,0,$add);
	}

	/**
	 * 時を加算する
	 *
	 * @param int $time
	 * @param int $add
	 * @return int
	 */
	static public function add_hour($add,$time=null){
		return self::add((($time === null) ? time() : $time),0,0,$add);
	}
	/**
	 * 日付文字列からタイムスタンプを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_date($str){
		if(empty($str)) return null;
		return (ctype_digit($str)) ? (int)$str : strtotime($str);
	}

	/**
	 * 時間文字列からタイムスタンプを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_time($str){
		if(preg_match("/^(\d+):(\d+):(\d+)$/",$str,$match)) return ((int)$match[1] * 3600) + ((int)$match[2] * 60) + ((int)$match[3]);
		if(preg_match("/^(\d+):(\d+):(\d+)(\.[\d]+)$/",$str,$match)) return (float)((((int)$match[1] * 3600) + ((int)$match[2] * 60) + ((int)$match[3])).$match[4]);
		if(preg_match("/[^\d]/",$str)) return null;
		return (is_numeric($str)) ? (float)$str : null;
	}

	/**
	 * 日付文字列からintdateを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_int($str){
		if(preg_match("/[^\d\/\-]/",$str)) return null;
		if(strlen(preg_replace("/[^\d]/","",$str)) > 8) $str = self::format($str,"Y/m/d");
		if(preg_match("/^(\d+)[^\d](\d+)[^\d](\d+)$/",$str,$match)) $str = sprintf("%d%02d%02d",intval($match[1]),intval($match[2]),intval($match[3]));
		return ($str > 0) ? intval($str) : null;
	}

	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format($time,$format=""){
		$format = str_replace(array('YYYY','MM','DD'),array('Y','m','d'),$format);
		$time = self::parse_date($time);
		if(empty($time)) return '';
		if(empty($format)) $format = 'Y/m/d H:i:s';
		return date($format,$time);
	}

	/**
	 * 整形された時間文字列を取得
	 *
	 * @param int $time
	 * @return string
	 */
	static public function format_time($time){
		$time = self::parse_time($time);
		if(!is_numeric($time)) return '';
		return sprintf('%02d:%02d:%02d',intval($time/3600),intval(($time%3600)/60),intval(($time%3600)%60));
	}

	/**
	 * 整形された日付文字列を取得
	 *
	 * @param int $intdate
	 * @return string
	 */
	static public function format_date($intdate){
		$date = self::parse_int($intdate);
		if(preg_match("/^([\d]+)([\d]{2})([\d]{2})$/",$date,$match)) return sprintf('%d/%02d/%02d',$match[1],$match[2],$match[3]);
		return '';
	}

	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_full($time){
		return self::format($time,'Y/m/d H:i:s (D)');
	}
	/**
	 * (GMT)日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_atom($time){
		return self::format($time - date('Z'),"Y-m-d\TH:i:s\Z");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_cookie($time){
		return self::format($time,'D, d M Y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_ISO8601($time){
		return self::format($time,"Y-m-d\TH:i:sO");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC822($time){
		return self::format($time,'D, d M Y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC850($time){
		return self::format($time,'l, d-M-y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC1036($time){
		return self::format($time,'l, d-M-y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC1123($time){
		return self::format($time,'D, d M Y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC2822($time){
		return self::format($time,'D, d M Y H:i:s O');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_rss($time){
		return self::format($time,'D, d M Y H:i:s T');
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_w3c($time){
		$time = self::parse_date($time);
		if($time === null) return '';
		$tzd = date('O',$time);
		$tzd = $tzd[0].substr($tzd,1,2).':'.substr($tzd,3,2);
		return self::format($time,"Y-m-d\TH:i:s").$tzd;
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @return string
	 */
	static public function format_pdf($time){
		$tzd = date("O",$time);
		$tzd = $tzd[0].substr($tzd,1,2)."'".substr($tzd,3,2)."'";
		return 'D:'.self::format($time,'YmdHis').$tzd;
	}

	/**
	 * 日付比較 ==
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function eq($timestampA,$timestampB){
		return self::parse_date($timestampA) == self::parse_date($timestampB);
	}
	/**
	 * 日付比較 >
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function gt($timestampA,$timestampB){
		return self::parse_date($timestampA) > self::parse_date($timestampB);
	}

	/**
	 * 日付比較 >=
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function gte($timestampA,$timestampB){
		return self::parse_date($timestampA) >= self::parse_date($timestampB);
	}

	/**
	 * 年齢の算出
	 *
	 * @param int $intdate
	 * @param int $time
	 * @return int
	 */
	static public function age($intdate,$time=null){
		if($time === null) $time = time();
		$intdate = intval(preg_replace("/[^\d]/","",$intdate));
		$a = intval(substr(self::format($time,"Ymd"),0,-4)) - intval(substr($intdate,0,-4));
		if(self::gte(self::parse_date("2000".substr($intdate,-4)),self::parse_date("2000".substr($time,-4)))) $a += 1;
		return $a;
	}

	/**
	 * 曜日の算出
	 *
	 * @param mixed $date intdate / string date
	 * @return int 0:日 1:月 2:火 3:水 4:木 5:金 6:土
	 */
	static public function weekday($date){
		if(!is_numeric($date)){
			$date = self::parse_int($date);
		}
		if(is_null($date)) return;
		$year = intval(floor($date / 10000));
		$month = intval(floor(($date % 10000) / 100));
		$day = intval($date % 100);
		if($month == 1 || $month == 2){
			$year--;
			$month += 12;
		}
		return ($year + intval($year/4) - intval($year/100) + intval($year/400) + intval((13*$month+8)/5) + $day) % 7;
	}
}
