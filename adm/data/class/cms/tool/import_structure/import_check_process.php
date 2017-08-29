<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/export_structure/distributed_process.php");

/**
* Проверка и получение необходимой информации из файлов экспорта с учетом распределенных операций
*
* @package		RBC_Contents_5_0
* @subpackage cms
* @copyright	Copyright (c) 2007 RBC SOFT
* @author Alexandr Vladykin 
*/

class import_check_process extends distributed_process {
	
	/**
	* Данные полученные из информационного файла экспорта
	* @var array
	*/
	
	private $info_data;
	
	/**
	* Некритические ошибки в процессе проверки файлов экспорта
	* @var array
	*/
	
	private $warnings = array();
	
	/**
	* Хранятся данные о корневой странице, под которую производится импорт в формате, возвращаемом get_change_record
	* @var array
	*/	
	
	private $root_page_info;
	
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
	* Конструктор, выполняет пребразования констант
	* @param array $params Дополнительные параметры
	*/
	
	function __construct($params=array()) {
		system_params::parse_template_param_for_object ($this, $this->constant_list);
		parent::__construct($params);
	}
	
	/**
	* Запуск итерации процесса
	* @param array $data Ссылка на данные, сохраняемые между итерациями. 
	*/
	
	protected function run (&$data) {
		$this->data = &$data;
		$this->check_info();
		
		// узнали из инфо-файла кол-во файлов, подсчет ведем по ним
		if ($this->only_counter) {
			// счетчик основан на кол-ве файлов
			$this->counter=$this->info_data['FILES'];
			return;
		}		
		
		self::check_time();
		if ($this->is_terminated()) return;
		$this->check_root();
		$this->check_data();
		if ($this->is_terminated()) return;
		
		$this->data['info_data']=$this->info_data;
		$this->data['root_page_info']=$this->root_page_info;
		$this->data['warnings']=$this->warnings;
	}
	
	/**
	* Проверка информационного файла и сохранение всех необходимых данных
	*/
	
	private function check_info () {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		$this->parse_info_file();

		$this->check_interface_languages();
		
		$this->check_files_eligibility();
		
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Проводит процесс парсинга информационного файла и сохраняет данные в параметре info_data
	* Парсинг и проверка проводится при помощи объекта xml_processor_import_info
	*/
	
	private function parse_info_file () {
		if (in_array(__METHOD__, $this->processed_stages)) return;		
		
		include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/import_structure/xml_processor_import_info.php");

		if (!$this->data['_f_EXPORT_TIME'] || !preg_match('/export_(.+)/', $this->data['_f_EXPORT_TIME'], $m))
			throw new Exception (metadata::$lang['lang_import_no_export_time']);

		$this->data['timename']=$m[1];	
				
		$info_file_path = $this->export_dir."export_{$this->data['timename']}/{$this->data['timename']}_info.xml";
		if (!file_exists($info_file_path)) 
			throw new Exception (metadata::$lang['lang_can_not_find_file'].': '. $info_file_path);
			
		$xml_info_obj = new xml_processor_import_info($info_file_path, $this->data);
		$xml_info_obj->process_xml();
			
		$this->info_data = $xml_info_obj->get_info();
		array_push($this->processed_stages, __METHOD__);		
	}
	
	/**
	* Проверяет все ли необходимые языки есть на целевой системе
	*/
	
	private function check_interface_languages() {
		if (in_array(__METHOD__, $this->processed_stages)) return;		
		
		// проверяем интерфейсные языки, на целевой системе их не должно быть больше чем на исходной
		$lang_obj = object::factory('LANG');
		$lang_obj -> apply_object_parameters( $none = array() );
		$langs_in_admin = $lang_obj->get_index_records($this->data, 'm2m', array('by_in_admin'=>1));
		$lang_obj->__destruct();
		
		foreach ($langs_in_admin as $lang) 
			if (is_array($this->info_data['interface_langs']))
				if (!in_array($lang['ROOT_DIR'], $this->info_data['interface_langs']))
					throw new Exception (metadata::$lang['lang_import_found_over_lang'].' '.$lang['TITLE']);
		
		array_push($this->processed_stages, __METHOD__);		
	}
	
	/**
	* Проверяет все ли файлы данных существуют, и нет ли лишних в соответствии с данными информационного файла
	*/
	
	private function check_files_eligibility() {
		if (in_array(__METHOD__, $this->processed_stages)) return;		

		for ($i=1; $i<=$this->info_data['FILES']; $i++) {
			$data_file_path=$this->export_dir."export_{$this->data['timename']}/{$this->data['timename']}_data_".sprintf('%04d', $i).".xml";
			
			if (!file_exists($data_file_path)) 
				throw new Exception(metadata::$lang['lang_can_not_find_file'].': '. $data_file_path);
		}
		
		$data_file_path=$this->export_dir."export_{$this->data['timename']}/{$this->data['timename']}_data_".sprintf('%04d', $i).".xml";
			
		if (file_exists($data_file_path)) 
			throw new Exception(metadata::$lang['lang_import_found_over_file'].': '. $data_file_path);

		array_push($this->processed_stages, __METHOD__);		
	}
	
	/**
	* Проверяет был ли задан корневой раздел на целевой системе, под который необходимо импортировать
	* А также соответствует ли он по языкам импортируемым данным
	*/
	
	private function check_root () {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		
		if (!$this->data['_f_page_id'])
			throw new Exception(metadata::$lang['lang_import_no_page']);
		
		$page_obj = object::factory('PAGE');
		$this->root_page_info = $page_obj->get_change_record(array('PAGE_ID'=>$this->data['_f_page_id']));
		if (!$this->root_page_info || !in_array($this->root_page_info['PAGE_TYPE'], array('page', 'folder')))
			throw new Exception(metadata::$lang['lang_import_bad_page_id']);
		$page_obj->__destruct();
		
		$lang = $this->get_lang_root($this->root_page_info['LANG_ID']);
		if ($lang!=$this->info_data['LANG_ROOT'])  {
			// если язык не совпадает - то если не экспортировались шаблоны - то прекращаем - иначе предупреждение
			if ($this->info_data['TEMPLATE']) {
				$this->warnings[]=metadata::$lang['lang_import_different_languages_page_and_export'];
			}
			else {
				throw new Exception(metadata::$lang['lang_import_different_languages_page_and_export']);
			}
		}
		array_push($this->processed_stages, __METHOD__);
	}
	
	/**
	* Возвращает по ID языка его ROOT_DIR
	* @todo Вынести выше, или превыбирать, часто появляется данная необходимость
	* @param int $lang_id ID языка
	* @return string ROOT_DIR языка
	*/
	
	private function get_lang_root($lang_id) {
		$lang_obj = object::factory('LANG');
		$lang_info = $lang_obj->get_change_record(array('LANG_ID'=>$lang_id));
		$lang_obj->__destruct();
		
		return $lang_info['ROOT_DIR'];
	}


	/**
	* Проверяет данные, которые необходимо проимпортировать при помощи объекта xml_processor_import_check_data
	*/
	
	private function check_data () {
		if (in_array(__METHOD__, $this->processed_stages)) return;
		include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/import_structure/xml_processor_import_check_data.php");
		
		$i =& $this->current_state['check_data_files_i'];
		if (!isset($i)) $i=1;
			
		while ($i<=$this->info_data['FILES']) {
			$data_file_path=$this->export_dir."export_{$this->data['timename']}/{$this->data['timename']}_data_".sprintf('%04d', $i).".xml";
			$tmp_file = tempnam(params::$params['adm_htdocs_server']['value'].'temp/test_import/', $i.'_');
				
			$xml_data_obj = new xml_processor_import_check_data($data_file_path, $this->info_data);
			$xml_data_obj->process_xml();
			
			$this->data['records_count']+=$xml_data_obj->records_count;
			
			$this->counter = ++$i;
					
			self::check_time();
			if ($this->is_terminated()) return;
		}

		array_push($this->processed_stages, __METHOD__);
	}
}	
?>