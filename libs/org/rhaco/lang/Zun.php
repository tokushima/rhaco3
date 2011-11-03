<?php
namespace org\rhaco\lang;
/**
 * 記法
 * @author tokushima
 */
class Zun{
	/**
	 * fのエイリアス
	 * @param $src
	 * @return string
	 */
	public function format($src){
		return $this->f($src);
	}
	/**
	 * 記法一覧
	 *
	 * ヘッダ部文字(大)
	 * 記法 #=
	 * 結果 <h3>〜</h3>
	 *
	 * ヘッダ部文字(中)
	 * 記法 ##=
	 * 結果 <h4>〜</h4>
	 *
	 * ヘッダ部文字(小)
	 * 記法 ###=
	 * 結果 <h5>〜</h5>
	 *
	 * 水平線
	 * 記法 ----
	 * 結果 <hr />
	 *
	 * 強調
	 * 記法 ***文字列***
	 * 結果 <strong>文字列</strong>
	 *
	 * 強調
	 * 記法 ***文字列***
	 * 結果 <strong>文字列</strong>
	 *
	 * 斜体
	 * 記法 ///文字列///
	 * 結果 <i>文字列</i>
	 *
	 * リンク
	 * 記法 [[文字列:url]]
	 * 結果 <a href="url">文字列</a>
	 *
	 * 画像
	 * 記法 @@url@@
	 * 結果 <img src="url" />
	 *
	 * 整形済みテキスト
	 * 記法 [[[ ]]]
	 * 結果 <pre>〜</pre>
	 *
	 * 整形済みテキスト
	 * 記法 [[[[ ]]]]
	 * 結果 <blockquote>〜</blockquote>
	 *
	 * リスト
	 * 記法 +文字列
	 * 　　 +文字列
	 * 結果 <ul>
	 * 　　 <li>文字列</li>
	 * 　　 <li>文字列</li>
	 * 　　 </ul>
	 *
	 * テーブルデータ
	 * 記法 |aaa|bbb|ccc|
	 * 結果 <table><tr>
	 * 　　 <td>aaa</td><td>bbb</td><td>ccc</td>
	 * 　　 </tr></table>
	 *
	 * テーブルヘッダ
	 * 記法 |*aaa|*bbb|*ccc|
	 * 結果 <table><tr>
	 * 　　 <th>aaa</th><th>bbb</th><th>ccc</th>
	 * 　　 </tr></table>
	 *
	 * HTML
	 * 記法 {{{HTMLを記述}}}
	 *
	 * @param $src
	 */
	public function f($src){
		/***
			$text = pre('
						[[[
							
							ほげほげ
						]]]
					');
			$result = pre('
						<pre>
							
							ほげほげ
						</pre>
					');
			$obj = new self();
			eq($result,$obj->f($text));
		 */
		$escapes = array();
		$htmls = array();

		if(preg_match_all("/\{\{\{(.+?)\}\}\}/ms",$src,$matches)){			
			foreach($matches[0] as $key => $value){
				$name = "__HTML_ESCAPE__".uniqid($key);
				$htmls[$name] = $matches[1][$key];
				$src = str_replace($value,$name,$src);
			}
		}
		$src = "\n".str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),str_replace(array("\r\n","\r"),"\n",$src))."\n";
		if(preg_match_all("/![pe]\{(.+?)\}/ms",$src,$matches)){
			foreach($matches[0] as $key => $value){
				$name = "__ZUN_ESCAPE__".uniqid($key);
				$escapes[$name] = $matches[1][$key];
				$src = str_replace($value,$name,$src);
			}
		}
		$src = str_replace("[[[[","<blockquote><pre>",$src);
		$src = str_replace("]]]]","</pre></blockquote>",$src);

		$src = str_replace("[[[","<pre>",$src);
		$src = str_replace("]]]","</pre>",$src);

		$src = preg_replace("/([\n][\s\t]*)###=([^\n]+)/","\n\\1<h5>\\2</h5>",$src);
		$src = preg_replace("/([\n][\s\t]*)##=([^\n]+)/","\n\\1<h4>\\2</h4>",$src);
		$src = preg_replace("/([\n][\s\t]*)#=([^\n]+)/","\n\\1<h3>\\2</h3>",$src);
		$src = preg_replace("/([\n][\s\t]*)---[-]+[\s\t]*[\n]/","\\1<hr />\n",$src);
		$src = preg_replace("/---(.+?)---/","<del>\\1</del>",$src);

		$src = preg_replace("/\[\[([^:]+?)\:(.+?)\]\]/","<a href=\"\\2\">\\1</a>",$src);
		$src = preg_replace("/@@(.+?)@@/","<img border=\"0\" src=\"\\1\" />",$src);
		$src = preg_replace("/\/\/\/(.+?)\/\/\//","<i>\\1</i>",$src);
		$src = preg_replace("/\*\*\*(.+?)\*\*\*/","<strong>\\1</strong>",$src);

		$src = $this->toList($src);
		$src = $this->toTable($src);
		$src = substr($src,1,-1);
		$src = $this->toBr($src);

		foreach($escapes as $key => $value){
			$src = str_replace($key,$value,$src);
		}
		foreach($htmls as $key => $value){
			$src = str_replace($key,$value,$src);
		}
		$src = substr(preg_replace("/([^\"\'])(http[s]{0,1}:\/\/[^\s<]+)/i","\\1<a href=\"\\2\">\\2</a>"," ".$src),1);
		return $src;
	}
	private function isExclusion($value,$exclusion){
		if($exclusion === 0){
			if(strpos($value,"<pre>") !== false){
				$exclusion = 1;
			}else if(strpos($value,"<blockquote>") !== false){
				$exclusion = 2;
			}
		}
		if(($exclusion === 1 && strpos($value,"</pre>") !== false) ||
			($exclusion === 2 && strpos($value,"</blockquote>") !== false)
		){
			$exclusion = 0;
		}
		return $exclusion;
	}
	private function toBr($src){
		$result = "";
		$exclusion = 0;
		$en = false;

		foreach(explode("\n",$src) as $value){
			$en = true;
			$exclusion = $this->isExclusion($value,$exclusion);
			$result .= (($exclusion > 0) ? $value : $value."<br />")."\n";
		}
		return ($en) ? substr($result,0,-7) : $result;
	}
	private function toTable($src){
		$result = "";
		$exclusion = 0;
		$isTable = false;
		$en = false;

		foreach(explode("\n",$src) as $value){
			$en = true;
			$exclusion = $this->isExclusion($value,$exclusion);

			if($exclusion === 0){
				if(preg_match("/^\|(.+)\|$/",trim($value),$match)){
					$value = ($isTable) ? "<tr>" : "<table><tr>";

					foreach(explode("|",$match[1]) as $column){
						if(substr($column,0,1) == "*"){
							$value .= "<th>".substr($column,1)."</th>";
						}else{
							$value .= "<td>".$column."</td>";
						}
					}
					$value .= "</tr>";
					$isTable = true;
				}else if($isTable){
					$value = "</table>".$value;
					$isTable = false;
				}
			}
			$result .= $value.((!$isTable) ? "\n" : "");
		}
		return ($en) ? substr($result,0,-1) : $result;
	}
	private function toList($src){
		$result = "";
		$exclusion = 0;
		$indent = 0;
		$pre_indent = 0;
		$in = 0;
		$en = false;

		foreach(explode("\n",$src) as $value){
			$en = true;
			$exclusion = $this->isExclusion($value,$exclusion);

			if($exclusion === 0){
				if(preg_match("/^([\s\t]*)\+(.+)$/",$value,$match)){
					$indent = strlen(str_replace("\t","    ",$match[1]));

					if($in == 0){
						$value = "<ul>";
						$isList = true;
						$in++;
					}else if($pre_indent < $indent){
						$value = "<ul>";
						$in++;
					}else if($pre_indent > $indent){
						$value = "</ul>";
						$in--;
					}else{
						$value = "";
					}
					$pre_indent = $indent;
					$value .= "<li>".$match[2]."</li>";
				}else{
					$value .= str_repeat("</ul>",$in);
					$in = 0;
				}
			}
			$result .= $value."\n";
		}
		return ($en) ? substr($result,0,-1) : $result;
	}
}
