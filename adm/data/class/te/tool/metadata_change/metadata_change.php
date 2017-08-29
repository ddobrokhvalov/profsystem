<?PHP
	/**
	* Класс измененимя метаданных через web-интерфейс
	* Сохраняет метаданные в сессию, а при подтверждении записывает данные в файл метаданных и запускает пребилдер
	* Работает на основе подклассов
	* @package		RBC_Contents_5_0
 	* @subpackage te
 	* @copyright	Copyright (c) 2008 RBC SOFT
	* @author Alexandr Vladykin <avladykin@rbc.ru>
	*/
	
	class metadata_change extends tool {
		
		/**
		* Исходные метаданные по def-файлу
		* @var array
		*/
		
		protected $def_data;
		
		/**
		* Метаданные созданные пользователем, хранимые в сессии
		* @var array
		*/
		
		protected $user_data;

		/**
		* Здесь хранится совокупность метаданных системы и созданных пользователем. Нужна для показа данных из других файлов метаданных
		* @var array
		*/
		
		protected $app_tables;
		
		/**
		* Здесь будут храниться типы полей, которые можно добавлять/редактировать в системе
		* @var array
		* @see metadata_change::set_field_types
		*/
		
		protected $field_types;
		
		/**
		* Здесь хранится HTML-представление информационного блока о том, что нужно нажать кнопку Применить, чтобы изменения появились в системе
		* @var string
		* @see metadata_change::set_info_block
		*/
		
		protected $info_block;
				
		
		/**
		* Конструктор, заполняются необходимые поля
		*/
		
		public function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);

			include (params::$params['adm_data_server']['value']."def/app_objects.php");
			$app_objects=$this->set_lang_constants($app_objects);

			// после подгружения файла метаданных app_objects, все хранится в переменной $app_objects.
			$this->def_data=$this->user_data=$app_objects;
			
			// Если в сессиии имеются метаданные, записанные пользователем, подгружаем их в user_data
			if ($_SESSION['metadata']['user_app_tables'] && $_SESSION['metadata']['changed']) {
				$this->user_data=$_SESSION['metadata']['user_app_tables'];
				
				// запоминаем в сессию данные из app_tables, чтобы в дальнейшем не перезаписать данные, внесенные пользователем в def-файл
				if (!$_SESSION['metadata']['def_data']) 
					$_SESSION['metadata']['def_data'] = $this->def_data;
			}
				
			// соединяем по ссылке данные сессии и user_data
			$_SESSION['metadata']['user_app_tables'] =& $this->user_data;
			
			// корректно обрабатываем языковые константы в user_data
			$this->user_data = $this->set_lang_constants($this->unset_lang_constants($this->user_data));
			
			// формируем app_tables из данных metadata::$objects
			$this->app_tables=array_filter(metadata::$objects, array($this, 'callback_func_array_filter_tables_by_app_level'));
			
			
			
				
			
			$this->app_tables = $this->array_merge_recursive_block($this->app_tables, $this->user_data);
			
			// заполняем типы полей
			$this->set_field_types();
		}
		
		/**
		 * Метод прикладывает к объекту данные из $_REQUEST
		 */
		public function apply_object_parameters( &$request )
		{
			parent::apply_object_parameters( $request );
			
			// формируем информационный блок
			$this -> set_info_block();
		}		
		
		/**
		* Заполняются типы полей необходимыми значениями
		*/
		
		private function set_field_types() {
			$this->field_types=array(
				'text'=>metadata::$lang['lang_string'],
				'int'=>metadata::$lang['lang_int'],
				'float'=>metadata::$lang['lang_metadata_changer_float'],
				'checkbox'=>metadata::$lang['lang_checkbox'],
				'textarea'=>metadata::$lang['lang_metadata_changer_text'],
				'order'=>metadata::$lang['lang_order'],
				'date'=>metadata::$lang['lang_date'],
				'datetime'=>metadata::$lang['lang_metadata_changer_datetime'],
				'select1'=>metadata::$lang['lang_metadata_changer_select1'],
				'img'=>metadata::$lang['lang_metadata_changer_img'],
				'file'=>metadata::$lang['lang_file']
			);
		}
		
		/**
		* Формируется информационный блок
		*/
		
		private function set_info_block() {
			if ($this->get_user_changes()) {
				$msg = metadata::$lang['lang_metadata_changer_warning_changes_will_not_be_available_untill_click_commit'].' <A href="'.$this->url->get_url('commit_changes', array('save_params'=>1)).'">'.metadata::$lang['lang_metadata_changer_commit_changes'].'</A>. '.metadata::$lang['lang_metadata_changer_warning_also_cancel_changes'].' <A href="'.$this->url->get_url('undo_changes', array('clear_prev_params'=>1)).'">'.metadata::$lang['lang_metadata_changer_cancel_changes'].'</A>.';
				$tpl = new smarty_ee( metadata::$lang );
				$tpl->assign('msg', $msg);
				$this->info_block=$tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
			}
		}
		
		/**
		* Применяются языковые константы к данным из дефа
		*/
		
		private function set_lang_constants($metadata) {
			if (is_array($metadata)) {
				$ret=array();
				foreach ($metadata as $name=>$value) 
					if (is_array($value)) 
						$ret[$name] = $this->set_lang_constants($value);
					else 
						if (isset(metadata::$lang[$value])) {
							$ret[$name]=metadata::$lang[$value];
							$ret['_lang_constant_'.$name]=$value;
						}
						else 
							$ret[$name]=$value;
				return $ret;
			}
			return null;
		}
		
		/**
		* Отменяем данные языковых констант из дефа
		*/

		private function unset_lang_constants($metadata) {
			if (is_array($metadata)) {
				$ret=array();
				foreach ($metadata as $name=>$value) 
					if (is_array($value)) 
						$ret[$name] = $this->unset_lang_constants($value);
					else 
						if (preg_match('/^_lang_constant_/', $name)) 
							continue;
						elseif ($metadata['_lang_constant_'.$name])
							$ret[$name]=$metadata['_lang_constant_'.$name];
						else 
							$ret[$name]=$value;
				return $ret;
			}
			return null;
		}
		
		
		/**
		* Функция обратного вызова, фильтрующая список таблиц по типу table и содержанию в них app_level
		*/
		
		public function callback_func_array_filter_tables_by_app_level ($var) {
			return $var['type']=='table' && isset($var['app_level']);
		}
		
		/**
		* Функция обратного вызова, фильтрующая список таблиц по типу table и отсутствию в них app_level
		*/
		
		public function callback_func_array_filter_tables_by_not_app_level ($var) {
			return $var['type']=='table' && !isset($var['app_level']);
		}

		/**
		* Рекурсивно соединяет 2 массива, при условии что 2-ой является частью 1-ого, но однозначно определяет порядок полей
		* @param array $paArray1 1-ый массив
		* @param array $paArray2 2-ой массив
		* @return array 
		*/
		
		protected function array_merge_recursive_block ($paArray1, $paArray2) {
			if (!is_array($paArray1) or !is_array($paArray2))
				return $paArray2;
			
			// если массивы ассоциативные, то выявляем блоки в первом массиве, и сортируем их в соответствии со вторым
			if (($paArray1 !== array_values($paArray1)) && ($paArray2 !== array_values($paArray2))) {
				$block_arr = array();
				foreach ($paArray1 as $key=>$val) 
					if ($paArray2[$key]) 
						$block_arr[$key] = $paArray1[$key];
					elseif (sizeof($block_arr) ) 
						// мы рассматриваем только одну группу, остальные нас не интересуют
						break;
					
				
				// если блок найден, то вставляем именно его в порядке как указано во втором массиве вместо того что есть в данном месте в 1-ом массиве
				if (sizeof($block_arr)) {
					$new_arr1 = array();
					foreach ($paArray1 as $key=>$val) {
						if (!array_key_exists($key, $block_arr)) 
							$new_arr1[$key] = $val;
						elseif (!$processed) {
							foreach (array_keys($paArray2) as $key2) {
								$new_arr1[$key2] = $paArray1[$key1];
								unset($block_arr[$key1]);
							}
							// если в block_arr еще что-то осталось, добавляем его
							$new_arr1 = array_merge($new_arr1, $block_arr);
							$processed=1;
						}
					}
					
					$paArray1 = $new_arr1;
				}
			}
			
			
			
			foreach ($paArray2 AS $sKey2 => $sValue2)
				$paArray1[$sKey2] = $this->array_merge_recursive_block(@$paArray1[$sKey2], $sValue2);
			
			return $paArray1;
		}
		
		/**
		* Вызов подклассов, может быть редактирование таблиц, полей и значений для поля select1
		* Проверяется по данным $_REQUEST['level']
		* @see object::subclass
		* @return string
		*/
		
		public function subclass() {
			include_once(params::$params["adm_data_server"]["value"]."class/te/tool/metadata_change/metadata_change_datatable.php");
			switch ($_REQUEST['level']) {
				case 'option_values' : $ret = 'metadata_change_option_values'; break;
				case 'fields' : $ret = 'metadata_change_fields'; break;
				default : $ret = 'metadata_change_tables'; break;
			}
			return $ret;
		}
		
		/**
		* Возвращает изменения, сделанные пользователем по сравнению с эталоном из def-файла
		* @return array
		*/
		
		protected function get_user_changes() {
			return $this->compare_metadata($this->user_data, $this->def_data);
		}
		
		
		/**
		* Рекурсивная функция сравнения метаданных
		* @var array $new Массив новых метаданных
		* @var array $old Массив старых метаданных
		* @return mixed Возвращает массив изменений в определенном формате, или false, если изменений не произошло
		*/
		
		private function compare_metadata ($new, $old) {
			foreach ($new as $key=>$value) {
				if (!isset($old[$key])) {
					// значит добавили новое значение
					$diff['added'][$key]=$value;
				}
				elseif (gettype($new[$key])!=gettype($old[$key])) {
					// значит было изменение, вынесено так, чтобы проверить на массивы
					$diff['changed'][$key]=$value;
				}
				elseif(is_array($value)) {
					// запускаем рекурсивную ф-ию, и записываем то что она возвратила в массив изменений
					$new_diff = $this->compare_metadata($value, $old[$key]);
					if ($new_diff) 
						$diff[$key]=$new_diff;
				}
				// если было изменение языка, а не значения, то _lang_constant_ будет существовать
				elseif (($new[$key]!=$old[$key]) && !($new['_lang_constant_'.$key])) {
					// было изменение
					$diff['changed'][$key]=$value;
				}
			}
			
			// цикл по старым метаданным, чтобы узнать кого нужно удалить
			foreach ($old as $key=>$value) {
				if (!isset($new[$key])) {
					$diff['deleted'][$key]=$value;
				} 
				elseif (is_array($value) && is_array($new[$key])) {
					$new_diff = $this->compare_metadata($new[$key], $value, 0);
					if ($new_diff)
						$diff[$key]=$new_diff;
				}
			}
			
			// если нет изменений, то проверяем не был ли изменен порядок
			if (!sizeof($diff)) 
				if (array_keys($new)!=array_keys($old))
					$diff['order_changed']=1;

			
			return sizeof($diff) ? $diff : false;
		}
		
		/**
		* Проверяет, существует ли в app_tables таблица $table_system_name
		* Если не существует выдает false или Exception, если $throw_exception=true
		* @param array $pk Первичный ключ таблицы (запись с ключом table_system_name)
		* @param boolean $throw_exception Вызывать ли Exception при неудаче
		* @return boolean
		*/
		
		protected function check_table ($pk, $throw_exception=true) {
			if (!$pk['table_system_name'] || !in_array($pk['table_system_name'], array_keys($this->app_tables))) {
				if ($throw_exception)
					throw new Exception(metadata::$lang['lang_metadata_changer_not_found_table'].' "'.$pk['table_system_name'].'"');
				else
					return false;
			}
			return true;
		}
		
		/**
		* Проверяет существует ли поле, и принадлежит ли оно к app
		* Если не существует выдает false или Exception, если $throw_exception=true
		* @param array $pk Массив первичного ключа поля (table_system_name, field_system_name)
		* @param boolean $throw_exception Вызывать ли Exception при неудаче
		* @param boolean $check_type проверять тип поля
		* @return boolean
		*/

		protected function check_field ($pk, $throw_exception=true, $check_type=true) {
			$this->check_table($pk);
			if (
				!$pk['field_system_name']
					|| !in_array(
							$pk['field_system_name'], 
							array_keys($this->app_tables[$pk['table_system_name']]['fields'])
						) 
					|| !$this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['app_level']
					|| 
						($check_type 
						&&
						(
						!in_array(
							$this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['type'],
							array_keys($this->field_types)
						)
						|| $this->app_tables[$pk['table_system_name']]['fields'][$pk['field_system_name']]['virtual']
						))
			) {
				if ($throw_exception)
					throw new Exception(metadata::$lang['lang_metadata_changer_not_found_field'].' "'.$pk['field_system_name'].'" ("'.$pk['table_system_name'].'")');
				return false;
			}
			return true;
		}
		
		/**
		* Проверяет корректность введенной пользователем информации о метаданных
		* В случае ошибок вызывает исключения
		* Перехватываемые ошибки:
		* -1- Должно быть хотя бы одно поле is_main
		* -2- Должно быть одно или ни одного поля sort
		* -3- Фильтр неприменим к полям с типами img, file, order, parent +
		* -4- Флаг editor может быть установлен только для типа поля textarea +
		* -5- Все значения value_list должны быть уникальными +
		* --
		* -6- Если тип поля изменен, datatype_like, value, translate, is_null должны быть сброшены +
		* -7- Флаг disabled всегда должен быть записан для no_change +
		* -8- value_list может быть указан только для поля с типом select1 +
		* -9- Для поля с типом select1 должна быть только не более одного поля по умолчанию.
		* --
		* @param boolean $full_check Полная проверка (проводится перед заливкой)
		* @todo Переделать app_tanles, чтобы каждый раз не вызывать в цикле замену field_options на user_data @see metadata_change_fields::get_table_records
		*/
		
		protected function check_user_data($full_check=false) {
			$this->app_tables = $this->array_merge_recursive_block($this->app_tables, $this->user_data);

			foreach ($this->user_data as $table_system_name=>$table_options) {
				if (is_array($table_options['fields'])) {
					$cnt=array();
					foreach ($this->app_tables[$table_system_name]['fields'] as $field_system_name=>$field_options) {
						
						// Если app_level, то берем данные из user_data
					 	if ($field_options['app_level']) {
					 		if (!$this->user_data[$table_system_name]['fields'][$field_system_name])
					 			continue;
					 		else
					 			$field_options=$this->user_data[$table_system_name]['fields'][$field_system_name];
						}
						
						// 1
						if ($field_options['is_main']) $cnt['is_main']++;
						// 2
						if ($field_options['sort']) $cnt['sort']++;
						
						if (!$this->check_field(array('table_system_name'=>$table_system_name, 'field_system_name'=>$field_system_name), false)) continue;
						
						// 6
						if (
							($this->app_tables[$table_system_name]['fields'][$field_system_name]) 
								&&
									($this->app_tables[$table_system_name]['fields'][$field_system_name]['type']!=$field_options['type'])
						) {
							$error=array();
							if ($field_options['datatype_like']) 
								$error[]=metadata::$lang['lang_metadata_changer_datatype_like'];
							if ($field_options['value'])
								$error[]=metadata::$lang['lang_metadata_changer_value'];
							if ($field_options['translate'])
								$error[]=metadata::$lang['lang_metadata_changer_translate'];
							if ($field_options['is_null'])
								$error[]=metadata::$lang['lang_metadata_changer_is_null'];
							
							if (sizeof($error)) 
								throw new Exception(metadata::$lang['lang_metadata_changer_type_changed_but_options_filled'].': "'.implode('", "', $error).'"'.' :"'.$field_system_name.'" ("'.$table_system_name.'")');
						} 
						
						// 7
						if ($field_options['no_change'] && !$field_options['disabled'])
							throw new Exception(metadata::$lang['lang_metadata_changer_type_disabled_must_be_filled_for_no_change'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
							
						// 8
						if (($field_options['type']!='select1') && (isset($field_options['value_list']))) 
							throw new Exception(metadata::$lang['lang_metadata_changer_value_list_can_be_filled_only_for_field_with_type_select1'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
							
						// 9
						if (is_array($field_options['value_list'])) {
							$selected=0;
							foreach ($field_options['value_list'] as $value) {
								if ($value['selected']) {
									if ($selected) 
										throw new Exception(metadata::$lang['lang_metadata_changer_can_not_be_more_than_one_default_value_list_in_the_option_values'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
									
									$selected = 1;
								}
							}
						}
							
						// 3 
						if ($field_options['filter'] && in_array($field_options['type'], array('img', 'file', 'order', 'parent'))) 
							throw new Exception(metadata::$lang['lang_metadata_changer_filter_can_not_be_applied_to_such_field_type'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
						
						// 4
						if ($field_options['editor'] && ($field_options['type']!='textarea')) 
							throw new Exception(metadata::$lang['lang_metadata_changer_editor_can_not_be_applied_to_not_textarea_type'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
						
						
						// 5
						if (sizeof($field_options['value_list'])) {
							$values=array();
							foreach ($field_options['value_list'] as $value) {
								if (in_array($value['value'], $values)) 
									throw new Exception(metadata::$lang['lang_metadata_changer_values_in_value_lists_must_be_unique'].' :"'.$field_system_name.'" ("'.$table_system_name.'")');
									
								$values[]=$value['value'];
							}
						}
					}
					
					// 1
					if (!$cnt['is_main'] && $full_check && $table_options['type']<>'internal_table') 
						throw new Exception(metadata::$lang['lang_metadata_changer_is_main_must_be_at_least_one'].': '.metadata::$lang['lang_table'].' "'.$table_system_name.'"');
					
					// 2
					if ($cnt['sort']>1) 
						throw new Exception(metadata::$lang['lang_metadata_changer_sort_must_be_not_more_than_one'].': '.metadata::$lang['lang_table'].' "'.$table_system_name.'"');
				}
			}
		}
		
//------------------------------------------------------------------------------------------------------------------------------------------------
		
		/**
		* Страница, вызываемая при подтверждении изменений
		*/
		
		public function action_commit_changes () {
			$changes = $this->get_user_changes();
			if (!$changes) 
				throw new Exception(metadata::$lang['lang_metadata_changer_no_changes_perfomed']);
				
			$operations = array();
			
			// проверяем изменены ли были данные в объектах системы с самого начала

			if ($this->compare_metadata($_SESSION['metadata']['def_data'], $this->def_data)) {
				$msg = metadata::$lang['lang_metadata_changer_warning_changed_can_not_be_committed_cause_of_changes_in_objects'];
				$tpl = new smarty_ee( metadata::$lang );
				$tpl->assign('msg', $msg);
				$info_block=$tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
			}
			else 
				$operations['save'] = array("name"=>"save", "alt"=>metadata::$lang["lang_action_save"], "url"=>$this->url->get_url ( "commit_changes_apply", array("clear_prev_params"=>1) )); 
				
			$operations['cancel'] = array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "undo_changes", array("clear_prev_params"=>1)));
			$operations['back'] = array("name"=>"back", "alt"=>metadata::$lang["lang_back"], "url"=>$this->url->get_url("", array( "restore_params" => 1 )));
			
			$changed_table = $this->get_def_changes_table();
			
			$tpl = new smarty_ee( metadata::$lang );
			$tpl -> assign( 'title', metadata::$lang['lang_metadata_changer_such_changes_performed'] );
			$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'form', $changed_table ); 
			
			$this->title = metadata::$lang['lang_metadata_changer_such_changes_performed'];
			$this->body = $info_block.$tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );
		}
		
		/**
		* Возвращает таблицу с измененениями, внесенными пользователем
		* @return string
		*/
		
		private function get_def_changes_table() {
			$header = array (
				'_table' => array (
					"title" => metadata::$lang['lang_table'],
				),
				'_info' => array (
					"is_main" => 1,
				),
			);
			
			return html_element::html_table( 
				array( 
					'header' => $header, 
					'list' => $this->get_def_changes_records()
				), 
				$this -> tpl_dir . 'core/html_element/html_table.tpl'
			);
		}
		
		/**
		* Возвращает записи для таблицы измененений, внесенных пользователем
		* return array
		*/
		
		private function get_def_changes_records() {
			$changes = $this->get_user_changes();
			$ret = array();
			foreach ($changes as $table=>$table_options) {
				if (sizeof($fields=$table_options['fields'])) {
					foreach ($fields as $field_name=>$field_value) {
						if ($field_name=='added') {
							foreach ($field_value as $new_name=>$new_value) {
								$ret[] = array (
									'_table' => $table,
									'_info' => metadata::$lang['lang_metadata_changer_field_added'].' '.$new_name
								);
							}
						}
						elseif ($field_name=='deleted') {
							foreach ($field_value as $del_name=>$del_value) {
								$ret[] = array (
									'_table' => $table,
									'_info' => metadata::$lang['lang_metadata_changer_field_deleted'].' '.$del_name
								);
							}
						}
						elseif ($field_name=='order_changed') {
							$ret[] = array (
								'_table' => $table,
								'_info' => metadata::$lang['lang_metadata_changer_order_changed']
							);
						}
						else {
							$ret[] = array (
								'_table' => $table,
								'_info' => metadata::$lang['lang_metadata_changer_field_changed'].' '.$field_name
							);
						}
					}
				}
			}

			return $ret;
		}
		
		/**
		* Отменяет изменения, внесенные пользователем
		*/
		
		public function action_undo_changes () {
			unset($_SESSION['metadata']);
			$this->url->redirect();
		}
		
		/**
		* Сохранение нового дефника и запуск пребилда
		*/
		
		public function action_commit_changes_apply () {
			$this->check_user_data(true);
			
			// Заменяем те значения языковых констант, которые пользователь не трогал на сами языковые константы
			$app_data = $this->unset_lang_constants($this->user_data);
		
			$new_app_objects_data = "<?PHP\n\$app_objects = ".var_export($app_data, true).";\n?>";
			$app_file=params::$params['adm_data_server']['value']."def/app_objects.php";
			file_put_contents($app_file, $new_app_objects_data);
			ob_start();
			$GLOBALS['db_modify']=true;
			try {
				include params::$params["adm_data_server"]["value"]."prebuild/prebuilder.php";
				unset ($_SESSION['metadata']);
			}
			catch (Exception $e) {
				ob_clean();
				throw $e;
			}
			$this->url->redirect();
		}
	}
?>