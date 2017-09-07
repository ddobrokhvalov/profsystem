<?php
/**
 * Сайт:
 * @author atukmanov
 *
 */
class rbcc5_site extends rbcc5_object {
	/**
	 * Версия
	 * @var unknown_type
	 */
	protected $_version=null;
	/**
	 * Получить версию:
	 * @return unknown_type
	 */
	function getVersion(){
		
		if ($this->_version) return $this->_version;
		
		return self::$env['version'];
	}
	/**
	 * Загрузить сайт по хосту
	 * @return rbcc5_site
	 */
	static function loadByHost($host){
		$sel= new rbcc5_select('SITE');
		$host=dbselect::Escape(preg_replace('@^www.@','',$host));
		$sel->Where("HOST='{$host}' OR TEST_HOST='{$host}'");
		if ($ret=rbcc5_object::fetchObject($sel)){
			$ret->_version=($ret->HOST==$host)?rbcc5_select::workVersion:rbcc5_select::testVersion;
			return $ret;
		}
	}
	/**
	 * Сайт по-умолчанию:
	 * @return rbcc5_site
	 */
	static function loadDefaultSite(){
		$sel= new rbcc5_select('SITE');
		$sel->OrderBy('SITE_ID','ASC');
		return rbcc5_object::fetchObject($sel);
	}
	/**
	 * Загрузить по id:
	 * @param $id
	 * @return rbcc5_site
	 */
	static function loadByID($id){
		return rbcc5_object::loadByID($id, 'SITE');
	}
	/**
	 * (non-PHPdoc)
	 * @see common/data/lib/op/op/rbcc5/rbcc5_object#_getInfo($path)
	 */
	function _getInfo($path){
		if (str::lower($path[0])=='version') return $this->getVersion();		
		return parent::_getInfo($path);
	}
	/**
	 * Протокол для URL:
	 * @var string
	 */
	static $protocol='http';
	/**
	 * Получить host
	 * @return string
	 */
	function getURL(){
		return self::$protocol.'://'.(($this->getVersion()==rbcc5_select::workVersion)?$this->HOST:$this->TEST_HOST).'/';
	}
	
	function getEnv(){
		return array('version'=>$this->getVersion(),'site_id'=>$this->getID());
	}
}
?>