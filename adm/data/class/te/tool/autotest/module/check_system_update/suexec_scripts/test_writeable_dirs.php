<?PHP
	/**
	* Скрипт, выделенный для возможности запуска через suExec
	* Проверяет доступны ли на запись файлы, которые можно обновить системой обновления
	* Возвращает TRUE или список файлов через <HR>
	* @package RBC_Contents_5_0
	* @subpackage te
	* @copyright Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/

	include_once(dirname(__FILE__)."/../../../../../table/system_update/suexec_scripts/common.php");
	include_once(dirname(__FILE__)."/../../../../../table/system_update/system_update.php");
	
	
	$files=array();
	$report=array();
	
	$writeable_list = $object->writeable_dirs;
	$exclude_list = $object->exclude_writeable_list;
	// файлы, которые уже проверены
	$already_passed = array();
	
	// сделано так чтобы сначала обрабатывал cms как подмножество te
	foreach(array('cms'=>$writeable_list['cms'], 'te'=>$writeable_list['te']) as $level=>$dirs){
		foreach ($dirs as $writeable_item) {
			$files=filesystem::ls_r($writeable_item);
			// удаляем все шаблоны смарти
			$files=array_filter($files, "remove_smarty");
			
			foreach($files as $file){
				if ($file["name"] && !is_writeable($file["name"]) && !in_array($file['name'], $already_passed)) {
					$report[]="{$file[name]}: ".metadata::$lang['lang_system_update_bad_rights_on_writing'];
				}
				if ($level=='cms') $already_passed[]=$file['name'];
			}
		}
	}
	
	if (sizeof($report)) {
		echo implode('<BR>', $report);
	}
	else {
		echo 'TRUE';
	}
	
	/**
	* Ф-ия для фильтрации массива, удаляет из него все элементы, у которых в ключе name 
	* содержатся данные, которые совпадают с exclude_list. Подставляется в array_filter
	*/
	function remove_smarty ($value) {
		global $exclude_list;
		
		foreach ($exclude_list as $exclude) 
			if (preg_match($exclude, $value[name])) {
				return false;
			}
				
		return true;
	}
?>