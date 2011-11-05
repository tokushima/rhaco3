<?php
namespace org\rhaco\net;
/**
 * セッションを操作する
 * @see http://jp2.php.net/manual/ja/function.session-set-save-handler.php
 * @author tokushima
 * @conf string $session_name セッション名
 * @conf string $session_limiter キャッシュリミッタ nocache,private,private_no_expire,public
 * @conf integer $session_expire キャッシュの有効期限(sec)
 * @conf string $module セッションの実装モジュールのパッケージ名
 */
class Session{
	private $ses_n;

	/**
	 * セッションを開始する
	 * @param string $name
	 * @return $this
	 */
	public function __construct($name='sess'){
		$this->ses_n = $name;
		if('' === session_id()){
			$session_name = \org\rhaco\Conf::get('session_name','SID');
			if(!ctype_alpha($session_name)) throw new \InvalidArgumentException('session name is is not a alpha value');
			session_cache_limiter(\org\rhaco\Conf::get('session_limiter','nocache'));
			session_cache_expire((int)(\org\rhaco\Conf::get('session_expire',10800)/60));
			session_name();

			$module = \org\rhaco\Conf::get('module');
			if(!empty($module)){
				$r = new \ReflectionClass('\\'.str_replace('.','\\',$module));
				$o = $r->newInstance();
				ini_set('session.save_handler','user');
				$noop_func = create_function('','');
				$true_func = create_function('','return true;');
				session_set_save_handler(
					(method_exists($o,'session_open') ? array($o,'session_open') : $true_func)
					,(method_exists($o,'session_close') ? array($o,'session_close') : $true_func)
					,(method_exists($o,'session_read') ? array($o,'session_read') : $noop_func)
					,(method_exists($o,'session_write') ? array($o,'session_write') : $true_func)
					,(method_exists($o,'session_destroy') ? array($o,'session_destroy') : $true_func)
					,(method_exists($o,'session_gc') ? array($o,'session_gc') : $true_func)
				);
				if(isset($this->vars[$session_name])){
					if((method_exists($o,'session_verify') ? array($o,'session_verify') : $noop_func) !== true) session_regenerate_id(true);
				}
			}
			session_start();
		}
	}
	/**
	 * セッションの設定
	 * @param string $name
	 * @param mixed $value
	 */
	public function vars($key,$value){
		$_SESSION[$this->ses_n][$key] = $value;
	}
	/**
	 * セッションの取得
	 * @param string $n
	 * @param mixed $d 未定義の場合の値
	 * @return mixed
	 */
	public function in_vars($n,$d=null){
		return isset($_SESSION[$this->ses_n][$n]) ? $_SESSION[$this->ses_n][$n] : $d;
	}
	/**
	 * キーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_vars($n){
		return array_key_exists($n,$_SESSION);
	}
	/**
	 * セッションを削除
	 */
	public function rm_vars(){
		foreach(((func_num_args() === 0) ? array_keys($_SESSION[$this->ses_n]) : func_get_args()) as $n) unset($_SESSION[$this->ses_n][$n]);
	}
	static public function __shutdown__(){
		if('' != session_id()) session_write_close();
	}
}