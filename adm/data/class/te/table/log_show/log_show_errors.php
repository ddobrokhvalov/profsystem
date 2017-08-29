<?PHP

/**
* Класс отображения журнала ошибок
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin	 
*
*/
class log_show_errors extends log_show {

	/**
	* @var $fields Поля показываемые пользователю
	*/
	
	protected $fields = array (
		"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
		"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1, 'filter'=>1),
		"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
		"OPERATION_DATE" => array ("title"=>"lang_date", "show"=>1, "sort"=>"desc", "type"=>"datetime", "view_type"=>"full"),
		"FILE" => array ("title"=>"lang_file", "show"=>1, "subtype"=>"extended"),
		"LINE" => array ("title"=>"lang_string", "show"=>1, "subtype"=>"extended"),
		"MSG" => array ("title"=>"lang_msg", "show"=>1, "subtype"=>"extended"),
		"DEBUG" => array ("title"=>"lang_debug_info", "show"=>1, "subtype"=>"extended"), 
		'TRACE' => array ("title"=>"lang_backtrace", "show"=>1, "subtype"=>"extended"),
		'ADDITIONAL' => array ("title" => "lang_additional", "show" => 1, "subtype" => "extended"),
	);
	
	/**
	* @var array $fields_absent_in_list Поля, которые не нужно показывать в списке
	*/
	
	protected $fields_absent_in_list = array (
		"IP",
		"FILE",
		"LINE",
		"AUTH_USER_ID",
		"DEBUG",
		"TRACE",
		"ADDITIONAL",
	);
	

	/**
	* Готовит запись
	* @param array $rec Запись журнала
	* @return Сформированная запись
	*/
	
	protected function prepare_log_record ($rec) {
		$rec = parent::prepare_log_record($rec);
		
		$add_fields = log::get_complex_field($rec['LOG_INFO']);
		$rec['FILE']=$add_fields['file'];
		$rec['LINE']=$add_fields['line'];
		$rec['MSG']=$add_fields['msg'];
		$rec['DEBUG']=$add_fields['debug'];
		$rec['TRACE']=$add_fields['trace'];
		
		//echo $rec['LOG_RECORD_ID'];
		$ext_info=db::sql_select('SELECT EXTENDED_INFO FROM LOG_EXTENDED_INFO WHERE LOG_RECORD_ID=:log_record_id', array('log_record_id'=>$rec['LOG_RECORD_ID']));
		$rec['ADDITIONAL']=$ext_info[0]['EXTENDED_INFO'];
		
		return $rec;
	}		
}
?>