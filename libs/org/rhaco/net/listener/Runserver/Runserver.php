<?php
namespace org\rhaco\net\listener;
/**
 * 簡易HTTPサーバ
 * SocketListenerのmodule
 * @unfinished
 * @author tokushima
 */
class Runserver{
	protected $php_cmd;

	/**
	 * @module org.rhaco.net.listener.SocketListener
	 * @param string $address
	 * @param integer $port
	 */
	public function listen($address,$port){
		if(empty($this->php_cmd)) $this->php_cmd = isset($_ENV['_']) ? $_ENV['_'] : 'php';
		$this->output('checking for php('.$this->php_cmd.') ...');
		$cmd = new \org\rhaco\Command($this->php_cmd.' -v');
		if($cmd->stderr() != null) throw new \RuntimeException($cmd->stderr());

		$this->output($cmd->stdout());
		$this->output('Development server is running at http://'.$address.':'.$port);
		$this->output('Quit the server with CONTROL-C.');
	}
	/**
	 * @module org.rhaco.net.listener.SocketListener
	 * @param org.rhaco.net.listener.Channel $channel
	 */
	public function connect($channel){
		$head = $body = null;
		$method = $uri = $query = $boundary = null;
		$POST = $GET = $FILES = $SERVER = array();
		$message_len = $null_cnt = 0;

		try{
			$uid = uniqid(''); 

			while(true){
				$message = $channel->read();
				if($message === '') $null_cnt++;
				if($null_cnt > 5) break;

				if($method === null){
					$head .= $message;

					if(substr($head,-4) === "\r\n\r\n"){
						$lines = explode("\n",trim($head));
						if(!empty($lines)){
							$exp = explode(' ',array_shift($lines));
							if(sizeof($exp) >= 2) list($method,$uri) = $exp;
							if(strpos($uri,'?')){
								list($uri,$SERVER['QUERY_STRING']) = explode('?',$uri);
								parse_str($SERVER['QUERY_STRING'],$GET);
							}
							foreach($lines as $line){
								$exp = explode(':',$line,2);
								if(sizeof($exp) == 2){
									list($name,$value) = $exp;
									$SERVER['HTTP_'.str_replace(array('-'),array('_'),strtoupper(trim($name)))] = trim($value);
								}
							}
						}
						if($method === null || $method == 'GET') break;
						if(isset($SERVER['HTTP_CONTENT_TYPE']) && preg_match("/multipart\/form-data; boundary=(.+)$/",$SERVER['HTTP_CONTENT_TYPE'],$m)){
							$boundary = '--'.$m[1];
						}
					}
				}else if($method == 'POST'){
					$message_len += strlen($message);
					$body .= $message;
					if(
						(isset($SERVER['HTTP_CONTENT_LENGTH']) && $message_len >= $SERVER['HTTP_CONTENT_LENGTH'])
						|| (!isset($SERVER['HTTP_CONTENT_LENGTH']) && substr($body,-4) === "\r\n\r\n")
					){
						if(isset($boundary)){
							list($body) = explode($boundary."--\r\n",$body,2);
							foreach(explode($boundary."\r\n",$body) as $k => $block){
								if(!empty($block)){
									list($h,$b) = explode("\r\n\r\n",$block);
									list($b) = explode("\r\n",$b,2);
									
									if(preg_match("/\sname=([\"'])(.+?)\\1/",$h,$m)){
										$name = $m[2];
										
										if(preg_match("/filename=([\"'])(.+?)\\1/",$h,$m)){
											$tmp_name = self::work_path($uid,$k);
											\org\rhaco\io\File::write($tmp_name,$b);
											$FILES[$name] = array('name'=>$m[2],'tmp_name'=>$tmp_name,'size'=>filesize($tmp_name),'error'=>0);
										}else{
											$POST[$name] = $b;
										}
									}
								}
							}
						}else{
							parse_str($body,$POST);
						}
						break;
					}else if(!isset($SERVER['HTTP_CONTENT_LENGTH'])){
						break;
					}
				}else{
					$this->output('Unknown method: '.$method);
					break;
				}
			}
			if(!empty($uri)){
				$request_uri = $uri;
				$this->output('request uri: '.$uri);
				$uri = preg_replace("/\/+/","/",$uri);
				$uri_path = \org\rhaco\net\Path::absolute(getcwd(),\org\rhaco\net\Path::slash($uri,false,null));
				if(
					strpos($uri,'.php') === false 
					&& !is_file($uri_path)
					&& $uri != '/favicon.ico'
				){
					$exp = explode('/',\org\rhaco\net\Path::slash($uri,false,null),2);
					if(is_file(\org\rhaco\net\Path::absolute(getcwd(),$exp[0].'.php')) && isset($exp[1])){
						$uri = '/'.$exp[0].'.php/'.$exp[1];
					}else if(is_file(\org\rhaco\net\Path::absolute(getcwd(),'index.php'))){
						$uri = '/index.php'.$uri;
					}
				}
				if($request_uri != $uri) $this->output(' - rewrite uri: '.$uri);				
				if(strpos($uri,'.php/') !== false){
					$exp = explode('.php/',$uri,2);
					$uri = $exp[0].'.php';
					$SERVER['PATH_INFO'] = '/'.$exp[1];
				}
				$path = \org\rhaco\net\Path::absolute(getcwd(),\org\rhaco\net\Path::slash($uri,false,null));

				if(is_file($path)){
					$file = new \org\rhaco\io\File($path);
					if(substr($path,-4) == '.php') $file->mime('text/html');
					
					$headers = array();
					$headers[] = 'Content-Type: '.$file->mime();
					$headers[] = 'Connection: close';
					
					if(substr($path,-4) == '.php'){
						$SERVER['REQUEST_METHOD'] = $method;
						$REQUEST = array('_SERVER'=>$SERVER,'_POST'=>$POST,'_GET'=>$GET,'_FILES'=>$FILES);
						\org\rhaco\io\File::write(self::work_path($uid,'request'),serialize($REQUEST));
						$exec_command = $this->php_cmd.' '.$file->fullname().' -emulator '.$uid;
						$this->output(' -- '.$exec_command);
						$cmd = new \org\rhaco\Command($exec_command);
						
						if(is_file(self::work_path($uid,'header'))){
							$send_header = unserialize(\org\rhaco\io\File::read(self::work_path($uid,'header')));
							if(!empty($send_header) && is_array($send_header)){
								$headers = array_merge($headers,$send_header);
							}
						}
						foreach($headers as $k => $v){
							if(strpos($v,':') === false){
								$top = $headers[$k];
								unset($headers[$k]);
								array_unshift($headers,$top);
								break;
							}
						}
						$output_header = trim(implode("\r\n",$headers));
						if(strpos($output_header,'HTTP/') !== 0){
							if(strpos($output_header,'Location: ') !== false){
								$output_header = "HTTP/1.1 302 Found\r\n".$output_header;
							}else{
								$output_header = "HTTP/1.1 200 OK\r\n".$output_header;
							}
						}
						$channel->write($output_header."\r\n\r\n");
						$channel->write($cmd->stdout());
						\org\rhaco\io\File::rm(self::work_path($uid));
					}else{
						$channel->write("HTTP/1.1 200 OK\r\n".implode("\r\n",$headers)."\r\n\r\n");
						$fp = fopen($file->fullname(),'rb');
						while(!feof($fp)) $channel->write(fread($fp,4096));
						fclose($fp);
					}
				}else{
					$this->output($path.' not found');
					$channel->write($this->error(404,'Not Found','The requested URL '.$uri.' was not found on this server.'));
				}
			}
		}catch(\org\rhaco\net\listener\exception\ErrorException $e){
			$this->output($e->getMessage());
		}
	}
	private function error($status,$summary,$message){
		$headers[] = 'HTTP/1.1 '.$status.' '.$summary;
		$headers[] = 'Connection: close';
		$headers[] = 'Content-Type: text/html';

		return implode("\r\n",$headers)."\r\n\r\n".'<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">'
				.'<html><head>'
				.'<title>'.$status.' '.$summary.'</title>'
				.'</head><body>'
				.'<h1>'.$summary.'</h1>'
				.'<p>'.$message.'</p>'
				.'<hr>'
				.'</body></html>';		
	}
	static private function work_path($uid,$name=null){
		return getcwd().'/work/emulator/'.$uid.(!empty($name) ? '/'.$name : '');
	}
	private function output($msg){
		print($msg.PHP_EOL);
	}
}
