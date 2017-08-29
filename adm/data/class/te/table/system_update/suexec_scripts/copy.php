<?PHP

	/**
	* Скрипт, выделенный для возможности запуска через suExec
	* Копирует файл
	* Возвращает true или false
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/


	include_once(dirname(__FILE__)."/common.php");

	if (!$_GET['from'] || !$_GET['to']) {
		die (metadata::$lang['lang_system_update_bad_params']);
	}
	elseif (!is_dir(dirname($_GET['to'])) && !mkdir(dirname($_GET['to']), 0777, true)) {
		die (dirname($_GET['to']).': '.metadata::$lang['lang_system_update_can_not_access_or_create_dir']);
	}
	elseif (!copy($_GET['from'], $_GET['to'])) {
		die (metadata::$lang['lang_system_update_can_not_copy'].' '.$_GET['from'].' '.metadata::$lang['lang_to'].' '.$_GET['to']);
	}
	echo 'true';

?>