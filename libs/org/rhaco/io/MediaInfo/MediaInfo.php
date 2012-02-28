<?php
namespace org\rhaco\io;
/**
 * Exif情報
 * @author tokushima
 * @see http://www.sno.phy.queensu.ca/~phil/exiftool/
 * @var choice $type @['choices'=>['photo','video']]
 * @var string $name
 * @var string $filename
 * @var integer $width
 * @var integer $height
 * @var timestamp $create_date
 * @var integer $size
 * @var string $make
 * @var string $model
 * @var number $longitude
 * @var number $latitude
 * @var number $rotation
 * @var time $duration
 * @var mixed{} $raw @['hash'=>false]
 * @conf string $cmd exiftoolのパス、空が定義された場合はexif_read_dataを利用する
 */
class MediaInfo extends \org\rhaco\Object{
	protected $type = 'photo';
	protected $name;
	protected $filename;
	protected $width;
	protected $height;
	protected $create_date;
	protected $size;
	protected $make;
	protected $model;
	protected $longitude;
	protected $latitude;
	protected $rotation = 0;
	protected $duration = 0;

	protected $raw;

	protected function __init__(){
		$this->create_date = time();
	}
	/**
	 * 動画か
	 * @return boolean
	 */
	public function is_video(){
		return ($this->type === 'video');
	}
	/**
	 * EXIFを取得する
	 * @param string $filename
	 * @param string $name
	 * @return $this
	 */
	static public function get($filename,$name=null){
		if(empty($filename)) throw new \org\rhaco\io\MediaInfo\MediaInfoException('undef filename');
		if(!is_file($filename)) throw new \org\rhaco\io\MediaInfo\MediaInfoException($filename.' not found');
		$self = new self();
		$self->name($name);
		$self->size(sprintf('%u',@filesize($filename)));

		$info = getimagesize($filename);
		if($info !== false){
			try{
				switch($info[2]){
					case IMAGETYPE_JPEG:
						if(self::cmd() !== ''){
							$exif = self::exiftool($filename,$self);
						}else{
							$exif = exif_read_data($filename);
							foreach($exif as $k => $v) $self->raw($k,$v);
							$self->filename(isset($exif['FileName']) ? $exif['FileName'] : basename($filename));
							$self->width(isset($exif['ExifImageWidth']) ? $exif['ExifImageWidth'] : (isset($info[0]) ? $info[0] : null));
							$self->height(isset($exif['ExifImageLength']) ? $exif['ExifImageLength'] : (isset($info[1]) ? $info[1] : null));
							if(isset($exif['DateTimeOriginal'])) $self->create_date($exif['DateTimeOriginal']);
							if(isset($exif['Make'])) $self->make($exif['Make']);
							if(isset($exif['Model'])){
								$self->model($exif['Model']);
							}else if(isset($exif['Camera Model Name'])){
								$self->model($exif['Camera Model Name']);								
							}
							if(isset($exif['Orientation'])){
								switch($exif['Orientation']){
									case 3: $self->rotation(180); break;
									case 6: $self->rotation(90); break;
									case 8: $self->rotation(270); break;
								}				
							}
							if(isset($exif['GPSLatitudeRef']) && isset($exif['GPSLatitude']) 
								&& isset($exif['GPSLongitudeRef']) && isset($exif['GPSLongitude'])
							){
								list($a,$b) = explode('/',$exif['GPSLatitude'][0]);
								$latitude = $a / $b;
								list($a,$b) = explode('/',$exif['GPSLatitude'][1]);
								$latitude = $latitude + ($a / $b / 60);
								list($a,$b) = explode('/',$exif['GPSLatitude'][2]);
								$latitude = $latitude + ($a / $b / 3600);
								
								list($a,$b) = explode('/',$exif['GPSLongitude'][0]);
								$longitude = $a / $b;
								list($a,$b) = explode('/',$exif['GPSLongitude'][1]);
								$longitude = $longitude + ($a / $b / 60);
								list($a,$b) = explode('/',$exif['GPSLongitude'][2]);
								$longitude = $longitude + ($a / $b / 3600);
							
								$self->latitude($latitude * (($exif['GPSLatitudeRef'] == 'N') ? 1 : -1));
								$self->longitude($longitude * (($exif['GPSLongitudeRef'] == 'E') ? 1 : -1));
							}
						}
						break;
					default:
						$self->type('photo');
						$self->filename(basename($filename));
						$self->width((isset($info[0]) ? $info[0] : null));
						$self->height((isset($info[1]) ? $info[1] : null));
						break;
				}
			}catch(\ErrorException $e){
				throw new \org\rhaco\io\MediaInfo\MediaInfoException('Unsupported '.$e->getMessage());
			}
		}else{
			$exif = self::exiftool($filename,$self);
			if(!isset($exif['MIME Type']) || strpos(strtolower($exif['MIME Type']),'video') === false) throw new \org\rhaco\io\MediaInfo\MediaInfoException('not supported');
			$self->type('video');
		}
		return $self;
	}
	static private function cmd(){
		return \org\rhaco\Conf::get('cmd','/usr/bin/exiftool');		
	}
	static private function exiftool($filename,$self){
		$exif = array();
		$cmd = self::cmd();
		if(empty($cmd)) return array();
		
		$data = trim(\org\rhaco\Command::out($cmd.' '.$filename));
		foreach(explode("\n",$data) as $line){
			list($label,$value) = explode(':',$line,2);
			if(!isset($exif[trim($label)])) $exif[trim($label)] = trim($value);
			$self->raw(trim($label),trim($value));
		}
		$self->filename(isset($exif['File Name']) ? $exif['File Name'] : basename($filename));
		$self->size(sprintf('%u',@filesize($filename)));
		$self->width(isset($exif['Image Width ']) ? $exif['Image Width '] : (isset($info[0]) ? $info[0] : null));
		$self->height(isset($exif['Image Height']) ? $exif['Image Height'] : (isset($info[1]) ? $info[1] : null));
		
		$create_date = null;
		if(isset($exif['Create Date'])){
			$create_date = $exif['Create Date'];
		}else if(isset($exif['Date/Time Original'])){
			$create_date = $exif['Date/Time Original'];
		}else if(isset($exif['Modify Date  Date'])){
			$create_date = $exif['Modify Date'];
		}
		if(!empty($create_date)){
			if(preg_match('/(\d{4}[^\d]\d{2}[^\d]\d{2} \d{2}[^\d]\d{2}[^\d]\d{2})/',$create_date,$m)) $create_date = $m[1];
			$self->create_date($create_date);
		}
		if(isset($exif['Make'])){
			$self->make($exif['Make']);
		}else if(isset($exif['User Data mak'])){
			$self->make($exif['User Data mak']);
		}
		if(isset($exif['Model'])){
			$self->model($exif['Model']);
		}else if(isset($exif['Camera Model Name'])){
			$self->model($exif['Camera Model Name']);								
		}else if(isset($exif['User Data mod'])){
			$self->model($exif['User Data mod']);
		}
		if(isset($exif['Orientation'])){
			switch($exif['Orientation']){
				case 3: $self->rotation(180); break;
				case 6: $self->rotation(90); break;
				case 8: $self->rotation(270); break;
			}
		}
		if(isset($exif['GPS Latitude']) && isset($exif['GPS Longitude'])){
			if(preg_match('/(\d+)\sdeg\s(\d+)\'\s([\d\.]+)\s*\"\s([N|E])/',$exif['GPS Latitude'],$m)){
				$self->latitude($m[1]+($m[2]/60)+($m[3]/3600)*(($m[4]=='N') ? 1 : -1));
			}
			if(preg_match('/(\d+)\sdeg\s(\d+)\'\s([\d\.]+)\s*\"\s([N|E])/',$exif['GPS Longitude'],$m)){
				$self->longitude($m[1]+($m[2]/60)+($m[3]/3600)*(($m[4]=='E') ? 1 : -1));
			}
		}else if(isset($exif['User Data xyz'])){
			list($xy,$z) = explode('/',$exif['User Data xyz']);
			if(preg_match("/([\-\+][\d\.]+)([\-\+][\d\.]+)/",$xy)){
				$self->latitude((float)$xy[1]);
				$self->longitude((float)$xy[2]);
			}
		}
		if(isset($exif['Rotation'])) $self->rotation($exif['Rotation']);
		if(isset($exif['Duration'])) $self->duration($exif['Duration']);
		return $exif;
	}
}