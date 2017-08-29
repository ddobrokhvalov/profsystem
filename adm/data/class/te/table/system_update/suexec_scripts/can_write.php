<?PHP
	/**
	* Скрипт, выделенный для возможности запуска через suExec
	* Проверяет, доступен ли файл для записи
	* Возвращает true или false
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/
	include_once(dirname(__FILE__)."/common.php");
	
	$path = $_GET['path'];
	
	if (!$_GET['path']) 
		die($_GET['path'].':'.metadata::$lang['lang_system_update_bad_path']);
	
	while ($_GET['path']!='.') {
		
		if (file_exists($_GET['path'])) {
			if (is_writeable($_GET['path']))
				die ('true');
			else 
				die ($_GET['path'].':'.metadata::$lang['lang_system_update_path_not_writeable']);
		}
		$_GET['path']=dirname($_GET['path']);
	}
	
	echo $path.':'.metadata::$lang['lang_system_update_bad_path'];
?>