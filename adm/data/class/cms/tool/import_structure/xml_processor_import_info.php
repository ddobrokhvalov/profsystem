<?PHP

	/**
	* Наследник xml_processor, занимающийся парсингом информационного файла экспорта и проверкой его
	* Необходим для процесса проверки файлов экспорта, запускаемого перед проведением импорта
	*
	* @package RBC_Contents_5_0
	* @subpackage cms
	* @copyright Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin 
	*/
	
	class xml_processor_import_info extends xml_processor {
		
		/**
		* Данные полученные из информационного файла
		* @var array
		*/
		
		private $info=array();
		
		/**
		* Общие данные операции импорта
		* @var array
		*/
		private $data;
		
		
		/**
		* Директории, которые существуют уже в папке, в которую производится импорт
		* @var array
		*/
		
		private $current_root_dirs=array();
			
		/**
		* Конструктор, дополнительно заполняем св-во current_root_dirs
		*/
		
		public function __construct($xml_file, &$data) {
			parent::__construct($xml_file);
			$this->data =& $data;
			
			$page_obj = object::factory('PAGE');
			$page_obj -> apply_object_parameters( $none = array( '_f_PARENT_ID' => $this->data['_f_page_id'] ) );
			$root_pages = $page_obj->get_index_records($this->data, 'm2m', array());
			
			foreach ($root_pages as $page) {
				$full_page  = $page_obj->get_change_record($page_obj->primary_key->get_from_record($page));
				if ($full_page['DIR_NAME'])	$this->current_root_dirs[] = $full_page['DIR_NAME'];
			}
			$page_obj->__destruct();
		}
		
		
		/**
		* Парсинг тага INFO, сохраняет все его атрибуты
		*/
		
		protected function process_tag_info ($tag, $context) {
			$this->info=$tag['attributes'];
			return;
		}
		
		/**
		* Парсинг тага SITE, сохраняет название сайта
		*/
		
		protected function process_tag_site ($tag, $context) {
			$this->info['site_name']=$tag['value'];
		}
		
		/**
		* Парсинг тага ADM_HTDOCS_HTTP
		* сохраняет данные, и проверяет, в случае если экспорт был неполным, а содержимое данного тага не совпадает с целевым сервером
		* вызывается исключение
		*/
		
		protected function process_tag_adm_htdocs_http ($tag, $context) {
			$this->info['adm_htdocs_http']=$tag['value'];
			$this->info['same_server'] = params::$params['adm_htdocs_http']['value'] === $this->info['adm_htdocs_http'];
			
			if (!$this->info['INF_BLOCK'] || !$this->info['CONTENT'] || !$this->info['TEMPLATE'] ||!$this->info['TEMPLATE_TYPE']) {
				if (!$this->info['same_server']) {
					$what_needed = array();
					if (!$this->info['INF_BLOCK']) 
						$what_needed[] = metadata::$lang['lang_import_of_block'];
					if (!$this->info['CONTENT'])
						$what_needed[] = metadata::$lang['lang_auth_system_content'];
					if (!$this->info['TEMPLATE'])
						$what_needed[] = metadata::$lang['lang_import_of_template'];
					if (!$this->info['TEMPLATE_TYPE'])
						$what_needed[] = metadata::$lang['lang_import_of_template_type'];
						
					throw new Exception(metadata::$lang['lang_import_can_not_import_partial_data'].': '.implode(', ', $what_needed));
				}
			}
		}
		
		/** 
		* Парсинг тага LANG
		* Сохраняет данные о языках интерфейса, существовавших в исходной системе
		*/
		
		protected function process_tag_interface_lang ($tag, $context) {
			$this->info['interface_langs'][]=$tag['attributes']['SYSTEM_NAME'];
		}
		
		/**
		* Парсинг тага PRG_MODULE
		* Проверяет существует ли данный модуль на целевой системе
		*/
		
		protected function process_tag_prg_module ($tag, $context) {
			$module = db::sql_select('SELECT * FROM PRG_MODULE WHERE SYSTEM_NAME=:module_system_name', array('module_system_name'=>$tag['attributes']['SYSTEM_NAME']));
			if (!sizeof($module))
				throw new Exception(metadata::$lang['lang_import_no_module'].' '.$tag['attributes']['SYSTEM_NAME']);
			return array('module'=>$module[0]);
		}
		
		/**
		* Парсинг тага MODULE_PARAM
		* Проверяет совпадение наличия параметров модулей на исходной и целевой системах
		*/
		
		protected function process_tag_module_param ($tag, $context) {
			if (!$context['module']) return;
			
			$module_param = db::sql_select(
							'SELECT 
								* 
							FROM 
								MODULE_PARAM 
							WHERE 
								PRG_MODULE_ID=:prg_module_id 
									AND 
										SYSTEM_NAME=:module_param_system_name
									AND 
										PARAM_TYPE=:module_param_param_type', 
							array(
								'prg_module_id'=>$context['module']['PRG_MODULE_ID'],
								'module_param_system_name'=>$tag['attributes']['SYSTEM_NAME'],
								'module_param_param_type'=>$tag['attributes']['TYPE']
							)
			);
			
			if (!sizeof($module_param)) 
				throw new Exception(metadata::$lang['lang_import_no_module_param_value'].' '.$context['module']['TITLE'].' - '.$tag['attributes']['SYSTEM_NAME'].' '.metadata::$lang['lang_import_with_type'].' '.$tag['attributes']['TYPE']);
			return array_merge($context, array('module_param'=>$module_param[0]));
		}
		
		/**
		* Парсинг тага PARAM_VALUE
		* Проверяет наличие всех значений модуля на целевой системе
		*/
		
		protected function process_tag_param_value ($tag, $context) {
			if (!$context['module'] || !$context['module_param']) return;
			
			// если шаблон, и экспортировались шаблоны, то проверять не нужно
			if (($this->info['TEMPLATE']) && ($context['module_param']['PARAM_TYPE']=='template')) return;
			
			$param_value = db::sql_select(
				'SELECT
					*
				 FROM
					PARAM_VALUE
				 WHERE
				 	MODULE_PARAM_ID=:module_param_id
				 		AND
				 			VALUE=:param_value',
				 array (
				 		'module_param_id'=>$context['module_param']['MODULE_PARAM_ID'],
				 		'param_value'=>$tag['value']
				 )
			);
			
			if (!sizeof($param_value)) 
				throw new Exception(metadata::$lang['lang_import_no_param_value'].' '.$context['module']['TITLE'].' - '.$context['module_param']['TITLE'].' = '.$tag['value']);
		}
		
		/** 
		* Парсинг тага OBJECT
		* Проверяет наличие необходимых объектов на целевой системе
		*/
		
		protected function process_tag_object ($tag, $context) {
			if (!metadata::$objects[$tag['attributes']['SYSTEM_NAME']])
				throw new Exception(metadata::$lang['lang_import_no_object'].' '.$tag['attributes']['SYSTEM_NAME']);

			return array('tag'=>&$tag, 'object'=>metadata::$objects[$tag['attributes']['SYSTEM_NAME']], 'object_name'=>$tag['attributes']['SYSTEM_NAME']);
		}
		
		
		/** 
		* Парсинг тага FIELD
		* Проверяет совпадение полей объектов на исходной и целевой системах в случае если он находится под тагом OBJECT
		*/
		
		protected function process_tag_field ($tag, $context) {
			if ($context['tag']['tag']=='OBJECT') {
				if (!$context['object']) return;
			
				if (!$context['object']['fields'][$tag['attributes']['FIELD_NAME']] || ($context['object']['fields'][$tag['attributes']['FIELD_NAME']]['type']!=$tag['attributes']['TYPE']))
					throw new Exception(metadata::$lang['lang_import_no_object_field'].' "'.$context['object_name'].'": "'.$tag['attributes']['FIELD_NAME'].'" '.metadata::$lang['lang_import_with_type'].' '.$tag['attributes']['TYPE']);
			}
		}
		
		/**
		* Парсинг така WORKFLOWS
		* Ничего не делаем
		*/
		
		protected function process_tag_workflows ($tag, $context) {}
		
		/**
		* Парсинг тага RECORD
		* На данный момент мы предполагаем только данные из таблицы WF_WORKFLOW
		* Зааписываем данные в общий массив info
		*/
		
		protected function process_tag_record ($tag, $context) {
			if ($tag['attributes']['TABLE_NAME']=='WF_WORKFLOW') {
				$workflow_obj = object::factory('WF_WORKFLOW');
				// для вызова нужен массив с info
				$lang_obj = object::factory('LANG');
				$lang_obj -> apply_object_parameters( $none = array() );
				$data_arr = array ('info'=>$this->get_info(), 'langs_in_admin' => lib::array_reindex($lang_obj->get_index_records($this->info, 'm2m', array('by_in_admin'=>1)), 'ROOT_DIR'));
				$lang_obj->__destruct();
				$this->info['workflows'][$tag['attributes']['RECORD_ID']]=$workflow_obj->get_import_field_values($tag['children'], $data_arr);
				
				$workflow_obj->__destruct();
			}
			
			return array('tag'=>&$tag);
		}
		
		/**
		* Парсинг тага FIELD_VALUE
		* На данный момент это данные только из таблицы WF_WORKFLOW, обрабатываемые вышестоящими методами
		*/
		
		protected function process_tag_field_value ($tag, $context) {}
		
		
		/**
		* Обрабатываем таг TE_OBJECT, который идет внутри структуры workflows
		* В нем находятся объекты, к которым применима данная цепочка публикации
		* Данные об этих объектах сохраняем в info
		*/
		
		protected function process_tag_te_object ($tag, $context) {
			if ($context['tag']['tag']=='RECORD') 
				$this->info['workflows'][$context['tag']['attributes']['RECORD_ID']]['TE_OBJECTS'][]=$tag['attributes']['name'];
		}
		
		/**
		* Парсинг тага ROOT_DIRS
		* Ничего не делаем
		*/

		protected function process_tag_root_dirs ($tag, $context) {}
		
		/**
		* Обрабатываем таг DIR, в котором должно присутствовать название корневой директории, 
		* которая была на экспортируемой системе. У нас в том месте, куда импортируем директории с 
		* таким названием оказаться не должно
		*/
		
		protected function process_tag_dir ($tag, $context) {
			if (in_array($tag['attributes']['name'], $this->current_root_dirs))
				throw new Exception(metadata::$lang['lang_import_child_page_already_exists_with_dir'].' "'.$tag['attributes']['name'].'"');
		}
		

		/**
		* Возвращает полученную информацию
		* @return array
		*/
		
		public function get_info() {
			return $this->info;
		}
		
		/**
		* Неизвестные ошибки вызывают exception
		*/
		
		protected function error_register($message, $tag) {
			throw new Exception($message.': '.print_r($tag, 1));
		}
	}
?>