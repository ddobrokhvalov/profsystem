<?PHP

/**
* Класс проверки данных таблицы TABLE_TRANSLATE
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/


class lang_constants_error_finder_tt_sources extends lang_constants_error_finder {
		
		/**
		* Заголовок отчета
		*/

		protected $caption = 'table translate';
		
		/**
		* Запускает процесс автотеста
		*/
	
		public function process () {
			$tt_data = $this->get_tt_data();
	
			$object_fields=$this->get_fields_with_decorator_translate();

			foreach ($object_fields as $te_object => $fields) {
				// объект контента
				if (is_object($object)) 
					$object -> __destruct();
				
				$object = object::factory($te_object);
				
				// данные контента
				$data = array_keys(lib::array_reindex(db::sql_select (
					'SELECT 
						'.$object -> autoinc_name.' AS CONTENT_ID
						
					 FROM
					 	'.$object -> obj), 'CONTENT_ID'));

				for ($i_data=0, $n_data=sizeof($data); $i_data < $n_data; $i_data++) {
					for ($i_fields=0, $n_fields=sizeof($fields); $i_fields < $n_fields; $i_fields++) {
						
						// проверка на наличие данных c CONTENT_ID в TT
						if (!$field_tt_data = $tt_data [$te_object] [ $data[$i_data] ] [ strtoupper($fields[$i_fields]) ]) {
							$this->struct_report ['no_data_in_tt'][] = metadata::$lang['lang_No_information_about_field']." '".htmlspecialchars($fields[$i_fields])."' ".metadata::$lang['lang_of_record'].' '.htmlspecialchars($this->get_record_title($object, $data[$i_data])).' '.metadata::$lang['lang_from_table'].' "'.$te_object.'"';
							continue;
						}

						// проверка на наличие данных с учетом языков
						$absent_languages = array();
						$absent_values = array();

						foreach (array_keys($this->db_langs) as $lang_id) {
							if (!$field_tt_data [ $lang_id ]) {
								$absent_languages[] = $lang_id;
								continue;
							}
							
							// проверка на наличие значения
							if (!$field_tt_data[ $lang_id ]['VALUE']) 
								$absent_values[]=$lang_id;
							
							// обработанные данные уничтожаем
							unset($field_tt_data [ $lang_id ]);
						}
						
						if (sizeof($absent_values) && ((sizeof($absent_values)<sizeof($this->db_length))||metadata::$objects[$te_object]['fields'][$fields[$i_fields]]['errors']&_nonempty_)) 
							foreach ($absent_values as $lang_id)
								$this->struct_report ['no_value_in_tt'][] =  metadata::$lang['lang_Empty_value_for_field']." '".htmlspecialchars($fields[$i_fields])."' ".metadata::$lang['lang_of_record'].' '.htmlspecialchars($this->get_record_title($object, $data[$i_data])).' '.metadata::$lang['lang_from_table'].' "'.$te_object.'" '.$this->get_languages_titles($lang_id);
					
						// языки, данных о которых нет
						if (sizeof ($absent_languages)) {
							$this->struct_report ['no_lang_in_tt'][] = metadata::$lang['lang_No_information_about_field']." '".htmlspecialchars($fields[$i_fields])."' ".metadata::$lang['lang_of_record'].' '.htmlspecialchars($this->get_record_title($object, $data[$i_data])).' '.metadata::$lang['lang_from_table'].' "'.$te_object.'" '.$this->get_languages_titles($absent_languages);
						}

						
						// если данные остались, то они лишние
						if (sizeof ($field_tt_data)) {
							foreach ($field_tt_data as $tt) 
								$this->struct_report ['odd_lang_in_tt'][] = array('descr'=>metadata::$lang['lang_Odd_information_about_field']." '".htmlspecialchars($fields[$i_fields])."' ".metadata::$lang['lang_of_record'].' '.htmlspecialchars($this->get_record_title($object, $data[$i_data])).' '.metadata::$lang['lang_from_table'].' "'.$te_object.'" '.$this->get_languages_titles($tt['LANG_ID']).'. '.metadata::$lang['lang_Value'].": '".htmlspecialchars($tt['VALUE'])."'", 'link_descr'=>metadata::$lang['lang_delete'], 'action' => 'fix_action',	'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'], 'fix_link'=>'TE_OBJECT_ID='.$tt['TE_OBJECT_ID'].'&LANG_ID='.$tt['LANG_ID'].'&CONTENT_ID='.$tt['CONTENT_ID'].'&FIELD_NAME='.$tt['FIELD_NAME']);
						}
						
					}
					unset ($tt_data[$te_object][ $data[$i_data] ]);
				}
			}
			
			if (is_object($object))
				$object -> __destruct();			
			
			// необработанные данные - лишние
			if (sizeof ($tt_data)) 
				foreach ($tt_data as $te_object => $content_fields) 
					if (sizeof ($content_fields)) 
						foreach ($content_fields as $content_id => $fields) 
							if (sizeof ($fields)) 
								foreach ($fields as $field_name => $langs) 
									if (sizeof ($langs)) 
										foreach ($langs as $lang_id => $tt) {
											$object = object::factory($te_object);
											$this->struct_report ['odd_data_in_tt'][] = array('descr' => metadata::$lang['lang_Odd_information_about_field']." '".htmlspecialchars($field_name)."' ".metadata::$lang['lang_of_record'].' '.htmlspecialchars($this->get_record_title($object, $content_id)).' '.metadata::$lang['lang_from_table'].' "'.$te_object.'" '.$this->get_languages_titles($tt['LANG_ID']).'. '.metadata::$lang['lang_Value'].": '".htmlspecialchars($tt['VALUE'])."'", 'link_descr' => metadata::$lang['lang_delete'], 'action' => 'fix_action',	'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'], 'fix_link'=>'TE_OBJECT_ID='.$tt['TE_OBJECT_ID'].'&LANG_ID='.$tt['LANG_ID'].'&CONTENT_ID='.$tt['CONTENT_ID'].'&FIELD_NAME='.$tt['FIELD_NAME']);
											$object -> __destruct();
										}
			
			
			return $this->create_report();
		}
		
		/**
		* Запускает процесс формирования репорта по результатам автотеста
		*/
		
		protected function create_report() {
			$this->simple_send_to_report($this->struct_report ['no_data_in_tt'], metadata::$lang['lang_autotest_test_lang_constants_no_data_in_tt']);
			$this->simple_send_to_report($this->struct_report ['no_lang_in_tt'], metadata::$lang['lang_autotest_test_lang_constants_no_lang_in_tt']);
			$this->simple_send_to_report($this->struct_report ['odd_lang_in_tt'], metadata::$lang['lang_autotest_test_lang_constants_odd_lang_in_tt']);
			$this->simple_send_to_report($this->struct_report ['odd_data_in_tt'], metadata::$lang['lang_autotest_test_lang_constants_odd_data_in_tt']);
			$this->simple_send_to_report($this->struct_report ['no_value_in_tt'], metadata::$lang['lang_autotest_test_lang_constants_no_value_in_tt']);
			if (sizeof($this->report))
				$this->report[0]['caption']="<h3 class='autotest'>".mb_strtoupper($this->caption, params::$params["encoding"]["value"]).'</h3> '.$this->report[0]['caption'];
			return $this->report;
		}
		
		/**
		* Автоисправление
		*/
		
		public function fix_action () {
			return $this->exec_delete($_REQUEST);
		}
		
		/**
		* Удаление из TT
		* @var array $raw_fields - массив, полученный из $_REQUEST
		* @return mixed
		*/
		
		public function exec_delete ($raw_fields) {
			if ($raw_fields['TE_OBJECT_ID'] && $raw_fields['LANG_ID'] && $raw_fields['CONTENT_ID'] && $raw_fields['FIELD_NAME']) {
				if (db::delete_record('TABLE_TRANSLATE', array_intersect_key($raw_fields, array_flip(array('TE_OBJECT_ID', 'LANG_ID', 'CONTENT_ID', 'FIELD_NAME'))))) {
					return metadata::$lang['lang_done'];
				}
			}
			return false;
		}


		/**
		* Формируем массив доступных языков из БД
		* Хотим получить по LANG_ID а не по ROOT_DIR для данного автотеста
		*/
		
		protected function set_db_langs() {
			if (!sizeof($this->db_langs))
				$this->db_langs=lib::array_reindex(db::sql_select('SELECT * FROM LANG WHERE IN_ADMIN=:in_admin ORDER BY PRIORITY DESC', array('in_admin'=>1)), 'LANG_ID');
		}


		/**
		* Получает данные о полях, которые необходимо проверить
		* @return array
		*/
		
		private function get_fields_with_decorator_translate () {
			$result = array();
			foreach (metadata::$objects as $te_object => $object)
				if (in_array('decorators', array_keys($object)) && in_array('translate', $object['decorators']))
					foreach ($object['fields'] as $field_name=>$field_props)
						if ($field_props['translate']) 
							$result[$te_object][]=$field_name;
			
			return $result;
		}
		
		
		/**
		* Получает все данные из таблицы TABLE_TRANSLATE
		* @return array
		*/
		
		private function get_tt_data () {
			return lib::array_reindex(
				db::sql_select(
					'SELECT 
						TOBJ.SYSTEM_NAME AS TE_OBJECT, 
						TT.TE_OBJECT_ID,
						TT.LANG_ID, 
						TT.CONTENT_ID, 
						UPPER(TT.FIELD_NAME) AS FIELD_NAME, 
						TT.VALUE 
					 FROM 
					 	TABLE_TRANSLATE TT 
					 		INNER JOIN 
					 	TE_OBJECT TOBJ 
					 		ON (TT.TE_OBJECT_ID = TOBJ.TE_OBJECT_ID)
				'), 
				'TE_OBJECT', 'CONTENT_ID', 'FIELD_NAME', 'LANG_ID'
			);			
		}
		
		/**
		* Получить заглавие записи по id объекта
		* @param object $obj Объект
		* @param int $content_id ID объекта
		* @return string
		*/
		
		private function get_record_title ($obj, $content_id) {
			$record_title = $obj->get_record_title(array($obj->autoinc_name=>$content_id));
			if ($record_title) {
				$record_title = "'".$record_title."'";
			}

			$record_title .= ' '.metadata::$lang['lang_with'].' CONTENT_ID='.$content_id;
			
			return $record_title;
		}
		
		/**
		* Получить названия языков
		* @param mixed $langs - ID языка или массив ID языков
		* @return string
		*/
		
		private function get_languages_titles ($langs) {
			$result_langs = array();
			$langs = (array)$langs;
			
			for ($i=0, $n=sizeof($langs); $i < $n; $i++) {
				if ($this->db_langs[$langs[$i]]['TITLE'])
					$result_langs[] = htmlspecialchars($this->db_langs[$langs[$i]]['TITLE']);
				else
					$result_langs[] = metadata::$lang['lang_with'].' LANG_ID='.$langs[$i].', '.metadata::$lang['lang_of_absent_in_the_system'];
			}
			
			if (sizeof($result_langs)>1) {
				return metadata::$lang['lang_for_languages'].': '.implode(', ', $result_langs);
			}
			elseif (sizeof($result_langs)==1) {
				return metadata::$lang['lang_for_language'].': '.htmlspecialchars($result_langs[0]);
			}
			
			return '';			
		}
}
?>