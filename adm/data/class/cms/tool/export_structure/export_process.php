<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/core/object/object.php");
include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/export_structure/distributed_process.php");

/**
* Реализация процесса экспорта данных с учетом распределенных операций
* Вызов производится при помощи статического метода process
*
* @todo Отвязать от контекста, убрать использование специфичных для распределенных операций значений $data
* @todo Что-то придумать с некоторым дублированием кода в методах 
* obj_records_to_xml, make_content, make_pages, get_used_modules_with_params_xml, get_used_objects_with_fields_xml
*
* @package		RBC_Contents_5_0
* @subpackage cms
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin 
*/

class export_process extends distributed_process {
	
	/**
	* ID разделов, которые необходимо экспортировать. Через запятую для прямого помещения в IN,
	* заполняется в методе set_exported_page_ids, вызывается в самом начале экспорта
	* @var string
	*/
	
	private $exported_page_ids;
	
	/**
	* Путь к директории экспорта, куда мы складываем файлы экспорта. 
	* Заполняется методом, создающим данную директорию, вызывается в начале экспорта.
	* @var string
	*/
	
	private $current_export_dir;
	
	/**
	* Время начала экспорта, которое используется при формировании названия директории и файлов экспорта
	* Имеет формат strftime('%Y%m%d%H%M%S'), заполняется методом, создающим директорию экспорта, который вызывается в начале экспорта
	* Заполняется методом, создающим данную директорию, вызывается в начале экспорта.
	* @var string
	*/
	
	private $timename;
	
	/**
	* Хранятся данные о корневой странице экспорта в формате, возвращаемом get_change_record
	* @var array
	*/
	
	private $root_page_info;
	
	/**
	* Директория экспорта, public для того, чтобы можно было с ней работать методом system_params::parse_template_param_for_object
	* @var string
	*/
	
	public  $export_dir = "{adm_htdocs_server}/export/";
	
	/**
	* http-путь к директории экспорта, public для того, чтобы можно было с ней работать методом system_params::parse_template_param_for_object
	* @var string
	*/
	
	public 	$export_dir_http = "{adm_htdocs_http}/export/";
	
	/**
	* Константы, которые необходимо преобразовать методом system_params::parse_template_param_for_object. public из-за его ограничений
	* @var array
	*/
	
	public  $constant_list = array('export_dir', 'export_dir_http');
	
	
	/**
	* Счетчик файлов данных, созданных процессом экспорта
	* @var int
	*/
	
	private $files_count = 0;
	
	/**
	* Путь к файлу, в который пишутся данные экспорта на данный момент
	* @var string
	*/
	
	private $current_file_name;
	
	/**
	* Файловый ресурс, в который пишутся данные экспорта на данный момент
	* @var resource
	*/
		
	private $current_file;
	
	/**
	* Сохраняются информация об используемых цепочках публикаций
	* @var array
	*/
	
	private $used_workflows = array();
	
	/**
	* Максимальное количество записей, возвращаемые SELECT за 1 раз
	*/
	
	const	RECORDS_ID_AT_ONCE = 100;
	
	/**
	* Конструктор, выполняет пребразования констант
	* @param array $params Дополнительные параметры
	*/
	
	protected function __construct ($params=array()) {
		system_params::parse_template_param_for_object ($this, $this->constant_list);
		parent::__construct($params);
	}

	/**
	* Запуск итерации экспорта
	* @param array $data Ссылка на данные, сохраняемые между итерациями. 
	*/
	
	protected function run (&$data) {
		$this->data = &$data;
		
		if (!$this->data['run_obj'] && !$this->only_counter) 
			// регистрим в логе
			log::register('log_records_change', 'export', '', 0, $this->data['_f_page_id']);
		
		$this->start_actions();
		if ($this->data['create_templates']) {
			if ($this->data['create_template_types']) {
				$this->make_template_areas();
				if ($this->is_terminated()) return;
				$this->make_template_types();
				if ($this->is_terminated()) return;
			}
			$this->make_page_templates();
			if ($this->is_terminated()) return;
			$this->make_module_templates();
			if ($this->is_terminated()) return;
		}

		if ($this->data['create_blocks']) {
			$this->make_blocks();
			if ($this->is_terminated()) return;
			if ($this->data['export_content']) {
				$this->make_content();
				if ($this->is_terminated()) return;
			}
		}
		
		$this->make_pages();
		if ($this->is_terminated()) return;
		
		$this->make_info ();
		if ($this->is_terminated()) return;
		
		// формируем конечное сообщение со ссылками на созданные файлы
		if (!$this->only_counter) 
			$this->data['complete_message'] = '<div class="img"><img src="/common/adm/img/messages/success.gif" alt="" style="margin-right: 2px"/></div>'.$this->data['complete_message'].$this->get_file_links_html();
	}
	
	
	/**
	* Выполняет необходимые действия, перед началом процесса экспорта
	*/
	
	private function start_actions() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$this->set_exported_page_ids();
		$this->create_export_dir();
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Заполняет свойство exported_page_ids id-шниками разделов, которые необходимо экспортировать
	*/
	
	private function set_exported_page_ids () {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		$page_obj = object::factory('PAGE');
		$page_obj -> apply_object_parameters( $none = array() );
		$pages=lib::array_reindex($page_obj->get_index_records($this->data, 'tree', '', $this->data['_f_page_id']), 'PAGE_ID');
		$this->exported_page_ids = implode(', ', array_keys($pages));
		
		if ($this->data['include_root']) {
			if (!$this->exported_page_ids)
				$this->exported_page_ids = $this->data['_f_page_id'];
			else
				$this->exported_page_ids = $this->data['_f_page_id'].', '.$this->exported_page_ids;
		}
		
		if (!$this->exported_page_ids) 
			throw new Exception(metadata::$lang['lang_es_error_not_found_any_pages_for_export']);
		
		if ($this->only_counter)
			$this->counter += sizeof (explode(', ', $this->exported_page_ids));
		
		$this->root_page_info = $page_obj->get_change_record(array('PAGE_ID'=>$this->data['_f_page_id']));
		
		$page_obj->__destruct();
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Создает экспортную директорию
	*/
		
	private function create_export_dir() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		// если просто считаем, то не нужно создавать каталог
		if ($this->only_counter) return;
		
		if (!is_dir($this->export_dir))
			throw new Exception (metadata::$lang['lang_es_error_no_export_dir'].': '.$this->export_dir);
			
		$this->timename = strftime('%Y%m%d%H%M%S');
		$this->current_export_dir = $this->export_dir.'/export_'.$this->timename;
		if (file_exists($this->current_export_dir))
			throw new Exception(metadata::$lang['lang_catalog_exists'].': '.$this->current_export_dir);
		
		if (!@mkdir($this->current_export_dir))
			throw new Exception(metadata::$lang['lang_es_error_cannot_create_export_dir'].': '.$this->current_export_dir);
		
		array_push($this->processed_stages, __METHOD__);
		
	}
	
	/**
	* Записывает данные об областях шаблонов
	*/
	
	private function make_template_areas() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$template_area_obj = object::factory('TEMPLATE_AREA');
		
		$this->obj_records_to_xml(
			$template_area_obj, 
			'SELECT
				TAM.TEMPLATE_AREA_ID
			FROM
				TEMPLATE_AREA_MAP TAM
					INNER JOIN 
						TEMPLATE_TYPE TT
							ON (TT.TEMPLATE_TYPE_ID=TAM.TEMPLATE_TYPE_ID)
					INNER JOIN 
						TEMPLATE T
							ON (T.TEMPLATE_TYPE_ID=TT.TEMPLATE_TYPE_ID)
					INNER JOIN
						PAGE P
							ON (T.TEMPLATE_ID=P.TEMPLATE_ID)
			WHERE
				P.PAGE_ID IN ('.$this->exported_page_ids.')
			UNION
			SELECT
				TEMPLATE_AREA_ID
			FROM
				PAGE_AREA 
					WHERE PAGE_ID IN ('.$this->exported_page_ids.')
			'
		);
		if ($this->is_terminated()) return;
		
		$template_area_obj->__destruct();
		array_push($this->processed_stages, __METHOD__);
	}

	/**
	* Записывает данные о типах шаблонов
	*/
	
	private function make_template_types() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$template_types_obj = object::factory('TEMPLATE_TYPE');
		
		$this->obj_records_to_xml(
			$template_types_obj, 
			'SELECT 
				DISTINCT T.TEMPLATE_TYPE_ID 
			FROM 
				TEMPLATE T 
					INNER JOIN 
						PAGE P 
					ON (T.TEMPLATE_ID=P.TEMPLATE_ID) 
			WHERE 
				P.PAGE_ID IN ('.$this->exported_page_ids.')'
		);
		if ($this->is_terminated()) return;
		
		$template_types_obj->__destruct();
		array_push($this->processed_stages, __METHOD__);
	}
	

	/**
	* Записывает данные о шаблонах разделов
	*/
	
	private function make_page_templates() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$template_obj = object::factory('TEMPLATE');
		
		$this->obj_records_to_xml(
			$template_obj,
			'SELECT 
				DISTINCT '. 
				$template_obj->primary_key->select_clause().' 
			FROM 
				TEMPLATE 
					INNER JOIN 
						PAGE 
					ON (TEMPLATE.TEMPLATE_ID=PAGE.TEMPLATE_ID) 
			WHERE 
				PAGE_ID IN ('.$this->exported_page_ids.')'
		);
		if ($this->is_terminated()) return;
		
		$template_obj->__destruct();
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Записывает данные о шаблонах модулей
	*/
	
	private function make_module_templates() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$module_templates_obj = object::factory('PARAM_VALUE');
		
		$this->obj_records_to_xml(
			$module_templates_obj, 
			'SELECT 
				DISTINCT PV.PARAM_VALUE_ID, PV.IS_DEFAULT
			 FROM
				PAGE P
					INNER JOIN
						PAGE_AREA PA
					ON (P.PAGE_ID=PA.PAGE_ID)
					
					INNER JOIN
						INF_BLOCK IB
					ON (PA.INF_BLOCK_ID=IB.INF_BLOCK_ID)
					
					INNER JOIN
						MODULE_PARAM MP
					ON (IB.PRG_MODULE_ID=MP.PRG_MODULE_ID)
					
					INNER JOIN 
						PAGE_AREA_PARAM PAP
					ON (
						PAP.PAGE_ID=PA.PAGE_ID 
							AND 
								PAP.VERSION=PA.VERSION 
							AND 
								PAP.TEMPLATE_AREA_ID=PA.TEMPLATE_AREA_ID 
							AND 
								PAP.MODULE_PARAM_ID=MP.MODULE_PARAM_ID
					)

					INNER JOIN
						PARAM_VALUE PV
					ON (PV.MODULE_PARAM_ID=MP.MODULE_PARAM_ID AND PAP.VALUE=PV.PARAM_VALUE_ID)
						
			 WHERE 
			 	MP.PARAM_TYPE=:template_type
			 		AND
			 			P.PAGE_ID IN ('.$this->exported_page_ids.')
			 ORDER BY PV.IS_DEFAULT DESC
			',
			array ('template_type'=>'template')
		);
		if ($this->is_terminated()) return;
		
		$module_templates_obj->__destruct();
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	
	/**
	* Записывает данные о блоках
	*/
	
	private function make_blocks() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$inf_block_obj = object::factory('INF_BLOCK');
		
		$this->obj_records_to_xml(
			$inf_block_obj,
			'SELECT 
				DISTINCT PA.INF_BLOCK_ID
			 FROM 
			 	PAGE P 
			 		INNER JOIN 
			 			PAGE_AREA PA 
			 	ON (P.PAGE_ID=PA.PAGE_ID AND P.VERSION=PA.VERSION) 
			 WHERE 
			 	P.PAGE_ID IN ('.$this->exported_page_ids.')'
		);
		if ($this->is_terminated()) return;
		
		
		$inf_block_obj->__destruct();
		array_push($this->processed_stages, __METHOD__);
	}
	
	
	/**
	* Записывает данные о контенте
	*/
	
	private function make_content() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		$sql = '
						SELECT 
						  DISTINCT
							IB.TE_OBJECT_ID,
							CM.CONTENT_ID,
							P.LANG_ID
						FROM
							PAGE P
								INNER JOIN
									PAGE_AREA PA
								ON (P.PAGE_ID=PA.PAGE_ID)
								
								INNER JOIN
									INF_BLOCK IB
								ON (PA.INF_BLOCK_ID=IB.INF_BLOCK_ID)
								
								INNER JOIN
									CONTENT_MAP CM
								ON (CM.INF_BLOCK_ID = IB.INF_BLOCK_ID)
						WHERE 
							CM.IS_MAIN=1
								AND
									P.PAGE_ID IN ('.$this->exported_page_ids.')
										AND P.LANG_ID=:root_lang_id
					';
		
		while ($records = $this->get_records($sql, array('root_lang_id'=>$this->root_page_info['LANG_ID']))) {
			$this->current_state[md5($sql)]['RECORDS']=$records;
			
			$i =& $this->current_state[md5($sql)]['i'];
			if (!isset($i)) $i=0;
			
			while ($i<sizeof($records)) {
				$element = $records[$i++];
				$obj = object::factory(object_name::$te_object_names[$element['TE_OBJECT_ID']]['SYSTEM_NAME']);
				$element[$obj->autoinc_name]=$element['CONTENT_ID'];
				$this->send_to_xml($obj, $obj->primary_key->get_from_record($element));
				$obj->__destruct();
				//self::check_time();
				//if ($this->is_terminated()) return;
			}
			unset($this->current_state[md5($sql)]['i']);
			unset($this->current_state[md5($sql)]['RECORDS']);
			self::check_time();
			if ($this->is_terminated()) return;
			
		}
		unset ($this->current_state[md5($sql)]);
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	
	/**
	* Записывает данные о разделах
	*/
	
	private function make_pages() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		if ($this->only_counter) return;
		$page_obj = object::factory('PAGE');
		$page_ids = explode(', ', $this->exported_page_ids);
		
		$i =& $this->current_state[md5($sql)]['i'];
		if (!isset($i)) $i=0;
			
		while ($i<sizeof($page_ids)) {
			$this->send_to_xml($page_obj, array('PAGE_ID'=>$page_ids[$i++]));
			$this->counter++;
			
			self::check_time();
			if ($this->is_terminated()) return;
		}
		unset($this->current_state['PAGE']);
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	
	/**
	* Управляет переводом данных, полученных для объекта $obj запросом $sql, в xml-вид и записью их в файлы данных
	* @param object table $obj Объект
	* @param string $sql Запрос
	* @param array $bind_arr привязываемые переменные запроса
	*/
	
	private function obj_records_to_xml ($obj, $sql, $bind_arr=array()) {
		while ($records = $this->get_records($sql, $bind_arr)) {
			$this->current_state[md5($sql)]['RECORDS']=$records;
			
			$i =& $this->current_state[md5($sql)]['i'];
			if (!isset($i)) $i=0;
				
			while ($i<sizeof($records)) {
				$this->send_to_xml($obj, $records[$i++]);
				self::check_time();
				if ($this->is_terminated()) return;
			}
			unset($this->current_state[md5($sql)]['i']);
			unset($this->current_state[md5($sql)]['RECORDS']);
			
			self::check_time();
			if ($this->is_terminated()) return;
		}
		unset($this->current_state[md5($sql)]);
	}
	
	
	/**
	* Возвращает данные, полученные запросом $sql с учетом итерационного контекста. В случае отсутствия данных возвращает false
	* @param string $sql Запрос
	* @param array $bind_arr привязываемые переменные запроса
	* @return array|bool 
	*/

	private function get_records ($sql, $bind_arr=array()) {
		if ($this->only_counter) {
			// если необходимо только подсчитать кол-во записей для обработки, считаем кол-во записей
			$sql='SELECT COUNT(*) AS CNT FROM ('.$sql.') "_COUNT_TBL_"';
			$cnt=db::sql_select($sql, $bind_arr);
			if (sizeof($cnt))
				$this->counter+=$cnt[0]['CNT'];
			return array();
		}
		
		if ($this->current_state[md5($sql)]['RECORDS']) 
			return $this->current_state[md5($sql)]['RECORDS'];
		
		$start = 0;

		if ($this->current_state[md5($sql)]['LAST_RECORD_NUM']) 
			$start = $this->current_state[md5($sql)]['LAST_RECORD_NUM'];

		$records = db::sql_select($sql." LIMIT $start, ".self::RECORDS_ID_AT_ONCE, $bind_arr);
		$this->save_records_state(md5($sql), $records);
		$this->counter+=sizeof($records);
		if (sizeof($records)) return $records;
		return false;
	}
	
	/**
	* Сохраняет текущее положение получения данных для итерационных нужд
	* @param string $unique_name Уникальное имя, под которым данные сохраняются в массиве состояния
	* @param array $records Сохраняемые данные
	*/
	
	private function save_records_state ($unique_name, &$records) {
		if (sizeof($records))
			$this->current_state[$unique_name]['LAST_RECORD_NUM'] += sizeof($records);
		else
			unset($this->current_state[$unique_name]);
	}
	
	/**
	* Записывает данные в файл данных
	* @param object table $obj объект, данные, относящиеся к которому необходимо записать
	* @param array $primary массив первичного ключа данных
	*/
	
	private function send_to_xml ($obj, $primary) {
		if ($this->only_counter) return;
		$xml = $obj->get_export_xml($primary);
		
		if ($this->current_file_name) {
			// если началась новая итерация, то заново открываем файл
			if (!$this->current_file) {
				$old_xml = file_get_contents($this->current_file_name);
				$this->current_file=fopen($this->current_file_name, 'w');
				fwrite($this->current_file, $old_xml);
			}
			
			// проверяем, не пора ли закругляться по причине большого размера файла
			if (ftell($this->current_file)+strlen($xml)>=$this->data['file_size']*1048576) {
				fclose($this->current_file);
				unset($this->current_file);
				unset($this->current_file_name);
			}
		}
		
		if (!$this->current_file_name) {
			// открываем новый файл данных
			$this->current_file_name = $this->current_export_dir.'/'.$this->timename.'_data_'.sprintf('%04d', ++$this->files_count).'.xml';
			$this->current_file = fopen($this->current_file_name, 'w');
			if (!$this->current_file) throw new Exception(metadata::$lang['lang_can_not_create_file'].': '.$filename);
			$encoding = params::$params['encoding']['value'];
			fwrite($this->current_file, "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>\n<DATA>\n</DATA>");
		}
		
		fseek($this->current_file, -7, SEEK_CUR);		
		fwrite($this->current_file, $xml);
		fwrite($this->current_file, '</DATA>');
		
	}
	
	/**
	* Записывает общий информационный файл
	*/
	
	private function make_info () {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		$this->counter++;
		if ($this->only_counter) return;
		
		$main_info = $this->get_main_page_info();
		$info_file = $this->current_export_dir.'/'.$this->timename.'_info.xml';
		
		$encoding = params::$params['encoding']['value'];
		$adm_htdocs_http = params::$params['adm_htdocs_http']['value'];
		$interface_langs_xml = $this->get_interface_langs_xml();
		$modules_xml = $this->get_used_modules_with_params_xml();
		$objects_xml = $this->get_used_objects_with_fields_xml();
		$workflows = $this->get_workflows_xml();
		
		$root_dirs = $this->get_root_dirs();				

		$xml = <<<FINISH_XML
<?xml version="1.0" encoding="{$encoding}"?>
<INFO INF_BLOCK="{$this->data['create_blocks']}" CONTENT="{$this->data['export_content']}" TEMPLATE="{$this->data['create_templates']}" 
 TEMPLATE_TYPE="{$this->data['create_template_types']}" DATETIME="{$this->timename}" LANG_ROOT="{$main_info['LANG_ROOT_DIR']}"
 PARENT_ID="{$this->data['_f_page_id']}" BASE64_CONTENT_ENCODING="{$encoding}" FILES="{$this->files_count}">
<SITE>
  <![CDATA[{$main_info['SITE_TITLE']}]]>
</SITE>
<ADM_HTDOCS_HTTP>{$adm_htdocs_http}</ADM_HTDOCS_HTTP>
{$interface_langs_xml}
{$modules_xml}
{$objects_xml}
{$workflows}
{$root_dirs}
</INFO>
FINISH_XML;
		/* ?> <? */
		$put_bytes=file_put_contents ($info_file, $xml);
		
		if (!$put_bytes)
			throw new Exception(metadata::$lang['lang_es_error_cannot_write_xml_to_file'].': '.$info_file);
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Получает общие данные для записи информационного файла
	* @return array
	*/
	
	private function get_main_page_info () {
		$ret = array();		
		
		$site_obj = object::factory('SITE');
		$ret['SITE_TITLE'] = $site_obj -> get_record_title(array('SITE_ID'=>$this->root_page_info['SITE_ID']));
		$site_obj -> __destruct();
		
		$lang_obj = object::factory('LANG');
		$lang_info = $lang_obj -> get_change_record(array('LANG_ID'=>$this->root_page_info['LANG_ID']));
		$ret['LANG_ROOT_DIR'] = $lang_info['ROOT_DIR'];
		$lang_obj->__destruct();
		
		return $ret;
	}

	/**
	* Возвращает xml для языков, которые на данный момент используются в интерфейсе админки для информационного файла
	* @return string
	*/
	
	private function get_interface_langs_xml() {
		$return = '';

		$lang_obj = object::factory('LANG');
		$lang_obj -> apply_object_parameters( $none = array() );
		$langs_in_admin = lib::array_reindex($lang_obj->get_index_records($this->data, 'm2m', array('by_in_admin'=>1)), 'LANG_ID');
		$lang_obj->__destruct();
		
		foreach ($langs_in_admin as $lang) 
			$return .= "<INTERFACE_LANG SYSTEM_NAME=\"{$lang['ROOT_DIR']}\" />\n";
		return $return;
	}
	
	/**
	* Возвращает xml об используемых модулях для информационного файла
	* @return string
	*/
	
	private function get_used_modules_with_params_xml() {
		
		$sql = 'SELECT 
					DISTINCT IB.PRG_MODULE_ID, PM.SYSTEM_NAME
				FROM 
					PAGE P
						INNER JOIN
							PAGE_AREA PA
								ON (PA.PAGE_ID=P.PAGE_ID)
						INNER JOIN
							INF_BLOCK IB 
								ON (PA.INF_BLOCK_ID=IB.INF_BLOCK_ID)
						INNER JOIN
							PRG_MODULE PM
								ON (PM.PRG_MODULE_ID=IB.PRG_MODULE_ID)
				WHERE 
					P.PAGE_ID IN ('.$this->exported_page_ids.')';
		
		if (!$this->current_state[md5($sql)] || !$this->current_state[md5($sql)]['XML']) 
			$this->current_state[md5($sql)]['XML']='';

		$ret =& $this->current_state[md5($sql)]['XML'];

		while ($records = $this->get_records($sql)) {
			$this->current_state[md5($sql)]['RECORDS']=$records;
			$i =& $this->current_state[md5($sql)]['i'];
			if (!isset($i)) $i=0;

			while ($i<sizeof($records)) {
				$ret .= "<PRG_MODULE SYSTEM_NAME=\"{$records[$i]['SYSTEM_NAME']}\">\n".preg_replace("/^/m", "  ", $this->get_module_params_xml($records[$i++]['PRG_MODULE_ID']))."</PRG_MODULE>\n";
				
				self::check_time();
				if ($this->is_terminated()) return;
			}
			unset($this->current_state[md5($sql)]['i']);
			unset($this->current_state[md5($sql)]['RECORDS']);
			
			//self::check_time();
			//if ($this->is_terminated()) return;
		}
		unset($this->current_state[md5($sql)]);

		return $ret;
	}
	
	/**
	* Возвращает данные об параметрах модуля в формате xml
	* @param int $module_id ID модуля
	* @return string
	*/
	
	private function get_module_params_xml($module_id) {
		$ret='';

		$module_param = db::sql_select('SELECT MODULE_PARAM_ID, SYSTEM_NAME, PARAM_TYPE FROM MODULE_PARAM WHERE PRG_MODULE_ID=:module_id', array('module_id'=>$module_id));

		for ($i=0, $n=sizeof($module_param); $i<$n; $i++) {
			if ($module_param[$i]['PARAM_TYPE']!='template')
				$ret.="<MODULE_PARAM SYSTEM_NAME=\"{$module_param[$i]['SYSTEM_NAME']}\" TYPE=\"{$module_param[$i]['PARAM_TYPE']}\">\n".preg_replace("/^/m", "  ", $this->get_module_param_values_xml($module_param[$i]['MODULE_PARAM_ID']))."</MODULE_PARAM>\n";
		}
		return $ret;		
	}
	
	/**
	* Возвращает значение для параметра модуля в формате xml
	* @param int $module_param_id ID параметра модуля
	* @return string
	*/
	
	private function get_module_param_values_xml($module_param_id) {
		$ret='';
		$module_param = db::sql_select('SELECT * FROM PARAM_VALUE WHERE MODULE_PARAM_ID=:module_param_id', array('module_param_id'=>$module_param_id));

		for ($i=0, $n=sizeof($module_param); $i<$n; $i++) 
			if ($module_param[$i]['VALUE'])
				$ret.="<PARAM_VALUE><![CDATA[{$module_param[$i]['VALUE']}]]></PARAM_VALUE>\n";

		return $ret;		
	}


	/**
	* Возвращает xml об используемых объектах для информационного файла
	* заодно собираем данные об используемых цепочках публикаций для дальнейшего использования
	*
	* @return string
	*/

	private function get_used_objects_with_fields_xml() {
		
		$sql = 'SELECT 
					DISTINCT IB.TE_OBJECT_ID
				FROM 
					PAGE P
						INNER JOIN
							PAGE_AREA PA
								ON (PA.PAGE_ID=P.PAGE_ID)
						INNER JOIN
							INF_BLOCK IB 
								ON (PA.INF_BLOCK_ID=IB.INF_BLOCK_ID)
				WHERE 
					IB.TE_OBJECT_ID>0 
						AND
							P.PAGE_ID IN ('.$this->exported_page_ids.')';
		
		if (!$this->current_state[md5($sql)] || !$this->current_state[md5($sql)]['XML']) 
			$this->current_state[md5($sql)]['XML']='';

		$ret =& $this->current_state[md5($sql)]['XML'];

		while ($records = $this->get_records($sql)) {

			$this->current_state[md5($sql)]['RECORDS']=$records;
			$i =& $this->current_state[md5($sql)]['i'];
			if (!isset($i)) $i=0;

			while ($i<sizeof($records)) {
				$obj_system_name=object_name::$te_object_names[$records[$i]['TE_OBJECT_ID']]['SYSTEM_NAME'];
				$ret .= "<OBJECT SYSTEM_NAME=\"".$obj_system_name."\">\n".preg_replace("/^/m", "  ", $this->get_object_fields_xml($obj_system_name))."</OBJECT>\n";
				
				// добавляем инфу в используемые цепочки публикаций
				$this->add_te_object_to_used_workflows($records[$i]['TE_OBJECT_ID']);
				
				$i++;
				//self::check_time();
				//if ($this->is_terminated()) return;
			}
			unset($this->current_state[md5($sql)]['i']);
			unset($this->current_state[md5($sql)]['RECORDS']);
			
			//self::check_time();
			//if ($this->is_terminated()) return;
			
		}
		unset($this->current_state[md5($sql)]);

		return $ret;
	}
	
	/**
	* Добавление информации в используемые цепочки публикаций
	* @param int $te_object_id ID объекта
	*/
		
	private function add_te_object_to_used_workflows ($te_object_id) {
		$obj_system_name=object_name::$te_object_names[$te_object_id]['SYSTEM_NAME'];
		if (is_array(metadata::$objects[$obj_system_name]['decorators']) && in_array('workflow', metadata::$objects[$obj_system_name]['decorators'])) {
			$workflow_ids=array();
			if (metadata::$objects[$obj_system_name]['workflow_scope']=='block') 
				$workflow_ids = db::sql_select('
					SELECT 
						DISTINCT IB.WF_WORKFLOW_ID 
					FROM 
						INF_BLOCK IB 
							INNER JOIN 
								PAGE_AREA PA
							ON (PA.INF_BLOCK_ID = IB.INF_BLOCK_ID)
								 
					WHERE 
						IB.TE_OBJECT_ID=:te_object_id 
							AND IB.WF_WORKFLOW_ID IS NOT NULL
							AND PA.PAGE_ID IN ('.$this->exported_page_ids.')
					', array('te_object_id'=>$te_object_id));
			else 
				$workflow_ids = db::sql_select('SELECT * FROM TE_OBJECT WHERE TE_OBJECT_ID=:te_object_id AND WF_WORKFLOW_ID IS NOT NULL', array('te_object_id'=>$te_object_id));

				
			if (sizeof($workflow_ids)) 
				for ($i=0, $n=sizeof($workflow_ids); $i<$n; $i++) 
					$this->used_workflows[$workflow_ids[$i]['WF_WORKFLOW_ID']][]=$obj_system_name;
		}
	}
	
	/**
	* Возвращает данные о полях объекта в формате xml
	* @param string $obj_system_name Системное имя объекта
	*/
	
	private function get_object_fields_xml($obj_system_name) {
		$ret = '';
		if (metadata::$objects[$obj_system_name] && is_array($fields = metadata::$objects[$obj_system_name]['fields'])) 
			foreach ($fields as $field_name=>$field) 
				$ret .= "<FIELD FIELD_NAME=\"{$field_name}\" TYPE=\"{$field['type']}\" />\n";
		return $ret;
	}
	
	/**
	* Возвращает HTML со ссылками на созданные файлы данных иинформационный файл
	* @todo: Перенести в Smarty?
	* @return string
	*/
	
	private function get_file_links_html () {
		$http_dir = $this->export_dir_http.'/export_'.$this->timename.'/';
		$filename=$this->timename.'_info.xml';
		$ret = '<div style="padding: 5px 0px 0px 22px;"> '.metadata::$lang['lang_es_export_files'].':<br /><a href="'.$http_dir.$filename.'">'.$filename.'</A>';
		for ($i=1; $i<=$this->files_count; $i++) {
			$filename=$this->timename.'_data_'.sprintf('%04d', $i).'.xml';
			$ret .= '<br /><a href="'.$http_dir.$filename.'">'.$filename.'</A>';
		}
		return $ret.'</div>';
	}
	
	
	/**
	* Возвращает XML с данными цепочек публикаций
	*/
	
	private function get_workflows_xml () {
		if (!$this->data['create_blocks'] || !sizeof($this->used_workflows)) return;
		$ret = "<WORKFLOWS>\n";
		
		$workflow_obj = object::factory('WF_WORKFLOW');
		
		
		foreach ($this->used_workflows as $workflow_id=>$te_objects) {
			$te_obj_xml = '';
			
			for ($i=0, $n=sizeof($te_objects); $i<$n; $i++) 
				$te_obj_xml .= "  <TE_OBJECT name=\"{$te_objects[$i]}\" />\n";
				
			$workflow_xml = preg_replace("/^/m", "  ", $workflow_obj->get_export_xml(array('WF_WORKFLOW_ID'=>$workflow_id)));
			$workflow_xml = preg_replace ('<</RECORD>>', "$te_obj_xml\\0", $workflow_xml);
			
			$ret .= $workflow_xml;
		}
		
		
		$workflow_obj->__destruct();
		
		$ret .= "</WORKFLOWS>\n";
		return $ret;
	}

	/**
	* Возвращает XML с корневыми директориями для последующей проверки их на совпадение с директориями на целевой системе
	*/
	
	private function get_root_dirs () {
		$res = '';
		$root_dirs = array();
		if ($this->data['include_root']) {
			$root_dirs[] = $this->root_page_info['DIR_NAME'];
		}
		else {
			$page_obj = object::factory('PAGE');
			$page_obj -> apply_object_parameters( $none = array( '_f_PARENT_ID' => $this->data['_f_page_id'] ) );
			$root_pages = $page_obj->get_index_records($this->data, 'm2m', array());
			
			foreach ($root_pages as $page) {
				$full_page  = $page_obj->get_change_record($page_obj->primary_key->get_from_record($page));
				if ($full_page['DIR_NAME'])	$root_dirs[] = $full_page['DIR_NAME'];
			}
			$page_obj->__destruct();
		}
		
		if (sizeof($root_dirs)) {
			$res .= '<ROOT_DIRS>';
			foreach ($root_dirs as $dir)
				$res .= "<DIR name=\"{$dir}\" />";
			$res .= '</ROOT_DIRS>';
		}
		return $res;
	}
}
?>