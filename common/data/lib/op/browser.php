<?php
class browser {
	static $charset='utf-8';
	/**
	 * 
	 * @return string
	 */
	function get($URL){
		self::request($URL);
	}
	/**
	 * UserAgent
	 * @var string
	 */
	static $userAgent=null;
	
	static function request($URL, $params=null){
		$c= curl_init($URL);
		curl_setopt($c,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER,true);
		return new browser_response(curl_exec($c), curl_getinfo($c));
	}
}
/**
 * 
 *
 */
class browser_response extends DataStore{
	
	var $response=null;
	
	function __construct($response, $info){
		
		$info['charset']=browser::$charset;
		
		if (isset($info['content_type'])){
			if (preg_match('@charset\=(.*)$@', $info['content_type'],$m)){
				$info['charset']==$m[1];
			}	
		}
		
		parent::__construct($info);
		
		if ($this->charset||$this->charset!=browser::$charset){
			$this->response=iconv($this->charset, browser::$charset, $response);
		}
		else {
			$this->response=$response;
		}	
	}
		
	function __toString(){
		return $this->response;
	}
	
	protected $_html;
	/**
	 * Получить нормальный HTML
	 * @return string
	 */
	function fineHTML(){
		if ($this->_html) return $this->_html;
		$this->_html=preg_replace('@<script(.*?)>(.*?)</script>@is','',$this->response);
		return $this->_html;
	}
	
	function _getInfo($path){
		
	}
}
?>