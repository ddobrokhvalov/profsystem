<?php
/**
 * Все тоже самое только возвращается одно поле
 */
class dbselect_scalar extends dbselect {
	function __construct($fields, $table, $serialized=null){
		parent::__construct($fields, $table, $serialized);
	}
	
	function AssocResult(&$row, &$Offset){
		$ret=parent::AssocResult($row, $Offset);
		if (count($this->Select)==1) return $ret[$this->Select[0]];
		else return $ret[$this->Select[1]];
	}
}
?>