<?PHP
	/**
	* Наследник xml_processor, занимающийся парсингом файлов данных и проверкой информации в них
	* Необходим для процесса проверки файлов экспорта, запускаемого перед проведением импорта
	*
	* @package RBC_Contents_5_0
	* @subpackage cms
	* @copyright Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin 
	*
	* @todo после вынесения template_dir в параметры класса PARAM_VALUE - сделать вызов из него
	*/

	class xml_processor_import_check_data extends xml_processor {
		
		/**
		* Подсчитывает кол-во корневых записей в файле
		* @param int $records_count
		*/
		public $records_count=0;
		
		/**
		* Общие данные операции импорта
		* @var array
		*/
		private $data;
		
		
		/**
		* Заполняем данные
		*/
		
		public function __construct($xml_file, &$data) {
			parent::__construct($xml_file);
			$this->data =& $data;
		}

		/**
		* Парсинг корневого тага DATA
		*/
		
		protected function process_tag_data($tag, $context) {
			return true;
		}
		
		/**
		* Парсинг тага RECORD - главного тага записи
		* Проверяем наличие таблицы на целевой системе и возможность записи файлов шаблонов
		*
		* а также если workflow записи относится к объекту с 
		* workflow_scope = block, то записываем в спец. массив block_workflows, ибо для таких записей мы можем поменять workflow, 
		* если его уже нет в системе, или ставим на новую систему
		*/
		
		protected function process_tag_record ($tag, $context) {
			$this->records_count++;
			
			if (!metadata::$objects[$tag['attributes']['TABLE_NAME']])
				throw new Exception(metadata::$lang['lang_import_no_object'].' '.$tag['attributes']['TABLE_NAME']);
			
			// должно быть возможно записать файлы шаблонов
			if ($tag['attributes']['TABLE_NAME']=='TEMPLATE') {
				static $template_checked;
				if (!$template_checked) {
					$template_obj = object::factory('TEMPLATE');
					if (!is_writeable($template_obj->template_root_dir))
						throw new Exception(metadata::$lang['lang_import_template_dir_is_not_writeable'].': '.$template_obj->template_root_dir);
					$template_obj->__destruct();
					$template_checked=true;
				}
			}
			elseif ($tag['attributes']['TABLE_NAME']=='INF_BLOCK') {
				if (sizeof($tag['children']))
					// получаем для записи имя объекта и ID воркфлоу
					foreach ($tag['children'] as $ch) {
						if ($ch['attributes']['FIELD_NAME']=='TE_OBJECT_ID') 
							$te_object_name = $ch['value'];
						elseif ($ch['attributes']['FIELD_NAME']=='WF_WORKFLOW_ID') 
							$wf_workflow_id = $ch['value'];
					}
					
					
				if ($wf_workflow_id && $this->data['workflows'][$wf_workflow_id]) {
					if (metadata::$objects[$te_object_name]['workflow_scope']=='block') {
						$this->data['block_workflows'][$wf_workflow_id] = $this->data['workflows'][$wf_workflow_id];
					}
				}
			}
			
			return $tag;
		}
		
		/**
		* Парсинг тага FIELD
		* Проверяет существует ли на целевой системе такое поле
		*/
		
		protected function process_tag_field ($tag, $context) {
			if (!metadata::$objects[$context['attributes']['TABLE_NAME']]['fields'][$tag['attributes']['FIELD_NAME']])
				throw new Exception(metadata::$lang['lang_import_no_field_for_object'].$context['attributes']['TABLE_NAME'].' - '.$tag['attributes']['FIELD_NAME']);
				
			return array ('field_name'=>$tag['attributes']['FIELD_NAME'], 'table'=>$context['attributes']['TABLE_NAME']);
		}
		
		/**
		* Парсинг тага FIELD_VALUE
		* Проверка чтобы не экспортировали корневой раздел
		* И возможность записи файлов шаблонов модулей
		*/
		
		protected function process_tag_field_value ($tag, $context) {
			// не должно быть корневого раздела
			if (($context['table']=='PAGE') && ($context['field_name']=='PARENT_ID') && ($tag['value']==0))
				throw new DBDebugException(metadata::$lang['lang_import_can_not_import_hi_level_page'], print_r($tag, 1));
			
			// должно быть возможно записать файлы шаблонов модуля	
			if (($context['table']=='PARAM_VALUE') && ($context['field_name']=='MODULE_PARAM_ID')) {
				$template_root_dir = params::$params['common_data_server']['value'].'module_tpl/'.$tag['value'].'/';
				if (!is_writeable($template_root_dir)) 
					throw new Exception(metadata::$lang['lang_import_template_dir_is_not_writeable'].': '.$template_root_dir);
			}
		}
		
		/**
		* Вызывается после обработки тага RECORD
		* Проверяет чтобы в файле экспорта были все поля, которые есть на целевой системе для данной таблицы
		*/
		
		protected function processed_tag_record($tag, $context) {
			if (!is_array($context)) $context=array();
			
			$field_names = array_keys(lib::array_reindex($context, 'field_name'));
			
			$obj = object::factory($tag['attributes']['TABLE_NAME']);
			$fields = array_values($obj->get_fields_for_export());
			$obj->__destruct();
			
			$diff=array_diff($fields, $field_names);
			
			if (sizeof($diff))
				throw new Exception(metadata::$lang['lang_import_different_fields'].':'.implode(', ', $diff));
		}
		
		/**
		* Неизвестные ошибки вызывают exception
		*/

		protected function error_register($message, $tag) {
			if ($message!='Unknown tag')
				throw new Exception($message.print_r($tag, 1));
		}
	}
?>