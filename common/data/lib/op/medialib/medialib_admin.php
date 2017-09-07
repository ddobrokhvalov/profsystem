<?php
/**
 * Класс для администрирования галлерей
 * @author atukmanov
 *
 */
class medialib_admin{
	
	var $once=true;
	
	var $areas=array();
	var $mainArea=null;
	
	static $browsers=array();
	
	static $tplPath=null;
	
	function __construct($allowedTypes=0, $isMultiload=true, $required=false){		
		if ($allowedTypes){
			$this->mainArea= new medialib_admin_area($allowedTypes, $isMultiload, $required);
		}	
	}
	/**
	 * Добавить область
	 * @return 
	 */
	function addArea($areaID, $areaTitle, $area){
		if (isset($this->areas[$areaID])) throw new Exception('area with name: "'.$areaID.'" just exists');
		if (!preg_match('/^[a-z|\d]{1,50}$/',$areaID)) throw new Exception('invalid area name: "'.$areaID.'"');
		$this->areas[$areaID]=array($areaTitle, $area);
	}
	var $id='';
	
	var $name='medialib';
	
	function printForm(){
		
		include medialib::$conf->tplPath.'header.php';
		$options=array();
		foreach ($this->areas as $areaID=>$areaInfo){
			list ($areaTitle, $area)=$areaInfo;
			/*@var $area medialib_admin_area*/						
			$area->printForm($areaID, $areaTitle, (isset($this->items[$areaID]))?$this->items[$areaID]:null, $this->name);			
		}
		
		if ($this->mainArea){
			$this->mainArea->printForm(medialib::mainArea,'Прикрепить',(isset($this->items[medialib::mainArea]))?$this->items[medialib::mainArea]:null, $this->name);							
		}		
			
		include medialib::$conf->tplPath.'footer.php';
	}
	
	
	function __toString(){
		
		ob_start();
		$this->printForm();
		return ob_get_clean();
	}
	/**
	 * Проверить запрос:
	 * @return boolean
	 */
	function validateRequest(){
		$ret=true;	
		
		$request= new DataStore($_REQUEST);
		foreach ($this->areas as $areaID=>$areaInfo){
			list ($areaTitle, $area)=$areaInfo;
			/*@var $area medialib_admin_area*/
			if (!$area->validateRequest($request,$areaID, $this->name)){			
				$ret=false;
			}
		}
		if ($this->mainArea) $ret&=$this->mainArea->validateRequest($request,medialib::mainArea,$this->name);
		return $ret;
	}
	/**
	 * Принять изменения:
	 * @param medialib $medialib
	 * @return medialib
	 */
	function commitData($medialib){
		//Создаем объект сравнения ():
		$diff= new medialib_diff(null, $medialib);
		foreach ($this->areas as $areaID=>$areaInfo){
			list ($areaTitle, $area)=$areaInfo;
			/*@var $area medialib_admin_area*/
			foreach ($area->getItems() as $i){							
				$diff->addSrcItem($i);
			}
		}
		if ($this->mainArea){			
			foreach ($this->mainArea->getItems() as $i){							
				$diff->addSrcItem($i);
			}
		}		
		$diff->execute();
		/**
		 * Принимаем изменения в облаке:
		 */		
		foreach ($this->areas as $areaID=>$areaInfo){
			list ($areaTitle, $area)=$areaInfo;
			if ($area->tagsDict&&isset($diff->tagsCloud[$area])){
				$area->tagsDict->updateCloud($diff->tagsCloud[$area]);
			}
		}		
		if ($this->mainArea&&$this->mainArea->tagsDict&&isset($diff->tagsCloud[medialib::mainArea])){
			$this->mainArea->tagsDict->updateCloud($diff->tagsCloud[medialib::mainArea]);
		}
		return $medialib;
	}
	/**
	 * Принимаем версию:
	 * 
	 * Поясним вкратце весь этот гемморрой: нам надо сохранить старые id, чтобы сохранились ссылки
	 * 
	 * 1. Мы сохраняем id item'а в том случае если остается == file и area
	 * 2. Новые- записывает
	 * 3. Старые- трем
	 * 
	 * @param $test			принимаемая
	 * @param $published	опубликованная
	 * @return medialib		опубликованная
	 */
	function commitVersion($src, $dst){
		$diff= medialib_diff::fromVersions($src, $dst);
		$diff->execute();		
	}

	var $items=array();
	/**
	 * Загрузить данные из библиотеки
	 * @param medialib $medialib
	 * @return void
	 */
	function loadData($medialib){
		
		if ($list= $medialib->getList()){
			
			$itemID=array();
			
			foreach ($medialib->getList() as $item){
				/*@var $item medialib_item*/
				if (!isset($this->items[$item->area])) $this->items[$item->area]=array();
				$this->items[$item->area][]=$item;
				
				$itemID[]=$item->id;
			}	
						
		}
		if (count($itemID)){
			$sel= medialib_select::tags();
			$sel->Where('item',eq,$itemID);
			foreach ($sel as $tag){
				medialib_item::$tagsCache[$tag['item']][$tag['tag']]=$tag['position'];
			}
		}
	}
	
	static function justUploaded(){
		return null;	
	}
		
}
?>