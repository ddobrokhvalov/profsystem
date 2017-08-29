<?PHP

	/**
	* Префикс, добавляемый к системным именам создаваемых пользователем полей
	*/
	
	define ('USER_FIELD_PREFIX', 'UF_');
	
	/**
	* Класс изменения метаданных через web-интерфейс. Работа с полями таблиц.
	* 
	* @package		RBC_Contents_5_0
 	* @subpackage te
 	* @copyright	Copyright (c) 2008 RBC SOFT
	* @author Alexandr Vladykin <avladykin@rbc.ru>
	*/

	class metadata_change_fields extends metadata_change_datatable {
			
		/**
		* Конструктор, устанавливаются в статусную строку необходимые значения
		*/
		
		public function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);
			
			// для того чтобы получить объект url
			$this -> apply_object_parameters( $_REQUEST );
			
			$this->title = $this->app_tables[$_REQUEST['table_system_name']]['title'];
			$this->path_id_array[]=array('TITLE'=>$this->title, 'URL'=>$this->url->get_url('index', array('restore_params'=>1)));
		}


		/**
		* Метод по умолчанию, выводит список полей
		* @see metadata_change_datatable::action_index
		*/
		
		public function action_index () {
			$this->check_table($this->get_pk($_REQUEST));
			parent::action_index();
		}
		
		
		/**
		* Возвращает из массива данных первичный ключ записи
		* @param array $data Данные
		* @return array
		* @see metadata_change_datatable::get_pk
		*/

		protected function get_pk ($data) {
			return array_intersect_key($data, array_flip(array('table_system_name', 'field_system_name')));
		}
		
		
		/**
		* Возвращает основные операции для таблицы
		* @see metadata_change_datatable::get_table_operations
		* @return array
		*/

		protected function get_table_operations () {
			$pk = $this->get_pk($_REQUEST);
			unset($pk['field_system_name']);
			$index_operations['add'] = array( 
				'name' => 'add', 
				'alt' => metadata::$lang['lang_add'], 
				'url' => $this -> url -> get_url( 
					'add', 
					array(
						'pk'=>$pk,
						"add_params"=>array('level'=>'fields'),
						"save_params" => 1
					) 
				) 
			);
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
				'_field_name' => array (
					"title" => metadata::$lang['lang_name'],
					"is_main" => 1,
				),
				'_system_name' => array (
					"title" => metadata::$lang['lang_system_name'],
					"is_main" => 1,
				),
				'_type' => array (
					"title" => metadata::$lang['lang_system_url_type'],
					"is_main" => 1,
				),
				'_is_main' => array (
					"title" => metadata::$lang['lang_metadata_changer_field_is_main'],
					"type" => 'checkbox'
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

		protected function get_table_records ($pk) {
			$this->check_table($pk);
			$ret = array();
			$i=0;
			
			if (is_array($this->app_tables[$pk['table_system_name']]['fields']))
			foreach ($this->app_tables[$pk['table_system_name']]['fields'] as $system_name=>$field) {
				$pk_field = array('table_system_name'=>$pk['table_system_name'], 'field_system_name'=>$system_name);
				if ($field['app_level']) {
					 if (!$this->user_data[$pk['table_system_name']]['fields'][$system_name])
					 	continue;
					 else
					 	$field=$this->user_data[$pk['table_system_name']]['fields'][$system_name];
				}
				
				$ret[$i++] = array (
					'_number' => $i,
					'_field_name' => $field['title'],
					'_system_name' => $system_name,
					'_type' => ($this->check_field($pk_field, false))?$this->field_types[$field['type']]:'',
					'_is_main' => $field['is_main'],
					'_order' => $this->check_field($pk_field, false, false)?array('up'=>$this->url->get_url("order",array("pk"=>$pk_field + array("dir"=>"up", 'level'=>'fields'))), 'down'=>$this->url->get_url("order",array("pk"=>$pk_field + array("dir"=>"down", 'level'=>'fields')))):'',
					'_ops' => $this->get_record_operations ($pk_field)	
								
				);
			}
			
			// очищаем верхнюю и нижнюю стрелку в order
			
			for ($i=0, $n=sizeof($ret); $i<$n; $i++) {
				if ((!isset($ret[$i-1]) || ($ret[$i-1]['_order'] == '')) && $ret[$i]['_order']['up']) {
					unset($ret[$i]['_order']['up']);
				}

				if ((!isset($ret[$i+1]) || ($ret[$i+1]['_order'] == '')) && $ret[$i]['_order']['down'])
					unset($ret[$i]['_order']['down']);	
			}
			
			return $ret;
		}
		
		
		/**
		* Возвращает данные операций для строки таблицы
		* @param array $pk Первичный ключ записи (table_system_name, field_system_name)
		* @return string
		*/
		
		protected function get_record_operations ($pk) {
			if (!$this->check_field($pk, false)) return '';
			$ops=array (
				table::format_index_op(
					array(
						"name" => "change", 
						"alt" => metadata::$lang["lang_change"], 
						"url" => $this->url->get_url("change", array("pk"=>$pk, "save_params" => 1, "add_params"=>array('level'=>'fields'))),
					)
				),
				table::format_index_op(
					array(
						"name" => "delete", 
						"alt" => metadata::$lang["lang_delete"], 
						"url" => $this->url->get_url("delete", array("pk"=>$pk, "add_params"=>array('level'=>'fields'))),
						"confirm" => true
					)
				)
			);
			
			if ($this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['type']=='select1') {
				$ops[] = table::format_index_op (
					array (
						"name" => "value_list",
						"alt" => metadata::$lang["lang_metadata_changer_value_list"],
						"url" => $this->url->get_url("", array("pk"=>$pk, "add_params"=>array('level'=>'option_values')))
					)
				);
			}
			
			return join(', ', $ops);
		}
		
		// -----------------------------------------------------------------------------------------------------------------------------------
		
		/**
		* HTML-карточка записи
		* @param array $pk Первичный ключ
		* @return array название-содержимое
		* @see metadata_change_datatable::html_card
		*/
		
		protected function html_card ($pk) {
			$form_name = html_element::get_next_form_name();
			$operations = array();
			
			$pk_for_hidden = array('pk'=>$pk, 'add_params'=>array('level'=>'fields'));
			
			if (!$pk['field_system_name']) {
				$title=metadata::$lang["lang_metadata_changer_add_field"];
				$operations =$this -> get_operations( array( 'save', 'cancel'),  $form_name);
				$action="added";
			}
			else {
				$this->check_field($pk);
				$title=metadata::$lang["lang_metadata_changer_change_field"];
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

			return array( $title, $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' ).$this->html_card_js($form_name) );
		}
		
		/**
		* Возвращает javascript для карточки html
		* Выводит поля для пользователя в соответствии с типом поля
		* @return string
		*/
		
		private function html_card_js ($form_name) {
			return <<<JSCRIPT
<script>
	
	show_fields_by_type();
	
	function show_fields_by_type () {
		var curr_type = document.forms['{$form_name}'].elements['_form_type'].value
		check_filter(curr_type)
		check_editor(curr_type)
	}
	
	addListener( document.forms['{$form_name}'].elements['_form_type'], 'change', show_fields_by_type );
	
	// может быть выведен только для типов checkbox, select1, select2, text, textarea, int, date и datetime        
	function check_filter (curr_type) {
		var filter_types = new Array ('checkbox', 'select1', 'text', 'textarea', 'int', 'date', 'datetime')
		
		var filter_el = document.forms['{$form_name}'].elements['_form_filter'][1]
		var filter_tr = document.getElementById('_form_filter')

		for (var i=0; i<filter_types.length; i++)
			if (curr_type==filter_types[i]) {
				filter_tr.style.display=''
				return
			}
		
		filter_tr.style.display = 'none'
		filter_el.checked = false
	}
	
	// применим только для textarea        
	function check_editor(curr_type) {
		var editor_el = document.forms['{$form_name}']['_form_editor'][1]
		var editor_tr = document.getElementById('_form_editor')
		
		if (curr_type=='textarea') {
			editor_tr.style.display = ''
		}
		else {
			editor_el.checked = false
			editor_tr.style.display = 'none'
		}
	}
	
</script>
JSCRIPT;
		}
		
		/**
		* Возвращает поля для формы редактирования
		* @param array $pk Первичный ключ
		* @return array
		* @see metadata_change_datatable::get_fields_for_form
		*/

		protected function get_fields_for_form ($pk) {
			$fields = array (
				'title' => array ('title'=>metadata::$lang['lang_name'], 'type'=>'text', 'errors'=>_nonempty_),
				'system_name' => array ('title'=>metadata::$lang['lang_system_name'], 'type'=>'text', 'no_change'=>1, 'disabled'=>1, 'errors'=>_nonempty_|_dirname_),
				'type' => array ('title'=>metadata::$lang['lang_system_url_type'], 'type'=>'select1', 'value_list'=>$this->get_types_for_select (), 'errors'=>_nonempty_),
				'show' => array ('title'=>metadata::$lang['lang_metadata_changer_field_show'], 'type'=>'checkbox'),
				'is_main' => array ('title'=>metadata::$lang['lang_metadata_changer_field_is_main'], 'type'=>'checkbox'),
				'sort' => array (
					'title'=>metadata::$lang['lang_metadata_changer_field_sort'], 
					'type'=>'select1', 
					'value_list'=>array(
						array ( 
							'title'=>metadata::$lang['lang_metadata_changer_field_sort_asc'], 
							'value'=>'asc'
						),
						array (
							'title'=>metadata::$lang['lang_metadata_changer_field_sort_desc'],
							'value'=>'desc'
						)
					)
				),
				'not_empty' => array ('title'=>metadata::$lang['lang_metadata_changer_field_not_empty'], 'type'=>'checkbox'),
				'errors' => array (
					'title' => metadata::$lang['lang_metadata_changer_field_error_check'],
					'type' => 'select1',
					'value_list' => array (
						array (
							'title'=>metadata::$lang['lang_int'],
							'value'=>_int_
						),
						array (
							'title'=>metadata::$lang['lang_metadata_changer_float'],
							'value'=>_float_
						),
						array (
							'title'=>metadata::$lang['lang_email'],
							'value'=>_email_
						),
						array (
							'title'=>metadata::$lang['lang_metadata_changer_datetime'],
							'value'=>_datetime_
						),
						array (
							'title'=>metadata::$lang['lang_date'],
							'value'=>_date_
						),
						array (
							'title'=>metadata::$lang['lang_time'],
							'value'=>_time_
						),
						array (
							'title'=>metadata::$lang['lang_login'],
							'value'=>_login_
						),
						array (
							'title'=>metadata::$lang['lang_dir_name'],
							'value'=>_dirname_
						),
						array (
							'title'=>metadata::$lang['lang_metadata_changer_field_error_check_alphastring'],
							'value'=>_alphastring_
						),
					)
				),
				'filter' => array ('title' => metadata::$lang['lang_metadata_changer_field_filter'], 'type'=>'checkbox'),
				'no_add' => array ('title' => metadata::$lang['lang_metadata_changer_field_no_add'], 'type'=>'checkbox'),
				'no_change' => array ('title' => metadata::$lang['lang_metadata_changer_field_no_change'], 'type'=>'checkbox'),
				'editor' => array ('title' => metadata::$lang['lang_metadata_changer_field_editor'], 'type'=>'checkbox')
			);
			
			if (!$pk['field_system_name']) {
				// добавляем инфу о префиксе, который мы добавим
				$fields['system_name']['title'].=' ('.metadata::$lang['lang_metadata_changer_field_prefix_will_be_added'].' "'.USER_FIELD_PREFIX.'" )';
			}
			
			if ($this->is_sorted_in_not_user_accessible_data($pk['table_system_name'])) 
				unset($fields['sort']);
			
			$record = "";
			if ($pk['field_system_name']) {
				$record = $this->get_prepared_record($this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']]);
				$record['system_name']=$pk['field_system_name'];
			}
			
			return $this->get_form_fields($pk['field_system_name']?'change':'add', '_form_', $record, "", $fields);
		}
		
		/**
		* Возвращает данные по таблице, существует ли в ней сортировка в данных, 
		* на которые пользователь не может оказать влияние
		*
		* @param string $table_system_name Системное имя таблицы
		*
		* @return boolean
		*/
		
		private function is_sorted_in_not_user_accessible_data($table_system_name) {
			foreach ($this->app_tables[$table_system_name]['fields'] as $field_name=>$field_data) {
				if ($this->user_data[$table_system_name]['fields'][$field_name]) continue;
				
				if ($field_data['sort']) return true;
			}
			return false;
		}
		
		/**
		* Возвращает типы данных для селекта типов данных
		*/
		
		private function get_types_for_select () {
			$ret = array();
			foreach ($this->field_types as $type=>$title) {
				$ret[] = array ('title'=>$title, 'value'=>$type);
			}
			return $ret;
		}
		
		/**
		* Возвращает подготовленную запись для формы редактирования
		* @paran array $field_props Свойства поля
		* @return array
		*/
		
		private function get_prepared_record ($field_props) {
			$ret = array();
			foreach ($field_props as $prop_name=>$prop_value) {
				if ($prop_name=='errors') {
					$ret['not_empty']=$prop_value&_nonempty_;
					$ret['errors'] = $prop_value&~_nonempty_;
				}
				else {
					$ret[$prop_name]=$prop_value;
				}
			}
			return $ret;
		}
		
//---------------------------------------------------------------------------------------------------------------------------------------------------		

		/**
		* Метод, сохраняющий запись
		* @see metadata_change_datatable::exec_add
		* @todo  код дублируется с edit, все перенести в field
		*/

		public function exec_add($raw_fields, $prefix, $pk) {
			$this->check_table($pk);

			$fields=$this->get_prepared_fields($raw_fields, $prefix, $pk);
			
			$field_system_name=strtoupper(USER_FIELD_PREFIX.$fields['system_name']);
			
			// Если поле с таким же именем уже в системе есть, то ошибка
			if ($this->app_tables[$pk['table_system_name']]['fields'][$field_system_name]) 
				throw new Exception(metadata::$lang['lang_metadata_changer_field_name_already_exists'].': '.$field_system_name);
			
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$field_system_name];
			$save_fields = $sys_fields;
			$form_fields = array_keys($this->get_fields_for_form ($pk));
			
			if (($fields['no_change']!=$sys_fields['no_change']) || ($fields['no_add']!=$sys_fields['no_add'])) {
				if ($fields['no_change'] || $fields['no_add'])
					$sys_fields['disabled']=1;
				elseif (!$fields['no_change'] && !$fields['no_add'])
					unset($sys_fields['disabled']);
			}
			
			$sys_fields['errors']=$fields['not_empty']|$fields['errors'];
			if (!$sys_fields['errors']) unset($sys_fields['errors']);
			$form_fields = array_diff($form_fields, array('not_empty', 'errors', 'system_name'));

			// если была изменена сортировка, то нужно убрать сортировку у всех остальных полей
			if ($fields['sort'])  
				foreach ($this->user_data[$pk['table_system_name']]['fields'] as &$field ) 
					if ($field['sort']) unset($field['sort']);

			foreach ($form_fields as $field_name) 
				if ($fields[$field_name])
					$sys_fields[$field_name]=$fields[$field_name];
			
			$sys_fields['app_level']=1;
			
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
		* Метод, вызываемый при сохранении записи, при этом с возвратом на страницу редактирования
		* @see metadata_change_datatable::action_changed_apply
		*/
		
		public function action_changed_apply() {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_change($_REQUEST, "_form_", $pk);
			$this->url->redirect("change", array("pk"=>$pk, 'add_params'=>array('level'=>'fields')));
		}
		
		/**
		* Метод, изменяющий запись
		* @see metadata_change_datatable::exec_change
		*/
		
		public function exec_change($raw_fields, $prefix, $pk){
			$this->check_field($pk);

			$fields=$this->get_prepared_fields($raw_fields, $prefix, $pk);
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'][$pk['field_system_name']];
			$save_fields = $this->user_data[$pk['table_system_name']]['fields'];
			$form_fields = array_keys($this->get_fields_for_form ($pk));
			
			if ($fields['type']!=$sys_fields['type']) {
				unset($sys_fields['datatype_like']);
				unset($sys_fields['value']);
				unset($sys_fields['translate']);
				unset($sys_fields['is_null']);
				
				// изменен тип с select1 на другой
				if ($sys_fields['type']=='select1') 
					unset($sys_fields['value_list']);
			}
			
			
			if (($fields['no_change']!=$sys_fields['no_change']) || ($fields['no_add']!=$sys_fields['no_add'])) {
				if ($fields['no_change'] || $fields['no_add'])
					$sys_fields['disabled']=1;
				elseif (!$fields['no_change'] && !$fields['no_add'])
					unset($sys_fields['disabled']);
			}

			$sys_fields['errors']=$fields['not_empty']|$fields['errors'];
			if (!$sys_fields['errors']) unset($sys_fields['errors']);
			$form_fields = array_diff($form_fields, array('not_empty', 'errors', 'system_name'));
			

			// если была изменена сортировка, то нужно убрать сортировку у всех остальных полей
			if ($fields['sort'] && !$sys_fields['sort'])  
				foreach ($this->user_data[$pk['table_system_name']]['fields'] as &$field ) 
					if ($field['sort']) unset($field['sort']);

			foreach ($form_fields as $field_name) {
				if ($fields[$field_name]) {
					// чтобы не менять то что завязано на языковых константах
					if ($fields[$field_name] != $sys_fields[$field_name]) {
						$sys_fields[$field_name]=$fields[$field_name];
						unset($sys_fields['_lang_constant_'.$field_name]);
					}
				}
				else
					unset($sys_fields[$field_name]);
			}
			
			try {
				$this->check_user_data();
				parent::exec_change($raw_fields, $prefix, $pk);
			}
			catch (Exception $e) {
				$this->user_data[$pk['table_system_name']]['fields']=$save_fields;
				throw $e;
			}
			
		}	
		
		/**
		* Возвращает подготовленые к вставке в БД значения полей
		* @see metadata_change_datatable::get_prepared_fields
		*/
		
		protected function get_prepared_fields($raw_fields, $prefix, $pk){
			$fields = array();
			foreach($this->get_fields_for_form ($pk) as $field_name=>$field)
				$fields[$field_name]=$this->field->get_prepared($raw_fields[$prefix.$field_name], $field, $prefix.$field_name);
			return $fields;
		}
			
		
		/**
		* Метод, вызываемый при удалении записи
		* @see metadata_change_datatable::action_delete
		*/
		
		public function action_delete() {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_delete($pk);
			$this->url->redirect("", array('pk'=>$pk, 'add_params'=>array('level'=>'fields')));
		}
		
		/**
		* Метод, удаляющий запись
		* @see metadata_change_datatable::exec_delete
		* @todo Привести к единому механизму pk через $this->field
		*/
		
		public function exec_delete($pk) {
			$this->check_field($pk);
			
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'];
			$save_fields = $sys_fields;
			unset($sys_fields[$pk['field_system_name']]);

			try {
				$this->check_user_data();
				parent::exec_delete($pk);
			}
			catch (Exception $e) {
				$sys_fields=$save_fields;
				throw $e;
			}
		}
		
		/**
		* Метод, вызывающий изменение порядка полей в списке
		* @see metadata_change_datatable::action_order
		*/
		
		public function action_order () {
			$pk = $this->get_pk($_REQUEST);
			$this->exec_order ($pk, $_REQUEST['dir']);
			$this->url->redirect("", array('pk'=>$pk, 'add_params'=>array('level'=>'fields')));
		}
		
		/**
		* Метод, изменяющий порядок записи в списке
		* @param array $pk Первичный ключ поля
		* @param string $dir Направление, up - вверх, down - вниз
		* @see metadata_change_datatable::exec_order
		*/
		
		public function exec_order ($pk, $dir) {
			$this->check_field($pk, true, false);
			
			// будем перезаписывать данные в $new_fields
			$sys_fields =& $this->user_data[$pk['table_system_name']]['fields'];
			$new_fields = array();
			
			// получаем все поля в текущем порядке и находим искомое
			$field_names = array_keys($sys_fields);
			$needed_key = array_search($pk['field_system_name'], $field_names);
			
			// проверяем можем ли мы менять местами с указанным полем
			$swap_key = ($dir=='up')?$needed_key-1:$needed_key+1;
			$swap_pk = $pk;
			$swap_pk['field_system_name'] = $field_names[$swap_key];
			$this->check_field($swap_pk, true, false);
			
			// меняем местами
			$tmp = $field_names[$swap_key];
			$field_names[$swap_key]=$field_names[$needed_key];
			$field_names[$needed_key] = $tmp;
			
			// записываем данные в $new_fields
			for ($i=0, $n=sizeof($field_names); $i<$n; $i++) 
				$new_fields[$field_names[$i]] = $sys_fields[$field_names[$i]];
			
			// присваиваем все в user_data
			$sys_fields = $new_fields;
			parent::exec_order($pk, $dir);
		}
	}
?>