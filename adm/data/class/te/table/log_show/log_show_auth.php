<?PHP
/**
* Класс отображения журнала авторизаций
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin	 
*
*/

	class log_show_auth extends log_show {
		
		/**
		* Поля показываемые пользователю
		*/
		
		protected $fields = array (
			"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
			"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1, 'filter'=>1),
			"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
			"OPERATION_DATE" => array ("title"=>"lang_date", "show"=>1, "sort"=>"desc", "type"=>"datetime", "view_type"=>"full"),
		);
		
	}
?>