<?php
class medialib_item extends DataStore{
	
	static $strTypes=array(
		medialib::file=>	'file',
		medialib::image=>	'image',
		medialib::video=>	'video',
		medialib::audio=>	'audio',
		medialib::flash=>	'flv',
	);
	
	var $fileError=null;
	/**
	 * Список
	 * @var medialib_list
	 */
	var $list;
	/**
	 * Библиотека
	 * @var medialib
	 */
	var $lib;
	/**
	 * Фабрика объекта
	 * @param array $info
	 * @param medialib_list $list
	 * @param medialib $lib
	 * 
	 * @return medialib_item
	 */
	static function instance($info, $list){
		if (!$info) return null;		
		
		if (isset(self::$strTypes[$info['file']['type']])){
			$className='medialib_item_'.self::$strTypes[$info['file']['type']];
		}
		else {
			$className='medialib_item';
		}
		$i=array();
		
		$ret= new $className($info);
		$ret->list=$list;
		
		return $ret;
	}
	
	/**
	 * Файл:
	 * 
	 * @var medialib_file
	 */
	var $file=null;
	/**
	 * Ошибка
	 * @var string
	 */
	var $error=null;
	/**
	 * Из файла:
	 * 
	 * @param $file
	 * @param $title
	 * @return unknown_type
	 */
	static function fromFile($file, $title, $order){
		$ret= new medialib_item();
		$ret->Info['title']=$title;
		$ret->Info['order']=$order;
		$ret->file=$file;
		return $ret;
	}
	
	function getFile(){
		
		if (!$this->file && $this->Info['file']){
			if (is_array($this->Info['file'])){
				$this->file= new medialib_file($this->Info['file']); 
			}
			elseif (is_numeric($this->Info['file'])){
				$this->file= medialib_file::loadByID($this->Info['file']);
			}
		}
		return $this->file;
	}
	
	private $_prevItem;
	function getPrevItem(){
		if (!$this->_prevItem) $this->_prevItem= $this->list->getPrevItem($this);
		return $this->_prevItem;
	}
	
	private $_nextItem;
	function getNextItem(){
		if (!$this->_nextItem) $this->_nextItem= $this->list->getNextItem($this);
		return $this->_nextItem;
	}
	
	private $_neibours;
	function getNeibours($count=6){
		if (!$this->_neibours){
			$this->_neibours=$this->list->getNeighbours($this, $count);
		}
		return $this->_neibours;
		
	}
	/**
	 * Урл для попапа:
	 * @return medialib_url
	 */
	function getPopupURL(){
		return new medialib_url(str_replace(
				array('{$itemID}','{$itemFileName}'),
				array($this->getID(),$this->getInfo('fileName')),
				$this->list->getItemPopupUrl()));
	}
	/**
	 * URL для превью (переопределяется в дочернем классе)
	 * @return medialib_url
	 */
	function getPreviewURL(){
		return '';		
	}
	/**
	 * (non-PHPdoc)
	 * @see lib/DataStore#_getInfo()
	 */
	function _getInfo($path){
		switch (str::lower($path[0])){
			case 'previtem':
				return $this->getPrevItem();
			break;
			case 'nextitem':
				return $this->getNextItem();
			break;
			case 'neighbours':
				return $this->getNeibours();
			break;
			case 'downloadurl':
				return $this->getSetting('downloadUrl','/medialib/downloads/').$this->Info['fileName'];
			break;
			case 'popupurl':			
				return $this->getPopupUrl();
			break;
			case 'preview':			
			case 'ext':
			case 'filesize':
				
				return $this->getFile()->getInfo($path);
			break;
			case 'tags':
				return $this->getTags();
			break;
		}		
		return parent::_getInfo($path);
	}
	
	function getSetting($path, $default){
		return $this->list->lib->getSetting($path, $default);
	}
	
	function setArea($area){
		$this->Info['area']=$area;
	}
	
	protected $_tags=null;
	/**
	 * Получить теги:
	 * @return unknown_type
	 */
	function getTags(){
		if ($this->_tags) return $this->_tags;
		$this->_tags=array();
		$sel=medialib_select::tags('item',$this->getID());		
		foreach ($sel as $obj){
			$this->_tags[$obj['tag']]=$obj['position'];
			
		}
		
		return $this->_tags;
	}
	
	var $tags=null;	
	static $tagsCache=array();
	
	function getTagsList(){		
		if ($this->tags==null) $this->tags=($this->id&&isset(self::$tagsCache[$this->id]))?self::$tagsCache[$this->id]:array();
		return $this->tags;
	}
}
?>