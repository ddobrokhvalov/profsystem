<?PHP
	/**
	* Класс измененимя метаданных через web-интерфейс. Работа с таблицами
	* 
	* @package		RBC_Contents_5_0
 	* @subpackage te
 	* @copyright	Copyright (c) 2008 RBC SOFT
	* @author Alexandr Vladykin <avladykin@rbc.ru>
	*/
	
	class metadata_change_tables extends metadata_change_datatable {
		
		/**
		* Метод по умолчанию, выводит список таблиц
		* @see metadata_change_datatable::action_index
		*/
		
		public function action_index () {
			$this->title = metadata::$objects[$this -> obj]['title'];
			parent::action_index();
		}
		
		/**
		* Возвращает основные операции для таблицы
		* @see metadata_change_datatable::get_table_operations
		* @return array
		*/
		
		protected function get_table_operations () {
			$index_oprations = array ();
			/*if ($this->get_user_changes()) {
				// кнопки выводим только если были какие-то изменения
				$index_operations['commit_changes'] = array( 'name' => 'commit_changes', 'alt' => metadata::$lang['lang_metadata_changer_commit_changes'], 'url' => $this->url->get_url( 'commit_changes' ) );
				$index_operations['undo_changes'] = array( 'name' => 'undo_changes', 'alt' => metadata::$lang['lang_undo'], 'url' => $this->url->get_url( 'undo_changes' ) );
			}*/
			return $index_operations;
		}
		
		/**
		* Возвращает массив заглавной строки таблицы для подстановки в html_element::html_table
		* @return array
		*/
		
		protected function get_table_header () {
			return array (
				'_number' => array (
					"title" => "N",
				),
				'_table_name' => array (
					"title" => metadata::$lang['lang_table'],
					"is_main" => 1,
				),
				'_system_name' => array (
					"title" => metadata::$lang['lang_system_name'],
					"is_main" => 1,
				),
				'_ops' => array (
					"title" => metadata::$lang["lang_operations"], 
					"type" => "_ops"
				),
			);
		}
		
		/**
		* Возвращает строки таблицы в html-виде для подстановки в html_element::html_table
		* @return array
		*/
		
		protected function get_table_records () {
			$ret = array();
			$i=0;
			foreach ($this->app_tables as $system_name=>$object) {
				if ($object['type']=='table') {
					$ret[$i++] = array (
						'_number' => $i,
						'_table_name' => $object['title'],
						'_system_name' => $system_name,
						'_ops' => $this->get_record_operations(array('table_system_name'=>$system_name)) 
					);
				}
			}
			
			return $ret;
		}
		
		/**
		* Возвращает данные операций для строки таблицы
		* @param array $pk Первичный ключ записи (table_system_name)
		* @return string
		*/
		
		protected function get_record_operations ($pk) {
			return table::format_index_op(
								array(
									"name"=>"change", 
									"alt"=>metadata::$lang["lang_change"], 
									"url"=>$this->url->get_url(
														"", 
														array(
															"pk"=>$pk, 
															"add_params"=>array('level'=>'fields'),
															"save_params" => 1,
														)
											)
								)
			);
		}
	}
?>