<?php
/**
 * Специальное изключение, для динамического типа (т.е. конвертим видео)
 * @return unknown_type
 */
class medialib_typeException extends Exception{
	var $sender=null;
	var $meta=null;
	var $type=filecommon::file;
	
	function __construct($sender, $meta=null, $type=filecommon::file){
		parent::__construct('Type temporary unused');
		$this->sender=$sender;
		$this->meta=$meta;
		$this->type=$type;
	}
	/**
	 * Запустить конвертацию
	 * @param $file
	 * @return boolean
	 */
	function start($file){
		$this->sender->startConvertation($file);
	}
}
?>