<?PHP
	/**
	* Класс измененимя метаданных через web-интерфейс. Работа со значениями полей типа select1
	* 
	* @package		RBC_Contents_5_0
 	* @subpackage te
 	* @copyright	Copyright (c) 2008 RBC SOFT
	* @author Alexandr Vladykin <avladykin@rbc.ru>
	*/

	class metadata_change_option_values extends metadata_change_datatable {
		
		/**
		* Конструктор, устанавливаются в статусную строку необходимые значения
		*/
		
		public function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);
			
			// для того чтобы получить объект url
			$this -> apply_object_parameters( $_REQUEST );
			
			$this->title = metadata::$lang['lang_metadata_changer_change_value_list'].' "'.$this->app_tables[$_GET['table_system_name']]['fields'][$_GET['field_system_name']]['title'].'"';
			$this->path_id_array[]=array('TITLE'=>$this->title, 'URL'=>$this->url->get_url('index', array('restore_params'=>1)));
			$this->path_id_array[]=array('TITLE'=>$this->app_tables[$_GET['table_system_name']]['title'], "URL"=>$this->url->get_url('index', array('add_params'=>array('table_system_name'=>$_GET['table_system_name'], 'level'=>'fields'))));
		}

		
		/**
		* Метод по умолчанию, выводит список таблиц
		* @see metadata_change_datatable::action_index
		*/

		public function action_index () {
			$pk = $this->get_pk($_REQUEST);
			$this->check_field($pk);
			parent::action_index();
		}
		

		/**
		* Дополняем метод metadata_change::check_field проверкой на тип поля
		* @see metadata_change::check_field
		*/
		
		protected function check_field ($pk, $throw_exception=true) {
			if (!parent::check_field($pk, $throw_exception)) return false;
			
			if ($this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['type']!='select1') {
				if ($throw_exception) 
					throw new Exception(metadata::$lang['lang_metadata_changer_field_does_not_support_value_list'].': "'.$pk['field_system_name'].'" ("'.$pk['table_system_name'].'")');
				return false;
			}
			return true;
		}
		
		/**
		* Возвращает из массива данных первичный ключ записи
		* @param array $data Данные
		* @return array
		*/
		
		protected function get_pk ($data) {
			return array_intersect_key($data, array_flip(array('table_system_name', 'field_system_name', 'value', 'old_value')));
		}
		

		/**
		* Возвращает основные операции для таблицы
		* @see metadata_change_datatable::get_table_operations
		* @return string
		*/
		
		protected function get_table_operations () {
			$pk = $this->get_pk($_REQUEST);
			unset($pk['value'], $pk['old_value']);

			$operations = array();
			$operations['add'] = array( 
				'name' => 'add', 
				'alt' => metadata::$lang['lang_add'], 
				'url' => $this -> url -> get_url( 
					'add', 
					array(
						'pk'=>$pk, 
						'add_params'=>array('level'=>'option_values'),
						'save_params'=>1
					)
				) 
			);
			
			$operations['group_delete'] = array( 
				'name'=>'group_delete', 
				'alt' => metadata::$lang['lang_delete'],
				'url' => "javascript:if ( CheckFillConfirm( '" . 
											metadata::$lang['lang_confirm_mass_delete'] . 
								"' ) ) { document.forms['checkbox_form'].action.value = 'group_delete'; document.forms['checkbox_form'].submit() }" 
			);
			return $operations;
		}
		
		
		/**
		* Возвращает массив заглавной строки таблицы для подстановки в html_element::html_table
		* @return array
		*/
		
		protected function get_table_header() {
			return array (
				'_number' => array (
					"title" => "N",
				),
				'_group' => array(
					"title"=>"", 
					"type"=>"_group"
				),
				'_value_title' => array (
					"title" => metadata::$lang['lang_name'],
					"is_main" => 1,
				),
				'_value_value' => array (
					"title" => metadata::$lang['lang_Value'],
					"is_main" => 1,
				),
				'_selected' => array (
					"title" => metadata::$lang['lang_metadata_changer_value_is_default'],
					"type" => "checkbox"
				),
				'_order' => array (
					"title" => metadata::$lang['lang_order'],
					"type" => 'order'
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
		
		protected function get_table_records($pk) {
			$this->check_field($pk);
			$ret = array();
			$i=0;
			if (is_array($this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'])) {
				foreach ($this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'] as $value) {
					$ret[$i++] = array (
						'_number' => $i,
						'_group' => array('id'=>$pk['table_system_name'].'/'.$pk['field_system_name'].'/'.$value['value']),
						'_value_title' => $value['title'],
						'_value_value' => $value['value'],
						'_selected' => $value['selected'],
						'_order' => array('up'=>$this->url->get_url("order",array("pk"=>array_merge($pk, array("dir"=>"up", 'level'=>'option_values', 'value'=>$value['value'])))), 'down'=>$this->url->get_url("order",array("pk"=>array_merge($pk, array("dir"=>"down", 'level'=>'option_values','value'=>$value['value']))))),
						'_ops' => $this->get_record_operations(array_merge($pk, array('value'=>$value['value'])))
					);
				}
			}
			
			unset($ret[0]['_order']['up'],$ret[sizeof($ret)-1]['_order']['down']);
			return $ret;
		}
		
		/**
		* Возвращает данные операций для строки таблицы
		* @param array $pk Первичный ключ записи (table_system_name)
		* @return string
		*/
		
		protected function get_record_operations ($pk) {
			$ops = array (
				table::format_index_op(
					array(
						"name" => "change", 
						"alt" => metadata::$lang["lang_change"], 
						"url" => $this->url->get_url(
							"change", 
							array(
								"pk"=>$pk, 
								'add_params'=>array('level'=>'option_values'),
								"save_params" => 1
							)
						),
					)
				),
				table::format_index_op(
					array(
						"name" => "delete", 
						"alt" => metadata::$lang["lang_delete"], 
						"url" => $this->url->get_url(
							"delete", 
								array(
									"pk"=>$pk, 
									'add_params'=>array('level'=>'option_values'),
								)
						),
						"confirm" => true
					)
				)
			);
			return join(', ', $ops);
		}
		
//---------------------------------------------------------------------------------------------------------------------------------------------------
		
		/**
		* HTML-карточка записи
		* @param array $pk Первичный ключ
		* @return array название-содержимое
		* @see metadata_change_datatable::html_card
		*/

		protected function html_card ($pk) {
			$form_name = html_element::get_next_form_name();
			$operations = array();
			
			$pk_for_hidden = array('pk'=>$pk, 'add_params'=>array('level'=>'option_values'));
			
			if (!$pk['value']) {
				$title=metadata::$lang["lang_metadata_changer_value_list_add"];
				$operations =$this -> get_operations( array('save', 'cancel'),  $form_name);
				$action="added";
			}
			else {
				$this->check_field($pk);
				$title=metadata::$lang["lang_metadata_changer_value_list_change"];
				$pk_for_hidden['pk']['old_value'] = $pk_for_hidden['pk']['value'];
				unset($pk_for_hidden['pk']['value']);
				$operations =$this -> get_operations( array('apply', 'save', 'cancel'),  $form_name);
				$action="changed";
			}
			
			$html_fields = html_element::html_fields( $this -> get_fields_for_form( $pk ) , $this -> tpl_dir . 'core/html_element/html_fields.tpl', $this->field);
			$form = html_element::html_form( $html_fields, $this -> url -> get_hidden( $action, $pk_for_hidden ), $this -> tpl_dir . 'core/html_element/html_form.tpl', true);
			
			$tpl = new smarty_ee( metadata::$lang );
			
			$tpl -> assign( 'title', $title );
			$tpl -> assign( 'form_name', $form_name );
			$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'form', $form ); 
			
			// вставляем данные в статус
			array_unshift($this->path_id_array, array('TITLE'=>$title));
			
			return array( $title, $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ) );
		}

		/**
		* Возвращает поля для формы редактирования
		* @param array $pk Первичный ключ
		* @return array
		* @see metadata_change_datatable::get_fields_for_form
		*/
		
		protected function get_fields_for_form($pk) {
			if ($pk['old_value'])
				$pk['value']=$pk['old_value'];
			$fields = array (
				'title' => array ('title'=>metadata::$lang['lang_name'], 'type'=>'text'),
				'value' => array ('title'=>metadata::$lang['lang_Value'], 'type'=>'text'),
				'selected' => array ('title'=>metadata::$lang['lang_metadata_changer_value_is_default'], 'type'=>'checkbox')
			);
			$values=lib::array_reindex($this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'], 'value');
			return $this->get_form_fields($pk['value']?'change':'add', '_form_', $values[$pk['value']], "", $fields);
		}

//---------------------------------------------------------------------------------------------------------------------------------------------------

		/**
		* Метод, сохраняющий запись
		* @see metadata_change_datatable::exec_add
		*/
		
		public function exec_add($raw_fields, $prefix, $pk) {
			$this->check_field($pk);
			
			$fields=$this->get_prepared_fields($raw_fields, $prefix, $pk);
			
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'];
			$save_fields = $sys_fields;
			
			$form_fields = array_keys($this->get_fields_for_form ($pk));
			
			$new_data=array();
			foreach ($form_fields as $field_name) 
				if ($fields[$field_name])
					$new_data[$field_name]=$fields[$field_name];
					
			$sys_fields[]=$new_data;
			
			try {
				$this->check_user_data();
				parent::exec_add($raw_fields, $prefix, $pk);
			}
			catch (Exception $e) {
				$sys_fields=$save_fields;
				throw $e;
			}
		}
		
		/**
		* Возвращает подготовленые к вставке в БД значения полей
		* @see metadata_change_datatable::get_prepared_fields
		*/

		protected function get_prepared_fields($raw_fields, $prefix, $pk) {
			$fields = array();
			foreach($this->get_fields_for_form ($pk) as $field_name=>$field)
				$fields[$field_name]=$this->field->get_prepared($raw_fields[$prefix.$field_name], $field, $prefix.$field_name);
			return $fields;
		}

		/** 
		* Метод, вызываемый при сохранении записи, при этом с возвратом на страницу редактирования
		* @see metadata_change_datatable::action_changed_apply
		*/
		
		public function action_changed_apply() {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_change($_REQUEST, "_form_", $pk);

			$pk['value']=$_REQUEST['_form_value'];
			unset($pk['old_value']);
			$this->url->redirect("change", array("pk"=>$pk, 'add_params'=>array('level'=>'option_values')));
		}
		
		/**
		* Метод, изменяющий запись
		* @see metadata_change_datatable::exec_change
		*/

		public function exec_change ($raw_fields, $prefix, $pk) {
			$this->check_field($pk);
			$fields=$this->get_prepared_fields($raw_fields, $prefix, $pk);
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'];
			$save_fields = $sys_fields;
			$form_fields = array_keys($this->get_fields_for_form ($pk));
			
			$new_data=array();
			foreach ($form_fields as $field_name) 
				if ($fields[$field_name])
					$new_data[$field_name]=$fields[$field_name];
					
			foreach ($sys_fields as $num=>$value) {
				if ($value['value']==$pk['old_value']) {
					$old_data = $sys_fields[$num];
					unset($old_data['_lang_constant_title']);
					if ($old_data!=$new_data) {
						$sys_fields[$num]=$new_data;
						unset($sys_fields['_lang_constant_title']);
						break;
					}
				}
			}
			
			try {
				$this->check_user_data();
				parent::exec_change ($raw_fields, $prefix, $pk);
			}
			catch (Exception $e) {
				$sys_fields=$save_fields;
				throw $e;
			}
			
		}
		
		/**
		* Метод, вызываемый при удалении записи
		* @see metadata_change_datatable::action_delete
		*/

		public function action_delete() {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_delete($pk);
			$this->url->redirect("", array('pk'=>$pk, 'add_params'=>array('level'=>'option_values')));
		}
		
		/**
		* Метод, вызываемый при удалении группы записи
		* @see table::action_group_delete
		*/

		public function action_group_delete() {
			$group_pks=$this->primary_key->get_group_from_request();
			foreach($group_pks as $pk) {
				if ($pk['pk']['METADATA_CHANGE_ID']) {
					$pk_path = explode('/', $pk['pk']['METADATA_CHANGE_ID']);
					$this->exec_delete(
						array(
							'table_system_name'=>$pk_path[0], 
							'field_system_name'=>$pk_path[1], 
							'value'=>$pk_path[2]
						)
					);
				}
			}
			$this->url->redirect("", array('pk'=>$this->get_pk($_REQUEST), 'add_params'=>array('level'=>'option_values')));
		}

		/**
		* Метод, удаляющий запись
		* @see metadata_change_datatable::exec_delete
		*/

		public function exec_delete ($pk) {
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'];
			$save_fields = $sys_fields;
			foreach ($sys_fields as $num=>$value) {
				if ($value['value']==$pk['value']) {
					unset($sys_fields[$num]);
					try {
						$this->check_user_data();
						parent::exec_delete ($pk);
					}
					catch (Exception $e) {
						$sys_fields=$save_fields;
						throw $e;
					}
					return;
				}
			}
		}
		
		/**
		* Метод, вызывающий изменение порядка полей в списке
		* @see metadata_change_datatable::action_order
		*/

		public function action_order () {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_order ($pk, $_REQUEST['dir']);
			$this->url->redirect("", array('pk'=>$pk, 'add_params'=>array('level'=>'option_values')));
		}
		
		/**
		* Метод, изменяющий порядок записи в списке
		* @param array $pk Первичный ключ поля
		* @param string $dir Направление, up - вверх, down - вниз
		* @see metadata_change_datatable::exec_order
		*/
		
		public function exec_order ($pk, $dir) {
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]['value_list'];
			$num = -1;

			// получаем номер искомой записи
			foreach ($sys_fields as $i=>$value) 
				if ($value['value']==$pk['value']) $num=$i;
			
			// получаем номер записи с которой нужно поменять искомую	
			if ($num>-1) 
				$swap_num = ($dir=='up')?$num-1:$num+1;
			
			// меняем
			if ($sys_fields[$num] && $sys_fields[$swap_num]) {
				$tmp = $sys_fields[$num];
				$sys_fields[$num]=$sys_fields[$swap_num];
				$sys_fields[$swap_num]=$tmp;
			}
			parent::exec_order($pk, $dir);
		}
	}
?>