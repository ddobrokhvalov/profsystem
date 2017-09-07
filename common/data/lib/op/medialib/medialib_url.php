<?php
/**
 * Формирователь URL (для смарти)
 *
 */
class medialib_url implements ArrayAccess {
		
	protected $baseURL;
	
	function __construct($baseURL){
		$this->baseURL=$baseURL;
	}
	
	function offsetExists($offset){
		return true;
	}
		
	
	function getURL($previewType){
		if (preg_match('/^[a-z|\d]{1,25}$/',$previewType)){
			return str_replace('{$preview}',$previewType,$this->baseURL);
		}
		else {
			throw new Exception('invalid preview type');
		}
	}
	
	function __tostring(){
		return $this->getURL('default');
	}
	
	function offsetGet($offset){
		return $this->getURL($offset);
	}
	
	function offsetSet($offset, $value){
		$o=explode('.',$offset);
	}
	
	function offsetUnset($offset){
		
	}
}
?>