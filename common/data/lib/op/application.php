<?php
/**
 * Приложения
 *
 */
class application {
	/**
	 * Путь до www root
	 * @var string
	 */
	static $DOCUMENT_ROOT='';
	/**
	 * Получить корень сайта
	 * @return string
	 */
	static function getDocumentRoot(){
		if (self::$DOCUMENT_ROOT) return self::$DOCUMENT_ROOT;
		return $_SERVER['DOCUMENT_ROOT'].'/'.$_SERVER['SERVER_NAME'].'/htdocs';
	}
	/**
	 * Получить URL:
	 * @param $url
	 * @param $allowRewrite
	 * @return application_url
	 */
	static function fetchURL($url, $allowRewrite=false){
		if (file_exists(self::_getPagePath($url))) return new application_url($url);
		if (!$allowRewrite) return null;
		$action=array();
		while (true){
			list($url,$file)=application_url::splitURL($url);			
			$path=self::_getPagePath($url);
			$action[]=$file;
			if (file_exists($path)) break;
			if (!$url) return null;
		}
		
		return new application_url($url, array_reverse($action));
	}
	/**
	 * Получить путь к странице:
	 * @return string
	 */
	protected static function _getPagePath($url){
		$ret=self::getDocumentRoot().'/'.trim($url,'/');
		if (false==strpos($url,'.')){
			$ret.='/index.php';
		}		
		return $ret;
	}
	/**
	 * 
	 * @var application_context
	 */
	protected static $context;
	/**
	 * Контекст:
	 * @return unknown_type
	 */
	static function setContext($context){
		self::$context=$context;
	}
	/**
	 * Получить id контекста:	 
	 * @return int
	 */
	static function getContextID(){
		if (self::$context) return self::$context->getID();
		return 0;
	}
	/**
	 * @return application_context
	 */
	static function getContext(){
		return self::$context;
	}
	/**
	 * URL:
	 * @var application_url
	 */
	static $url=null;
	/**
	 * Выполнить URL:
	 * @param application_url $url
	 * @return void
	 */
	static function executeURL($url){
		self::$url=$url;
		return self::_getPagePath(implode('/',$url->url));
	}
		
	/**
	 * Вы
	 * @param $code
	 * @param $message
	 * @return unknown_type
	 */
	static function throwError($code, $message=''){
		global $bench;
		if (file_exists(self::getDocumentRoot().'/ru/errors/'.$code.'/index.php')){
			include_once self::getDocumentRoot().'/ru/errors/'.$code.'/index.php';
		}
		else {
			include_once self::$DOCUMENT_ROOT.'/ru/errors/500/index.php';
		}
		die();
	}
	/**
	 * Проверить контекст страницы:
	 * @return boolean
	 */
	static function validatePageContext($pageID){
		if ($contextID=self::getContextID()){
			$contextSql='CONTEXT_ID IN (0, '.$contextID.')';
		}
		else {
			$contextSql='CONTEXT_ID=0';
		}
		$res=db::sql_select("SELECT * FROM `APPLICATION_CONTEXT_CONTENT` WHERE CLASS='PAGE' AND CONTENT_ID=:CONTENT_ID AND ".$contextSql, array(
			'CONTENT_ID'=>(int)$pageID,			
		));
		return (count($res))?true:false;
	} 
}
?>