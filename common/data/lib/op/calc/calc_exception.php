<?php
class calc_exception extends Exception{
	const undefinedVariable='lang_calc_undefined_variable';
	const invalidFormula='lang_calc_invalid_formula';
	
	static $lang=array();
	
	var $varID=null;
	
	function __construct($message, $varID){
		parent::__construct(self::formatMessage($message, $varID));
		$this->varID=$varID;	
	}
	
	static function formatMessage($message, $varID){
		if (isset(self::$lang[$message])){
			return str_replace(
				'[$title]',
				(isset(self::$lang['var_'.$varID]))?self::$lang['var_'.$varID]:$varID,
				self::$lang[$message]	
			);
		}
		else return $message;
	}
}
?>