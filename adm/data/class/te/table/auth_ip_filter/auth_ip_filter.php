<?PHP
	/**
	* Класс для управления фильтрацией по IP пользователей RBC Contents
	*
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/
	
	
	class auth_ip_filter extends table_translate {
		
		/**
	 	* Возвращает подготовленные к помещению в БД данные
	 	* 
	 	* Производит проверку данных. В случае неуспеха вызвает исключение
	 	* 
	 	* @param array $raw_fields		Сырые данные, например, $_REQUEST
	 	* @param string $prefix		Префикс, которым дополнены сырые данные, например, _form_ для формы
	 	* @param string $mode			Режим работы метода - "add" или "change"
	 	* @return array
	 	*/
		
		public function get_prepared_fields($raw_fields, $prefix, $mode){
			$fields = $this -> call_parent( 'get_prepared_fields', array($raw_fields, $prefix, $mode) );
			if (!$fields['START_IP'])
				$fields['START_IP'] = 0;
			
			if (!$fields['FINISH_IP'])
				$fields['FINISH_IP'] = 4294967295; // макс. IP
				
			// поля пришли уже обработанными, можно просто сравнить
			if ($fields['START_IP']>$fields['FINISH_IP']) 
				throw new Exception(metadata::$lang["lang_auth_ip_filter_start_ip_must_be_least"]);
			return $fields;
		}
	}
?>