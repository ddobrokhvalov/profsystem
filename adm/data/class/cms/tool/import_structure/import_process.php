<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/export_structure/export_process.php");

/**
* Реализация процесса импорта данных с учетом распределенных операций
*
* @package RBC_Contents_5_0
* @subpackage cms
* @copyright Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin 
*/

class import_process extends distributed_process {

	/**
	* Массив названий таблиц, для которых необходимо сохранять карту соответствия ID
	* @var array
	*/
	
	public $remember_table_ids = array('TEMPLATE_AREA', 'TEMPLATE_TYPE', 'TEMPLATE', 'INF_BLOCK', 'PAGE');
	
	/**
	* Директория с файлами экспорта
	* @var string
	*/
	
	public  $export_dir = "{adm_htdocs_server}export/";
		
	/**
	* Константы, которые необходимо преобразовать методом system_params::parse_template_param_for_object. public из-за его ограничений
	* @var array
	*/

	public  $constant_list = array('export_dir');
	
	/**
	* Возвращает число - сколько всего операций необходимо проделать
	*/
	
	public static function get_count(&$data, $obj) {
		return $data['records_count'];
	}
	
	
	/**
	* Конструктор, выполняет пребразования констант
	* @param array $params Дополнительные параметры
	*/
	
	function __construct($params=array()){
		system_params::parse_template_param_for_object ($this, $this->constant_list);
		parent::__construct($params);
	}

	/**
	* Запуск итерации импорта
	* @param array $data Ссылка на данные, сохраняемые между итерациями. 
	*/
	
	protected function run (&$data) {
		$this->data = &$data;
		if (!$this->data['run_obj'] && !$this->only_counter) {
			// Регистрируем процесс импорта в журнале
			log::register('log_records_change', 'import', '', 0,  $this->data['_f_page_id']);
			// Очищаем таблицу IMPORT_LOG
			db::sql_query('TRUNCATE TABLE IMPORT_LOG');
		}
		
		$this->set_langs_data();
		
		$i =& $this->current_state['main']['i'];
		if (!isset($i)) $i=1;
		while ($i<=$data['info_data']['FILES']) {
			$this->process_import_file($this->export_dir."export_{$this->data['timename']}/{$this->data['timename']}_data_".sprintf('%04d', $i).".xml");
			if ($this->is_terminated()) return;
			++$i;
		}
		// если все нормально, то очищаем лог импорта
		if (!sizeof($this->data['warnings']))
			db::sql_query('TRUNCATE TABLE IMPORT_LOG');
	}
	
	/**
	* Запоминает в сохраняемых данных массив языков админки
	*/
	
	private function set_langs_data() {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$lang_obj = object::factory('LANG');
		$lang_obj -> apply_object_parameters( $none = array() );
		$this->data['langs_in_admin'] = lib::array_reindex($lang_obj->get_index_records($this->data, 'm2m', array('by_in_admin'=>1)), 'ROOT_DIR');
		$lang_obj->__destruct();
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Обрабатывает файл данных
	* @param string $filepath путь к файлу
	*/
	
	private function process_import_file ($filepath) {
		$parsed =& $this->current_state[md5($filepath)]['parsed'];
		if (!$parsed) {
			$parser=new ExpatXMLParser();
			$parsed=$parser->Parse($filepath);
			if (!$parsed[0] || ($parsed[0]['tag']!='DATA') || !sizeof($parsed[0]['children']))
				throw new Exception(metadata::$lang['lang_impord_bad_data_file'].': '.$filepath);
			$parsed=$parsed[0]['children'];
			unset($parser);
		}
		
		while (sizeof($parsed)) {
			$p = array_shift($parsed);
			$this->process_element($p);
			self::check_time();
			if ($this->is_terminated()) return;
		}
	}
	
	/**
	* Обрабатывает элемент данных
	* @param array $el элемент данных
	*/
	
	private function process_element($el) {
		if ($el['tag']!='RECORD')
			throw new Exception(metadata::$lang['lang_impord_bad_record_element'].': '.print_r($el, 1));
			
		$object_name=$el['attributes']['TABLE_NAME'];
		$obj = object::factory($object_name);
		try {	
			$id_map=$obj->import_from_xml($el, $this->data);
			if (in_array($object_name, $this->remember_table_ids))  {
				if (!is_array($this->data['id_maps'][$object_name]))
					$this->data['id_maps'][$object_name]=array();
				$this->data['id_maps'][$object_name]+=$id_map;
			}
		}
		catch (Exception $e) {
			$this->add_warning($e, $el);
		}
		$this->counter++;
		$obj->__destruct();
		unset($obj);
	}
	
	/**
	* Запоминает некритическую ошибку
	* @param object Exception $e объект исключения
	* @param array $el обрабатываемый объект
	*/
	
	private function add_warning (&$e, $el) {
		$this->data['warnings'][]=$el['attributes']['TABLE_NAME'].', RECORD_ID='.$el['attributes']['RECORD_ID'].': '.$e->getMessage();
	}
}	
?>