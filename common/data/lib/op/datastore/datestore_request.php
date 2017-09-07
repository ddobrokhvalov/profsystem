<?php
/**
 * Запрос к iDataStore объекту, позволяет транслировать данные.
 * 
 * 
 */
class datastore_query extends DataStore implements Iterator, Countable   {
	/**
	 * Запрос:
	 *
	 * @var string
	 */
	var $Request=null;
	
	private $iso;
	/**
	 * Данные:
	 *
	 * @var datastore
	 */
	var $ds=null;
	/**
	 * 
	 */
	function __construct($Request, $ds){
		$this->Request=(is_object($Request))?$Request:self::_argv2path($Request);
		$this->iso=is_object($this->Request);
		$this->ds=&$ds; 
	}
	/**
	 * Подзапрос:
	 *
	 * @param string/array $NewRequest	подзапрос
	 */
	function getSubRequest($NewRequest){		
		$nr= self::_argv2path($NewRequest);
		if ($this->iso){
			return new datastore_query($this->Request->getSubrequest($NewRequest),&$this->ds);
		}
		else {
			return new datastore_query(array_merge($this->Request, $NewRequest), &$this->ds);
		}
	}
		
	function _getInfo($path){
		if (!$this->ds) return null;
		if (!$this->iso) return $this->ds->getInfo($this->Request,$path);
		else return $this->iso->getInfo($this->Request->translatePath($path));
	}
	/**
	 * Настройка:
	 *
	 * @param unknown_type $Name
	 * @param unknown_type $Default
	 * @param unknown_type $allow_empty
	 * @return unknown
	 */
	function getSetting($Name, $Default, $allow_empty){
		if (!$this->ds) return null;
		$Path=$this->_argv2path($Name);
		return $this->ds->getSetting(($this->iso)?$this->Request->translatePath($path):array_merge($this->Request,$Path), $Default, $allow_empty);
	}

	var $var=null;
	
	public function rewind() {
		if ($this->ds) return false;
	    if (!$this->var) $this->var=@array_keys($this->ds->getInfo($this->Request));
    	reset($this->var);
  	}

  	public function current() {
  		if (!$this->ds) return false;	
    	if (false!==$var = current($this->var)){    	
    		return $this->getSubRequest($var);
    	}
    	else return false;
  	}

  	public function key() {
    	return current($this->var);    	
  	}

  	public function next() {
    	if (false!== $var = next($this->var)) return $this->getSubRequest($var);
    	else return false;
    	
  	}

  	public function valid() {
    	$var = $this->current() !== false;    	
    	return $var;
  	}
  	
  	function _count(){
  		return count($this->getInfo());
  	}	
}
?>