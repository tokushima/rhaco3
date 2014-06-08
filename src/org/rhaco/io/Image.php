<?php
namespace org\rhaco\io;
/**
 * 画像操作ライブラリ
 * Jpegの処理時には非圧縮の画像に変換するので、その分メモリが必要になる
 * @author tokushima
 * @author riaf
 * @var integer $width 画像の幅
 * @var integer $height 画像の高さ
 * @var choice $type 画像の種類 @['choices'=>['jpg','gif','png','bmp']]
 * @var number $quality 品質 0-10 @['max'=>10]
 * @var boolean $resample リサイズ時に再サンプリングを行う
 */
class Image extends \org\rhaco\Object{
	private $resource;
	protected $width = 100;
	protected $height = 100;
	protected $type = 'jpg';
	protected $quality = 8;
	protected $resample = true;

	protected function __del__(){
		if(is_resource($this->resource)) imagedestroy($this->resource);
	}
	public function div($quality){
		$this->quality($quality);
		return $this;
	}
	protected function __new__($width,$height,$color='#000000'){
		if($width !== null){
			$this->width = $width;
			$this->height = $height;
			$this->resource = imagecreate($width,$height);
			imagefill($this->resource,0,0,$this->get_color($color));
		}
	}
	private function resource($resource,$type=null){
		$this->resource = $resource;
		$this->width = imagesx($this->resource);
		$this->height = imagesy($this->resource);
		if($type !== null) $this->type($type);
	}
	/**
	 * 文字列から新規インスタンスを返す
	 * @param string $src
	 * @return self
	 */
	static public function parse($src){
		$self = new self();
		$self->resource(imagecreatefromstring($src));
		return $self;
	}
	/**
	 * ファイル名から新規インスタンスを返す
	 * @param string $filename
	 * @return self
	 */
	static public function load($filename){
		if(!is_file($filename)) throw new Image\ImageException('`'.$filename.'` file not found.');
		$size = getimagesize($filename);
		if($size === false) throw new Image\ImageException('invalid file');
		$self = new self(null,null);
		try{
			switch($size[2]){
				case IMAGETYPE_GIF:
					$self->resource(imagecreatefromgif($filename),'gif');
					break;
				case IMAGETYPE_JPEG:
					$self->resource(imagecreatefromjpeg($filename),'jpg');
					break;
				case IMAGETYPE_PNG:
					$self->resource(imagecreatefrompng($filename),'png');
					break;
				case IMAGETYPE_WBMP:
					$self->resource(imagecreatefromwbmp($filename),'bmp');
					break;
				default:
					throw new Image\ImageException();
			}
		}catch(Image\ImageException $e){
			throw new Image\ImageException('invalid data: '.$size[2]);
		}catch(\Exception $e){
			throw new Image\ImageException($e->getMessage());
		}
		return $self;
	}
	private function image_resize($dst_width,$dst_height){
		switch($this->type){
			case 'gif':
				$dst_image = imagecreate($dst_width,$dst_height);
				$tcolor = imagecolorallocate($dst_image,255,255,255);
				imagecolortransparent($dst_image,$tcolor);
				imagefilledrectangle($dst_image,0,0,$dst_width,$dst_height,$tcolor);
				break;
			default:
				$dst_image = imagecreatetruecolor($dst_width,$dst_height);
				break;
		}
		if($this->resample){
			imagecopyresampled($dst_image,$this->resource,0,0,0,0,$dst_width,$dst_height,$this->width,$this->height);
		}else{
			imagecopyresized($dst_image,$this->resource,0,0,0,0,$dst_width,$dst_height,$this->width,$this->height);
		}
		imagedestroy($this->resource);
		$this->width = $dst_width;
		$this->height = $dst_height;
		$this->resource = $dst_image;
		return $this;
	}
	/**
	 * 回転
	 * @param integer $angle 角度
	 * @param string $bg_color カバーされない部分の色(RGB. #000000)
	 */
	public function rotate($angle,$bg_color='#000000'){
		if($angle != 0){
			$resource = imagerotate($this->resource,$angle,$this->get_color($bg_color),0);
			if($resource === false) throw new Image\ImageException('rotate fail');
			$this->resource = $resource;
		}
		return $this;
	}
	private function get_color($rgb){
		return imagecolorallocate($this->resource,hexdec(substr($rgb,1,2)),hexdec(substr($rgb,3,2)),hexdec(substr($rgb,5,2)));
	}
	/**
	 * リサイズを行う
	 * @param integer $width
	 * @param integer $height
	 * @return $this
	 */
	public function resize($width, $height){
		return $this->resize_width($width)->resize_height($height);
	}
	/**
	 * サムネイルを作成する
	 * @param integer $width
	 * @param integer $height
	 * @param string $color #000000
	 * @return $this
	 */
	public function thumbnail($width, $height,$color='#000000'){
		$this->resize_width($width)->resize_height($height);
		if($width > $this->width || $height > $this->height){
			$dst_image = imagecreatetruecolor($width,$height);
			imagefill($dst_image,0,0,$this->get_color($color));
			imagecopy($dst_image,$this->resource
							,((int)(($width - $this->width) / 2)),((int)(($height - $this->height) / 2))
							,0,0
							,$this->width,$this->height
			);
			imagedestroy($this->resource);
			$this->width = $width;
			$this->height = $height;
			$this->resource = $dst_image;
		}
		return $this;
	}
	/**
	 * 切り抜き
	 * @param integer $x
	 * @param integer $y
	 * @param integer $width
	 * @param integer $height
	 * @return $this
	 */
	public function scraps($x,$y,$width,$height){
		$dst_image = imagecreatetruecolor($width,$height);
		imagecopy($dst_image,$this->resource
						,0,0
						,$x,$y
						,$this->width,$this->height
		);
		imagedestroy($this->resource);
		$this->width = $width;
		$this->height = $height;
		$this->resource = $dst_image;
		return $this;
	}
	/**
	 * 幅指定のリサイズを行う
	 * @param integer $width
	 * @param boolean $keep
	 * @return $this
	 */
	public function resize_width($width,$keep=false){
		$dst_height = $keep ? $this->height : ($this->height / ($this->width / $width));
		return $this->image_resize($width,$dst_height);
	}
	/**
	 * 縦指定のリサイズを行う
	 * @param int $height
	 * @param boolean $keep
	 * @return $this
	 */
	public function resize_height($height,$keep=false){
		$dst_width  = $keep ? $this->width : ($this->width / ($this->height / $height));
		return $this->image_resize($dst_width,$height);
	}
	/**
	 * 画像が指定サイズより大きい場合にリサイズを行う
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return $this
	 */
	public function fit($width,$height){
		return $this->fit_width($width)->fit_height($height);
	}
	/**
	 * 画像の横が指定サイズより大きい場合にリサイズを行う
	 * @param integer $width
	 * @return $this
	 */
	public function fit_width($width){
		if($width < $this->width) $this->resize_width($width);
		return $this;
	}
	/**
	 * 画像の縦が指定サイズより大きい場合にリサイズを行う
	 * @param integer $height
	 * @return $this
	 */
	public function fit_height($height){
		if($height < $this->height) $this->resize_height($height);
		return $this;
	}
	/**
	 * ファイルに出力する
	 * @param string $filename
	 * @param string $type
	 * @return string
	 */
	public function write($filename,$type=null){
		if(!is_dir(dirname($filename))) \org\rhaco\io\File::mkdir(dirname($filename));
		if($type !== null) $this->type($type);
		$bool = false;
		$ext = image_type_to_extension($this->type_no(),true);
		if($ext == '.jpeg') $ext = '.jpg';
		if(!preg_match('/'.preg_quote($ext).'$/i',$filename)) $filename = $filename.$ext;

		switch($this->type){
			case 'gif': $bool = imagegif($this->resource,$filename); break;
			case 'jpg': $bool = imagejpeg($this->resource,$filename,ceil($this->quality*10)); break;
			case 'png': $bool = imagepng($this->resource,$filename,10-ceil($this->quality)); break;
			case 'bmp': $bool = imagewbmp($this->resource,$filename); break;
		}
		if(!$bool) throw new Image\ImageException('invalid type');
		return $filename;
	}
	/**
	 * イメージを取得する
	 * @param string $type
	 * @return string binary
	 */
	public function read($type){
		ob_start();
			$this->output_image($type);
		return ob_get_clean();
	}
	private function output_image($type=null){
		if($type !== null) $this->type($type);
		switch($this->type()){
			case 'gif': return imagegif($this->resource,null);
			case 'jpg': return imagejpeg($this->resource,null,ceil($this->quality*10));
			case 'png': return imagepng($this->resource,null,10-ceil($this->quality));
			case 'bmp': return imagewbmp($this->resource,null);
		}
		throw new Image\ImageException('invalid type');
	}
	/**
	 * 標準出力に出力する
	 * @param string $type
	 */
	public function output($type=null){
		if($type !== null) $this->type($type);
		header('Content-Type: '.image_type_to_mime_type($this->type_no()));
		return $this->output_image($type);
	}
	private function type_no(){
		switch($this->type){
			case 'gif': return IMAGETYPE_GIF;
			case 'jpg': return IMAGETYPE_JPEG;
			case 'png': return IMAGETYPE_PNG;
			case 'bmp': return IMAGETYPE_WBMP;
		}
		return IMAGETYPE_JPEG;
	}
	/**
	 * 文字列のサイズを取得する
	 * @param string $font_file フォントファイルのパス
	 * @param integer $font_size ポイント数
	 * @param string $text 対象の文字列
	 * @param integer $linespacing 行間
	 * @param integer $letterspacing 文字間
	 * @throws \InvalidArgumentException
	 */
	static public function text_size($font_file,$font_size,$text,$linespacing=2,$letterspacing=1){
		$lines = explode(PHP_EOL,str_replace(array("\r\n","\r","\n"),PHP_EOL,$text));
		$width = $height = $sp = 0;
		$char_width = $char_height = $line_height = array();
		foreach($lines as $i => $line){
			$line_height[$i] = $height;
			$w = $h = 0;
			$strlen = mb_strlen($line);
			for($y=0;$y<$strlen;$y++){
				$result = imagettfbbox($font_size,0,$font_file,mb_substr($line,$y,1));
				if($result === false) throw new \InvalidArgumentException('failure '.$font_path);
				$char_width[$i][$y] = $w;
				$w = $w + ($result[2] - $result[6]) + ((($strlen-1) > $y) ? $letterspacing : 0);
				$h = ($result[3] - $result[7]);
			}
			$char_height[$i] = $h;
			if($w > $width) $width = $w;
			$height = $height + $h + (($i<sizeof($lines)-1) ? $linespacing : 0);
		}
		return array($width,$height,$char_width,$char_height,$line_height);
	}
	/**
	 * テキストを挿入する
	 * @param string $text　挿入する文字列
	 * @param integer $x 横位置
	 * @param integer $y 縦位置
	 * @param string $font_file フォントファイルのパス
	 * @param integer $font_size ポイント数
	 * @param string $color 文字色 #000000
	 * @param integer $linespacing 行間
	 * @param integer $letterspacing 文字間
	 * @return $this
	 */
	public function set_text($text,$x,$y,$font_file,$font_size,$color='#000000',$linespacing=2,$letterspacing=1){
		list($bx,$by,$char_width,$char_height,$line_height) = self::text_size($font_file,$font_size,$text,$linespacing,$letterspacing);
		$c = $this->get_color($color);
		$lines = explode(PHP_EOL,str_replace(array("\r\n","\r","\n"),PHP_EOL,$text));
		
		foreach($line_height as $i => $y){
			foreach($char_width[$i] as $j => $x){
				imagettftext($this->resource,$font_size,0,$x,$y+$char_height[$i],$c,$font_file,mb_substr($lines[$i],$j,1));
			}
		}
		return $this;
	}
	/**
	 * 網点
	 * @param integer $range
	 * @param string $color
	 * @return $this
	 */
	public function halftone($range,$color='#ffffff'){
		for($x=0;$x<$this->width;$x+=$range){
			for($y=0;$y<$this->height;$y+=$range) imagesetpixel($this->resource,$x,$y,$this->get_color($color));
		}
		return $this;
	}
	/**
	 * ぼかし(ガウス)
	 * @return $this
	 */
	public function gaussian(){
		imagefilter($this->resource,IMG_FILTER_GAUSSIAN_BLUR);
		return $this;
	}
	/**
	 * グレースケール
	 * @return $this
	 */
	public function grayscale(){
		imagefilter($this->resource,IMG_FILTER_GRAYSCALE);
		return $this;
	}
	/**
	 * 矩形を挿入する
	 * @param integer $xs 横の開始位置
	 * @param integer $xs 横の開始位置
	 * @param integer $xe 横の終了位置
	 * @param integer $ye 横の終了位置
	 * @param string $color 背景色 #000000
	 * @return $this
	 */
	public function rectangle($xs,$xs,$xe,$ye,$color){
		imagefilledrectangle($this->resource,$xs,$xs,$xe,$ye,$this->get_color($color));
		return $this;
	}
}
