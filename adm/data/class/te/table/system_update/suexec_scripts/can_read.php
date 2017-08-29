<?PHP
	/**
	* Скрипт, выделенный для возможности запуска через suExec
	* Проверяет, доступен ли файл для чтения
	* Возвращает true или false
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/
	
	include_once(dirname(__FILE__)."/common.php");	
	
	if (!$_GET['path'] || !file_exists($_GET['path']))
		echo $_GET['path'].':'.metadata::$lang['lang_system_update_bad_path'];
	elseif (!is_readable($_GET['path'])) 
		echo $_GET['path'].':'.metadata::$lang['lang_system_update_path_not_readable'];
	else
		echo 'true';
?>