<?php
class request extends DataStore {
	static $Request=null;
	
	function __init(){
		self::$Request=&$_REQUEST;
		self::$CachePath=STORAGE_PATH.'tmp/upload/';
		self::$MaxCachedFileSize=107374182400;//100M
	}
	/**
	 * Строка из запроса
	 *
	 * @param string $Name	  ключ
	 * @param mixed  $Default значение по умолчанию
	 * @param string/array $Match  регулярное выражение/массив допустимых значений для проверки
	 * @return string
	 */
	static function String($Name, $Default=null, $Match=null){
		return (isset($_REQUEST[$Name])&&(!$Match||(is_string($Match)&&preg_match($Match,$_REQUEST[$Name]))||(is_array(
		$Match)&&(in_array($_REQUEST[$Name],$Match)))))?$_REQUEST[$Name]:$Default;
	}
	/**
	 * Enter description here...
	 *
	 * @param string $Name
	 * @param int $Min	минимальное значение (вкл) false- не ограниченно, по умолчанию- не отрицательные
	 * @param int $Mod	округлить &limit=11-> request::Int('limit',0,10) -> 10	
	 * @param int $Max	максимальное значение (по умолчанию - MySql Unsigned Int)
	 * 
	 * @return int
	 */
	static function Int($Name, $Default=0, $Min=0, $Mod=false, $Max=4294967295){
		 if (!isset(self::$Request[$Name])) return $Default;
		 $ret=(int)self::$Request[$Name];
		 if ($Min!==false&&$ret<$Min) return $Default;
		 if ($Max!==false&&$ret>$Max) return $Default;
		 if ($Mod) $ret-=$ret%$Mod;
		 return $ret;
	}
	/**
	 * Распарсить дату
	 *
	 * @param string $DateFormat
	 */
	static function ts($Name, $DateFormat='d.m.Y', $Default=0){
		if ($r=request::String($Name)) return str::ParseDate($r, $DateFormat);
		else return $Default;
	}
	static function Cookie($Name,$Default=0,$Regexp=null){
		return (isset($_COOKIE[$Name])&&(!$Regexp||preg_match($Regexp,$_COOKIE[$Name])))?$_COOKIE[$Name]:$Default;
	}
	/**
	 * Массив
	 *
	 * @param string $Name
	 * @return array
	 */
	static function Arr($Name, $Default=null){
		return (isset(self::$Request[$Name])&&is_array(self::$Request[$Name]))?self::$Request[$Name]:$Default; 
	}
	/**
	 * Данные сессии
	 *
	 * @param string $Name
	 * @param mixed $Default
	 * @param string $Regexp
	 */
	static function Session($Name, $Default=null, $Regexp=null){
		if (isset($_SESSION[$Name])){
			return (!$Regexp||preg_match($Regexp,$_SESSION[$Name]))?$_SESSION[$Name]:$Default;
		}
		else return $Default;
	}
	static function Debug(){
		str::print_r($_GET,'GET');
		str::print_r($_POST,'POST',false);
		str::print_r($_COOKIE,'COOKIE',false);
		str::print_r($_SESSION,'SESSION',false);
		str::print_r($_SERVER['HTTP_USER_AGENT'],'HTTP_USER_AGENT',false);
		str::print_r($_SERVER['REMOTE_ADDR'],'REMOTE_ADDR',false);
		str::print_r($_SERVER['HTTP_REFERER'],'HTTP_REFERER',false);		
	}
	
	static function UserAgent(){
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	static function Referer(){
		return $_SERVER['HTTP_REFERER'];
	}
	/**
	 * Запомнить перенаправление:
	 *
	 */
	static function rememberRedirect(){
		if (!$_SESSION['ReturnTo']=request::String('ReturnTo')) $_SESSION['ReturnTo']=self::Referer();
	}
	
	static function getRedirect(){
		if ($ret=$_SESSION['ReturnTo']){
			unset($_SESSION['ReturnTo']);
			return $ret;
		}
		else {	
					
			if (self::Referer()!='http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']){
				return self::Referer();
			}
			else {
				return '/';
			}
		}
	}
	/**
	 * Список зареврайтенных папок:
	 */
	static function Rewrite(){
		list($rewrite)=split('\?|\.',substr($_SERVER['REQUEST_URI'],strlen(dirname($_SERVER['SCRIPT_NAME']))));
		return explode('/',trim($rewrite,'/'));
	}
	/**
	 * Получить абсолютный Url:
	 * @return string
	 */
	static function getUrl($Path=''){
		static $BaseUrl;
		if (!$BaseUrl) $BaseUrl=dirname($_SERVER['SCRIPT_NAME']);
		return $BaseUrl.'/'.$Path;
	}

	function __construct(){
		parent::__construct(self::$Request);						
		$this->Info['_FILES']=$_FILES;
	}
	/**
	 * Путь для кэша файла при serialize
	 *
	 * @var string
	 */
	static $CachePath;
	/**
	 * Максимальный р-р файла кэшируемого при serialize:
	 */
	static $MaxCachedFileSize;
	/**
	 * При сериализации сторим свежезагруженные файлы на диск
	 *
	 */
	function __sleep(){
		
//		if (!$this->isWakeup){
//			foreach ($this->Info['_FILES'] as $id=>$File){
//				if (!is_readable($File['tmp_name'])) continue;//В жопу надо
//				
//				if (!is_uploaded_file($File['tmp_name'])) throw new Exception('Request compromised');			
//				if (filesize($File['tmp_name'])>self::$MaxCachedFileSize) throw new Exception('Submited file to big');
//				
//				$dir=md5($File['tmp_name']);
//				$dir=self::$CachePath.substr($dir,0,2).'/'.substr($dir,2,2).'/';
//				filecommon::mkdir($dir);
//				$dir.=tempnam($dir,'');
//				copy($File['tmp_name'],$dir);
//				$this->Info['_FILES'][$id]['tmp_name']=$dir;
//			}
//		}
		return array('Info');
	}
	/**
	 * Флаг- запрос восстановлен:
	 *
	 * @var string
	 */
	var $isWakeup=false;
	/**
	 * Выставляем флажок, что запрос восстановлен:
	 */
	function __wakeup(){
		$this->isWakeup=true;
	}
	
	function _getInfo($path){
		if ($path[0]=='_FILES'&&isset($path[1])){
			if (!isset($this->Info['_FILES'][$path[1]]['tmp_name'])) return null;
			if (!is_readable($this->Info['_FILES'][$path[1]]['tmp_name'])){
								
				return null;			
			}
			if (!$this->isWakeup&&!is_uploaded_file($this->Info['_FILES'][$path[1]]['tmp_name'])) throw new Exception('Request compromised');
		}
		return parent::_getInfo($path);
	}

	function __toString(){
		$Info=$this->Info;
		unset($Info['_FILES']);
		ksort($Info);
		return http_build_query($Info);
	}
	
	const utf='utf-8';
	const win='windows-1251';
	const koi='koi8-r';
		
	function ajaxString($key){
		if (isset($_GET[$key])) $ret=$_GET[$key];
		elseif (isset($_POST[$key])) $ret=$_POST[$key];
		else return null;
		$ret=urldecode($ret);		
//		str::print_r($ret);
//		die();
		if (systemEncoding!=utf){
		 	return (function_exists('iconv'))?iconv(self::utf,systemEncoding,$ret):null;
		}
		else {
			return $ret;
		}
		//else return $ret;			
	}
}
?>