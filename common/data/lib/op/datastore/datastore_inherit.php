<?php

class datastore_inherit extends datastore {
	/**
	 * Объект высшего уровня
	 *
	 * @var iDC
	 */
	var $Parent;
	function __construct($Info, $Parent=null){
		parent::__construct($Info);
		$this->Parent=$Parent;
	}
	
	function setParent($Parent){
		$this->Parent= &$Parent;
	}
	
	protected function _getInfo($path){
		if ($path==null) return $this->Info;//parse: $Me->getInfo('$');	
		if (!$path[0]&&$this->Parent){
			return $this->Parent->_getInfo(array_slice($path, 1));
		}
		else {			
			return parent::_getInfo($path);
		}
		
	}
}
?>