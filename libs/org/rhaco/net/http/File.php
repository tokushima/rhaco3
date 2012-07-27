<?php
namespace org\rhaco\net\http;
/**
 * 添付ファイルの操作
 * @author tokushima
 *
 */
class File{
	/**
	 * inlineで出力する
	 * @param File $file 出力するファイル
	 */
	static public function inline($filename){
		self::output_file_content($filename,'inline');
	}
	/**
	 * attachmentで出力する
	 * @param File $file 出力するファイル
	 */
	static public function attach($filename){
		self::output_file_content($filename,'attachment');
	}
	static private function output_file_content($filename,$disposition){
		if($filename instanceof \org\rhaco\io\File){
			if(is_file($filename->fullname())){
				$filename = $filename->fullname();
			}else{
				\org\rhaco\net\http\Header::send('Last-Modified',gmdate('D, d M Y H:i:s').' GMT');
				\org\rhaco\net\http\Header::send('Content-Type',$filename->mime().'; name='.$filename->name());
				\org\rhaco\net\http\Header::send('Content-Disposition',$disposition.'; filename='.$filename->name());
				print($filename->value());
				exit;
			}
		}
		if(is_file($filename)){
			$update = @filemtime($filename);			
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $update <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
				\org\rhaco\net\http\Header::send_status(304);
				exit;
			}
			\org\rhaco\net\http\Header::send('Last-Modified',gmdate('D, d M Y H:i:s',$update).' GMT');
			\org\rhaco\net\http\Header::send('Content-Type',self::mime($filename).'; name='.basename($filename));
			\org\rhaco\net\http\Header::send('Content-Disposition',$disposition.'; filename='.basename($filename));

			if(isset($_SERVER['HTTP_RANGE']) && preg_match("/^bytes=(\d+)\-(\d+)$/",$_SERVER['HTTP_RANGE'],$range)){
				list($null,$offset,$end) = $range;
				$length = $end - $offset + 1;
				
				\org\rhaco\net\http\Header::send_status(206);
				\org\rhaco\net\http\Header::send('Accept-Ranges','bytes');
				\org\rhaco\net\http\Header::send('Content-length',sprint('%u',$length));
				\org\rhaco\net\http\Header::send('Content-Range',sprintf('bytes %u-%u/%u',$offset,$end,filesize($filename)));

				print(file_get_contents($filename,null,null,$offset,$length));
				exit;
			}else{
				\org\rhaco\net\http\Header::send('Content-length',sprintf('%u',filesize($filename)));
				$fp = fopen($filename,'rb');
				while(!feof($fp)){
					echo(fread($fp,8192));
					flush();
				}
				fclose($fp);
				exit;
			}
		}
		\org\rhaco\net\http\Header::send_status(404);
		exit;
	}
	static private function mime($filename){
		$ext = (false !== ($p = strrpos($filename,'.'))) ? strtolower(substr($filename,$p+1)) : null;
		switch($ext){
			case 'jpg':
			case 'jpeg': return 'jpeg';
			case 'png':
			case 'gif':
			case 'bmp':
			case 'tiff': return 'image/'.$ext;
			case 'css': return 'text/css';
			case 'txt': return 'text/plain';
			case 'html': return 'text/html';
			case 'xml': return 'application/xml';
			case 'js': return 'text/javascript';
			case 'flv':
			case 'swf': return 'application/x-shockwave-flash';
			case '3gp': return 'video/3gpp';
			case 'gz':
			case 'tgz':
			case 'tar':
			case 'gz': return 'application/x-compress';
			case 'csv': return 'text/csv';
			case null:
			default:
				return 'application/octet-stream';
		}
	}
}