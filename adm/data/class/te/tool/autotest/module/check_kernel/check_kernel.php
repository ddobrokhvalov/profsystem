<?PHP

/**
* Класс для автотеста файлов ядра - проверка, изменялись ли на основе контрольных сумм
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

class check_kernel extends autotest_test {
	
	/**
	* @param string $checksum_file_path	Путь к файлу со списком файлов ядра и их контрольными суммами
	*/
	
	public $checksum_file_path = '{adm_data_server}prebuild/core_checksum/core_checksums.php';
	
	/**
	* @param array $constant_list	Список параметров, заданных через шаблон, в которые необходимо данные из параметров
	*/
	
	public $constant_list = array('checksum_file_path');
	
	/**
	* Конструктор
	*/
	
	protected function __construct ($element) {
		parent::__construct($element);
		system_params::parse_template_param_for_object ($this, $this->constant_list);
	}

	/**
	* Тест
	*/
	
	public function do_test(){
		$this->check_core_checksums();
	}	
	
	/**
	* Проверка контрольных сумм
	*/
	
	public function check_core_checksums () {
		$su_obj = object::factory('SYSTEM_UPDATE');
		if (!file_exists($this->checksum_file_path) || (!include($this->checksum_file_path)) || !$checksums) {
			$this->report[]=array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_kernel_checksum_file_not_available'].': '.$this->checksum_file_path);
			return false;
		}
		foreach ($checksums as $item) {
			$file_path = system_params::parse_template_param($item['path']);
			
			if (!file_exists($file_path))
				$this->report[]=array("status"=>1, "descr"=>metadata::$lang['lang_system_update_file_not_exists'].': '.$file_path);
			elseif ($item['checksum'] != $su_obj->get_checksum($file_path))
				$this->report[]=array("status"=>1, "descr"=>metadata::$lang['lang_system_update_checksum_invalid'].': '.$file_path);
		}
		$su_obj -> __destruct();
	} 
}

?>