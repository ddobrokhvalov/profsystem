<?PHP
	/**
	* Класс отображения журнала неуспешных авторизаций
	*
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin	 
	*
	*/
	
	class log_show_auth_failed extends log_show {
		
		/**
		* @var $fields Поля показываемые пользователю
		*/
		protected $fields = array (
			"LOG_OPERATION_ID" => array ("title"=>"lang_log_operation", "type"=>"select2", "fk_table"=>"LOG_OPERATION", "show"=>1, "filter"=>1, "is_main"=>1),
			"IP" => array ("title"=>"lang_IP", "type"=>"text", "show"=>1),
			"OPERATION_DATE" => array ("title"=>"lang_date", "show"=>1, "sort"=>"desc", "type"=>"datetime", "view_type"=>"full"),
			'LOGIN' => array ('title'=>'lang_login', 'show'=>1, 'subtype'=>'extended'),
			'ADMIN_ID' => array ('title'=>'lang_admin_id', 'show'=>1, 'subtype'=>'extended'),
			'ADMIN_NAME' => array ('title'=>'lang_administrator', 'show'=>1, 'subtype'=>'extended'),
			'BAD_PASSWORD_COUNT' =>  array ('title'=>'lang_bad_password_count', 'show'=>1, 'subtype'=>'extended'),
			'BAD_PASSWORD_MAX_COUNT' =>  array ('title'=>'lang_bad_password_max_count', 'show'=>1, 'subtype'=>'extended'),
		);
		
		/**
		* @var array $fields_absent_in_list Поля, которые не нужно показывать в списке
		*/
		
		protected $fields_absent_in_list = array (
			'ADMIN_ID',
			'BAD_PASSWORD_COUNT',
			'BAD_PASSWORD_MAX_COUNT'
			
		);
		
		/**
		* Готовит запись
		* @param array $rec Запись журнала
		* @return Сформированная запись
		*/

		protected function prepare_log_record ($rec) {
			$rec = parent::prepare_log_record($rec);
			
			$add_fields = log::get_complex_field($rec['LOG_INFO']);

			$rec['LOGIN']=$add_fields['login'];
			if (is_array($add_fields['user'])) {
				$rec['ADMIN_ID']=$add_fields['user']['AUTH_USER_ID'];
				$rec['ADMIN_NAME']=$add_fields['user']['SURNAME'];
				$rec['BAD_PASSWORD_COUNT'] = $add_fields['user']['BAD_PASSWORD_COUNT'];
				$rec['BAD_PASSWORD_MAX_COUNT'] = $add_fields['BAD_PASSWORD_MAX_COUNT'];
			}
			
			return $rec;
		}
		
	}
?>