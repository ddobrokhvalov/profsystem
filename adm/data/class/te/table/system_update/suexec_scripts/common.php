<?PHP
	/**
	* Вставляется во все скрипты, выделенные для возможности запуска через suExec для системы обновления
	* Иницирует необходимые параметры и действия
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/

	include_once(dirname(__FILE__)."/../../../../../../data/config/params.php");
	include_once(params::$params["adm_data_server"]["value"]."class/core/object/object.php");

	// Рабирается QUERY_STRING
	$param_num = preg_match_all("/([a-z0-9_]+?)=([^&=\$]*)/i", $_ENV['QUERY_STRING'], $matches);
	for ($i = 0; $i < $param_num; $i++)
		$_GET[$matches[1][$i]] = base64_decode(urldecode($matches[2][$i]));
	
	if (!$_GET['l'] || !$_GET['sk']) {
		echo metadata::$lang['lang_system_update_bad_suexec_params'];
		exit;
	}
	
	
	// создаем auth с необходимыми параметрами имени пользователя и секретного ключа
	auth::singleton($_GET['l'], $_GET['sk']);

	$object=object::factory("SYSTEM_UPDATE");	
	
	if(!$object->full_object->is_permitted_to("view")){
			echo metadata::$lang['lang_system_update_access_denied'];
			exit;
	}
?>