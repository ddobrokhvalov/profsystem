<?php
class filecommon_select{
	/**
	 * Интерфейс к ф.с. a'la dbselect
	 *
	 * @param string $dir
	 */
	function __construct($dir=''){
		
	}
	
	static function load(){
		return new filecommon_select(); 
	}
	
	function Select(){
		
	}
	
	var $Where=array();	
	/**
	 * Условие
	 *
	 * @param string $Field	
	 * @param string $Type
	 * @param string $Value
	 */
	function Where($Field, $Type, $Value){
		$this->Where[$Field]=$Value;
	}
	/**
	 * Выбрать объект:
	 *
	 * @return string
	 */
	function SelectObject(){
		return filecommon::unserialize(STORAGE_PATH.$this->Where['id']);
	}
	/**
	 * Вставить:
	 *
	 * @param array $Data
	 */
	function Insert($Data){		
		filecommon::serialize(STORAGE_PATH.$this->Where['id'], $Data);
	}
	/**
	 * 
	 * ToDo: read folder
	 * 
	var $Order=1;
	function OrderBy($Order){
		$this->Order=$Order;
	}
	
	var $dir=null;
	function SelectResult(){
		$this->dir=filecommon::ls(STORAGE_PATH.$this->Where['dir'],$this->Where['regexp'],$this->OrderBy);
	}
	
	function Next(){
		return next($this->dir)
	}
	*/
}
?>