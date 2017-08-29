<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/te/table/log_show/log_show_records_change.php");

/**
* Класс отображения журнала изменения прав
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin	 
*
*/

class log_show_permissions extends log_show {
	
	/**
	* @var $fields Поля показываемые пользователю
	*/

	protected $fields = array (
		"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
		"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1, 'filter'=>1),
		"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
		"OPERATION_DATE" => array ("title"=>"lang_date", "show"=>1, "sort"=>"desc", "type"=>"datetime", "view_type"=>"full"),
		"OBJECT_ID" => array ("title"=>"lang_changed_role_id", "show"=>1),
		"ROLE_NAME" => array ("title"=>"lang_role_name", "show"=>1, "subtype"=>"extended"),
		"CHANGED" => array ("title"=>"lang_changed", "show"=>1, "subtype"=>"extended"),
	);
	
	/**
	* @var array $fields_absent_in_list Поля, которые не нужно показывать в списке
	*/
	
	protected $fields_absent_in_list = array (
		"IP",
		"OBJECT_ID",
		"CHANGED"
	);
	
	/**
	* Готовит запись
	* @param array $rec Запись журнала
	* @return Сформированная запись
	*/

	protected function prepare_log_record ($rec) {
		$rec = parent::prepare_log_record($rec);
		
		$add_fields = log::get_complex_field($rec['LOG_INFO']);
		$rec['ROLE_NAME']=htmlspecialchars($add_fields['role_name']);
			
		$rec['CHANGED']=log_show_records_change::get_m2m_for_print($rec['LOG_RECORD_ID']);
		
		return $rec;
	}
}	
?>