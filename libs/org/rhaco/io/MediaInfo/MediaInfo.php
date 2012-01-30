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
 * @conf string $cmd exiftoolのパス
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
	private function cmd($filename){
		$cmd = \org\rhaco\Conf::get('cmd','/usr/bin/exiftool');
		$out = \org\rhaco\Command::out($cmd.' '.$filename);
		return trim($out);
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
		$self = new self();
		$self->name($name);
		$self->size(sprintf('%u',@filesize($filename)));

		$info = getimagesize($filename);
		if($info !== false){
			try{
				switch($info[2]){
					case IMAGETYPE_JPEG:
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
						break;
					default:
						$self->type('photo');
						$self->filename(basename($filename));
						$self->width((isset($info[0]) ? $info[0] : null));
						$self->height((isset($info[1]) ? $info[1] : null));
						break;
				}
			}catch(\ErrorException $e){
				throw new \org\rhaco\io\MediaInfo\MediaInfoException("未対応");
			}
		}else{
			$exif = array();
			$data = $self->cmd($filename);

			foreach(explode("\n",$data) as $line){
				list($label,$value) = explode(":",$line,2);
				$exif[trim($label)] = trim($value);
				$self->raw(trim($label),trim($value));
			}
			if(!isset($exif['MIME Type']) || strpos(strtolower($exif['MIME Type']),'video') === false) throw new \org\rhaco\io\MediaInfo\MediaInfoException('not supported');

			$self->type('video');
			$self->filename(isset($exif['File Name']) ? $exif['File Name'] : basename($filename));
			$self->size(sprintf('%u',@filesize($filename)));
			if(isset($exif['Image Width'])) $self->width($exif['Image Width']);
			if(isset($exif['Image Height'])) $self->height($exif['Image Height']);
			if(isset($exif['Create Date'])) $self->create_date($exif['Create Date']);
			if(isset($exif['User Data mak'])) $self->make($exif['User Data mak']);
			if(isset($exif['User Data mod'])) $self->model($exif['User Data mod']);
			if(isset($exif['User Data xyz'])){
				list($xy,$z) = explode('/',$exif['User Data xyz']);
				if(preg_match("/([\-\+][\d\.]+)([\-\+][\d\.]+)/",$xy)){
					$self->latitude((float)$xy[1]);
					$self->longitude((float)$xy[2]);
				}
			}
			if(isset($exif['Rotation'])) $self->rotation($exif['Rotation']);
			if(isset($exif['Duration'])) $self->duration($exif['Duration']);
		}
		return $self;
	}
}