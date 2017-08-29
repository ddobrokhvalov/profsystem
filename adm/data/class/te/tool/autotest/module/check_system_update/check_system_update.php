<?PHP

include_once(params::$params["adm_data_server"]["value"]."class/core/object/table.php");
include_once(params::$params["adm_data_server"]["value"]."class/te/table/system_update/system_update.php");


/**
* Класс для автотеста системы обновлений
* @package		RBC_Contents_5_0
* @subpackage te
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

class check_system_update extends autotest_test {

	/**
	* @var object $su_object Объект SYSTEM_UPDATE
	*/
	
	private $su_object;
	
	/**
	* тест
	*/
	public function do_test(){
		$this->su_object = object::factory('SYSTEM_UPDATE');
		$this->test_update_dir();
		$this->test_writeable_dirs();
	}
	
	/**
	* Проверяет на запись каталог, где хранятся обновления
	*/
	private function test_update_dir() {
		$files=filesystem::ls_r($this->su_object->updates_path);
		foreach($files as $file)
				if ($file['name'] && !is_writable($file["name"]))
					$this->report[]=array("descr"=>"{$file[name]}: ".metadata::$lang['lang_system_update_bad_rights_on_writing']);
	}
	
	/**
	* Проверяет на запись каталоги, файлы в которых обновление может переписать
	*/
	private function test_writeable_dirs() {
		$params=$this->su_object->get_secure_params_for_suexec();
		$test_result = @file_get_contents($this->su_object->get_host().system_update::HTTP_EXEC_SCRIPT_PATH.lib::make_request_uri($params, 'test_writeable_dirs.cgi'));
		if ($test_result != 'TRUE') {
			if (!$test_result) {
				$this->report[]=array("descr"=>metadata::$lang['lang_system_update_can_not_run_cgi_script'].': '.$this->su_object->get_host().system_update::HTTP_EXEC_SCRIPT_PATH.lib::make_request_uri($params, 'test_writeable_dirs.cgi').'<br>'.metadata::$lang['lang_system_update_can_not_run_cgi_script_descr']);
			}
			else {
				$res=explode('<BR>', $test_result);
				foreach ($res as $line) 
					$this->report[]=array("descr"=>"{$line} ");
			}
		}
	}
} 
?>