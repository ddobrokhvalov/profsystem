<?PHP

	/**
	* Скрипт, выделенный для возможности запуска через suExec
	* Удаляет файл, если он последний в директории, то и директорию
	* Возвращает true или false
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/

	include_once(dirname(__FILE__)."/common.php");

	if (!$_GET['from'] ||
		!@unlink($_GET['from'])) {
			echo $_GET['from'].' '.metadata::$lang['lang_system_update_can_not_delete_file'];
			exit;
	}
	@rmdir(dirname($_GET['from']));
	echo 'true';
?>