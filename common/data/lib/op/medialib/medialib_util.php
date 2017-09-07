<?
/**
 * Утилиты для работы с библиотекой:
 *
 */
class medialib_util {
	/**
	 * Прикрепить библиотеки:
	 * @param dbselect $sel
	 * @param string   $type
	 * @param dbselect $libs
	 * 
	 * @return 
	 */
	static function joinLibs($sel, $type=null, $libs=null){
		if (!$libs) $libs=medialib_select::libs();
		if (!$type) $type=$sel->table;
		$sel->Join($libs, '{$1.'.$sel->primary_key.'}={$2.content_id} AND {$2.type}=\''.dbselect::Escape($type).'\' AND {$2.version}={$1.VERSION} AND {$2.lang}={$1.LANG_ID}',null,'medialib','LEFT');
		
		return $sel;
	}
	/**
	 * Утилита для вывода списка объектов:
	 * 
	 * Имеет смысл когда к объекту прикрепляется файл или картинка.
	 * т.е. у нас есть список объектов (dbSelect) у которого есть изображение (или что-то там)
	 * 
	 * объект -> библиотека -> объект в области
	 * 
	 * + нативный dbSelect интервейс
	 * 
	 * @param dbselect $sel 	выборка
	 * @param string   $type	тип, по которому объект связывается с библиотекой
	 * @param string   $area	область
	 * @return dbselect
	 */
	static function joinAreaItem($sel, $type=null, $area=medialib::mainArea){
		$lib= medialib_select::libs();
		$items=medialib_select::items();
		$files=medialib_select::files();
		$items->Join($files,'file','id','file','LEFT');
		$lib->Join($items,'{$1.id}={$2.lib} AND {$2.area}=\''.dbselect::Escape($area).'\' AND {$2.order}=1', null, 'item','LEFT');
		return self::joinLibs($sel, $type, $lib);
		return $sel;
	}
	/**
	 * Разбить select полученный medialib_util::joinAreaItem в список
	 * 
	 * @param dbselect 	$sel
	 * @param string	$objectKey	ключ по которому будет размещен конечный объект
	 * @param string	$selectKey	ключ по которому прикреплена таблица
	 * @param string	$className	класс
	 * 
	 * @return array	список объектов
	 */
	static function fetchSelect($sel, $objectKey='image', $selectKey='medialib', $className=null){
		$ret=array();
		foreach ($sel as $obj){
			if (isset($obj[$selectKey])&&isset($obj[$selectKey]['item'])&&$obj[$selectKey]['item']['id']){
				$item= medialib_item::instance($obj[$selectKey]['item']);
				unset($obj[$selectKey]);
				$obj[$objectKey]=$item;
				if ($className) $ret[]=DataStore::factory($obj, $className);
				else $ret[]=$obj;
			}
		}
		return $ret;
	}
	/**
	 * Прикрепить объект к элементам массива.
	 * 
	 * Имеет смысл, когда у нас есть список объектов 
	 * (т.е. мы выбрали что-то из кэша или не хотим пользоваться  
	 */
	static function joinItemToArray($arr, $type, $env, $area=medialib::mainArea){
		$items=self::getMappedItemsList($arr, $type, $env, 1, $area);
		foreach ($arr as &$obj){
			if (isset($items[$obj[$primaryKey]]))
			$obj['medialib_item']=$items[$obj[$primaryKey]][0];
		}
		return $arr;
	}
	/**
	 * Тоже самое только $count объектов
	 * 
	 * @param $arr
	 * @param $primaryKey
	 * @param $env
	 * @param $count
	 * @param $area
	 * @return unknown_type
	 */
	static function joinItemsToArray($arr, $type, $env, $count=1, $area=medialib::mainArea){
		$items=self::getMappedItemsList($arr, $type, $env, $count, $area);
		
		foreach ($arr as &$obj){
			if (isset($items[$obj[$type.'_ID']]))
			$obj['medialib_items']=$items[$obj[$type.'_ID']];
		}
		return $arr;
	} 
	/**
	 * Получить $count первых medialib_item прикрепленных и элементу массиву $arr в окружении $env
	 * 
	 * 
	 * @param $arr			array	список объектов
	 * @param $primaryKey	string	ключ определяющий id объекта
	 * @param $env			array	параметры окружения (lang, version, type)
	 * @param $area
	 * @param $count
	 * @return unknown_type
	 */
	static function getMappedItemsList($arr, $type, $env, $count=1, $area=medialib::mainArea){
		$id=array();
		foreach ($arr as $obj){			
			$id[]=(is_numeric($obj))?$obj:$obj[$type.'_ID'];
		}
		if (!count($id)) return array();
		$libs= medialib_select::libs();
		if (rbcc5_metadata::hasDecorator($type,'version')) $libs->Where('version',eq,$env['version']);
		if (rbcc5_metadata::hasDecorator($type,'lang')) $libs->Where('lang',eq,$env['lang']);		
		$libs->Where('type',eq,$type);
		$libs->Where('content_id',eq,$id);
		$items= medialib_select::items();
		$items->Join(medialib_select::files(),'file','id','file','LEFT');
		if ($count>1){
			$items->Where('order',smaller_or_eq,$count);
			$items->Where('area',eq,$area);
			$items->OrderBy('order','ASC');			
			$libs->Join($items,'id','lib','item','LEFT');			
		}
		else {
			$libs->Join($items,'{$1.id}={$2.lib} AND {$2.area}=\''.dbselect::Escape($area).'\' AND {$2.order}=1', null, 'item', 'LEFT');			
		}
		$ret=array();
		foreach ($libs as $obj){			
			$ret[$obj['content_id']][]=medialib_item::instance($obj['item'],null);			
		}
		
		return $ret;
	}
	/**
	 * Получить записи по тегу:
	 * @param $tag
	 * @param $env
	 * @return medialib_list
	 */
	static function getItemsByTag($tag, $env=array()){
		
		$sel= medialib_select::items();
		$sel->OrderBy('id','DESC');
		$sel->Join(medialib_select::files(),'file','id','file','LEFT');
		//Используем теги:
		$sel->Join(medialib_select::tags('tag',$tag),'id','item','tags','INNER');
		//Привязываем библиотеки:
		$lib= medialib_select::libs();
		foreach ($env as $k=>$v) $lib->Where($k,eq,$v);
		$sel->Join($lib, 'lib','id','lib','INNER');
		
		$ret=new medialib_list();
		$ret->sel=$sel;		
		return $ret;
	}
}
?>