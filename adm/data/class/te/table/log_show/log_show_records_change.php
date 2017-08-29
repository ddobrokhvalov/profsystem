<?PHP

/**
* Класс отображения журнала операций над записями
*
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin	 
*
*/

class log_show_records_change extends log_show {
	
	/**
	* @var $fields Поля показываемые пользователю
	*/

	protected $fields = array (
		"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
		"AUTH_USER_ID" => array ("title"=>"lang_administrator", "type"=>"select2", "fk_table"=>"AUTH_USER", "show"=>1, 'filter'=>1),
		"TE_OBJECT_ID" => array ("title"=>"lang_object", "type"=>"select2", "show"=>1, "fk_table"=>"TE_OBJECT", "filter"=>1),
		"LANG_ID" => array ("title"=>"lang_lang", "type"=>"select2", "show"=>1, "fk_table"=>"LANG", 'filter'=>1),
		"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
		"OPERATION_DATE" => array ("title"=>"lang_date", "show"=>1, "sort"=>"desc", "type"=>"datetime", "view_type"=>"full"),
		"OBJECT_ID" => array ("title"=>"lang_object_id", "show"=>1),
		"OBJECT_NAME" => array ("title"=>"lang_object_name", "show"=>1, "subtype"=>"extended"),
		"VERSION" => array ("title"=>"lang_version", "type"=>"select1", "show"=>1, 'filter'=>1, 'value_list'=>array(array('title'=>"lang_test", 'value'=>1), array('title'=>"lang_work", 'value'=>0))),
		"CHANGED" => array ("title"=>"lang_additional", "show"=>1, "subtype"=>"extended"),
		"INF_BLOCK_ID" => array ("title"=>"lang_inf_block", "show"=>1, "subtype"=>"extended"),
		"PRG_MODULE_ID" => array ("title"=>"lang_module", "show"=>1, "subtype"=>"extended"),
		"SITE_ID" => array ("title"=>"lang_site", "show"=>1, "subtype"=>"extended"),
		"RESOLUTIONS" => array ("title"=>"lang_resolutions", "show"=>1, "subtype"=>"extended"),
		
		// для параметров системы
		"OLD_VALUE" => array ("title"=>"lang_old_value", "show"=>1, "subtype"=>"extended"),
		"NEW_VALUE" => array ("title"=>"lang_new_value", "show"=>1, "subtype"=>"extended"),
	);
	
	/**
	* @var array $fields_absent_in_list Поля, которые не нужно показывать в списке
	*/
	
	protected $fields_absent_in_list = array (
		'LANG_ID', 
		'IP', 
		'VERSION',
		'CHANGED',
		'INF_BLOCK_ID',
		'INF_BLOCK_TABLE',
		'PRG_MODULE_ID',
		'SITE_ID',
		'RESOLUTIONS',
		'OLD_VALUE',
		'NEW_VALUE'
	);
	
	
	/**
	* Готовит запись
	* @param array $rec Запись журнала
	* @return Сформированная запись
	*/
		
	protected function prepare_log_record ($rec) {
			$rec=parent::prepare_log_record($rec);
			
			if (self::$te_object_names[$rec['_TE_OBJECT_ID']]) {
				$rec['TE_OBJECT_ID']=metadata::$objects[self::$te_object_names[$rec['_TE_OBJECT_ID']]['SYSTEM_NAME']]['title'];
			}
			
			if (isset($rec['VERSION'])) {
				$rec['VERSION'] = $rec['VERSION'] ? metadata::$lang["lang_test"] : metadata::$lang["lang_work"];
			}
			
			$rec['CHANGED']=self::get_m2m_for_print($rec['LOG_RECORD_ID']);
			
			$add_fields = log::get_complex_field($rec['LOG_INFO']);
			
			$rec['OBJECT_NAME']=$add_fields['object_name'];
			
			$rec['INF_BLOCK_ID']=$add_fields['inf_block_title'];
			$rec['INF_BLOCK_TABLE']=$add_fields['inf_block_table'];
			$rec['PRG_MODULE_ID']=$add_fields['prg_module_title'];
			$rec['SITE_ID']=$add_fields['site_title'];
			$rec['RESOLUTIONS']=$add_fields['resolutions'];
			
			$rec['OLD_VALUE']=$add_fields['old_value'];
			$rec['NEW_VALUE']=$add_fields['new_value'];
			
			return $rec;
	}
	/**
	* Получение строки для печати с информацией об изменениях для данного $log_record_id в таблице многие ко многим
	* @param int $log_record_id Уникальный номер записи журнала
	*/
	
	public static function get_m2m_for_print($log_record_id) {
		$ret='';
		if (sizeof($ext_info=db::sql_select('SELECT EXTENDED_INFO FROM LOG_EXTENDED_INFO WHERE LOG_RECORD_ID=:log_record_id', array('log_record_id'=>$log_record_id)))) {
			$ext = log::get_complex_field($ext_info[0]['EXTENDED_INFO']);
			$add_fields = $ext['m2m_changed'];

			$add_tables=self::get_additional_table_instances($ext['m2m']);
			
			if (sizeof($add_fields)) {
				foreach ($add_fields as $tetr_id=>$data) {
					if ($add_tables['tertiary_instance'] && $tetr_id) {
						$ret.='"'.metadata::$objects[$add_tables['tertiary_instance']->obj]['title'].'": "'.
							$add_tables['tertiary_instance']->get_full_object()->get_record_title(array($add_tables['tertiary_instance']->autoinc_name=>$tetr_id)).
								'" ('.$tetr_id.'). ';//.'<BR>';
					}
					
					if (sizeof($data['inserted'])) {
						$ret.=metadata::$lang['lang_Tied'].' "'.metadata::$objects[$add_tables['secondary_instance']->obj]['title'].'": ';
						$ret.=self::m2m_element_for_print($data['inserted']).' ';//.'<BR>';
					}
					
					if (sizeof($data['deleted'])) {
						$ret.=metadata::$lang['lang_Untied'].' "'.metadata::$objects[$add_tables['secondary_instance']->obj]['title'].'": ';
						$ret.=self::m2m_element_for_print ($data['deleted']).' ';//.'<BR>';
					}
					$ret.="\n";
				}
			}
		 }
		 return $ret;			
	}
	
	/**
	* Возвращает данные, пригодные для вывода для элемента m2m
	* @param array $arr Массив данных
	* @return string
	*/
	private static function m2m_element_for_print($arr) {
		$el = array();
		for ($i=0, $n=sizeof($arr); $i<$n; $i++) {
			$ids=array_keys($arr[$i]);
			$el[]=$arr[$i][$ids[1]];
		}
		return implode(', ', $el);
	}
	
	/**
	* Получение объектов вторичной и третичной таблиц
	* @param $m2m Данные о m2m
	* return array Объекты вторичной и третичной таблиц
	*/
	private static function get_additional_table_instances($m2m='') {
		static $secondary_instance, $tertiary_instance;
		if (!$secondary_instance)
			if ($m2m && $m2m['secondary_table']) 
				$secondary_instance=object::factory($m2m["secondary_table"]);
		
		if (!$tertiary_instance)
			if ($m2m && $m2m['tertiary_table']) 
				$tertiary_instance=object::factory($m2m["tertiary_table"]);
		
		return array('secondary_instance'=>$secondary_instance, 'tertiary_instance'=>$tertiary_instance);
	}
}
?>