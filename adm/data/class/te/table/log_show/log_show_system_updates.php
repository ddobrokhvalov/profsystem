<?PHP
/**
* Класс отображения журнала системы обновлений
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin	 
*
*/
	
class log_show_system_updates extends log_show {
	
	/**
	* @var $fields Поля показываемые пользователю
	*/

	protected $fields = array (
		"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
		"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1, 'filter'=>1),
		"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
		"OPERATION_DATE" => array ("type"=>"datetime", "title"=>"lang_date", "show"=>1, "sort"=>"desc", "view_type"=>"full"),
		"SYSTEM_UPDATE_ID" => array ("title"=>"lang_log_system_update_id", "show"=>1, "type"=>"int",	"subtype"=>"extended"),
		"SYSTEM_UPDATE_DATE" => array ("title"=>"lang_system_update_date", "show"=>1, "type"=>"date",	"subtype"=>"extended"),
		"SYSTEM_UPDATE_NAME" => array ("title"=>"lang_name", "show"=>1, "type"=>"text",	"subtype"=>"extended"),
		"ADDITIONAL_INFO" => array ("title"=>"lang_log_info", "show"=>1,	"subtype"=>"extended"),
		"START_MODE" => array ("title"=>"lang_log_system_update_start_mode", "show"=>1,	"subtype"=>"extended"),
		"SYSTEM_UPDATE_LOG" => array ("title"=>"lang_log_system_update_log", "show"=>1,	"subtype"=>"extended"),
	);
	
	/**
	* @var array $fields_absent_in_list Поля, которые не нужно показывать в списке
	*/
	
	protected $fields_absent_in_list = array (
		"IP",
		"SYSTEM_UPDATE_ID",
		"ADDITIONAL_INFO",
		"SYSTEM_UPDATE_LOG"
	);
	
	/**
	* Готовит запись
	* @param array $rec Запись журнала
	* @return Сформированная запись
	*/
	
	protected function prepare_log_record ($rec) {
		$rec = parent::prepare_log_record($rec);
		
		$add_fields = log::get_complex_field($rec['LOG_INFO']);
		$ext_info=db::sql_select('SELECT EXTENDED_INFO FROM LOG_EXTENDED_INFO WHERE LOG_RECORD_ID=:log_record_id', array('log_record_id'=>$rec['LOG_RECORD_ID']));
		
		$rec['SYSTEM_UPDATE_ID']=$add_fields['system_update_id'];
		$rec['SYSTEM_UPDATE_DATE']=$add_fields['system_update_date'];
		$rec['SYSTEM_UPDATE_NAME']=htmlspecialchars($add_fields['system_update_name']);
		$rec['ADDITIONAL_INFO']=$add_fields['additional_info'];
		$rec['SYSTEM_UPDATE_LOG'] = $ext_info[0]['EXTENDED_INFO'];

		$rec['START_MODE']=$add_fields['start_mode'];
		
		return $rec;
	}
}
?>