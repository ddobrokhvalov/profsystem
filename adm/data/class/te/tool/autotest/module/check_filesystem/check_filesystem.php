<?php
/**
* Класс для автотеста файлов системы
* @package    RBC_Contents_5_0
* @subpackage te
* @copyright  Copyright (c) 2007 RBC SOFT 
* @todo Потом надо будет не забыть дополнять набор путей новыми нужными
*/

class check_filesystem extends autotest_test{
	public $writeable_list;
	
	
	/**
	* Сюда необходимо добавлять список дирректорий и файлов, которые необходимо проверить
	*/
	private function set_writeable_list () {
			$this->writeable_list= array (
				params::$params['common_htdocs_server']['value'].params::$params['upload_dir']['value'],
				params::$params['adm_data_server']['value'].'updates/',
			);
			
			// в случае без CMS не нужно эти вещи проверять
			if (metadata::$objects['PAGE']) {
				$this->writeable_list = array_merge($this->writeable_list, array (
					params::$params['adm_data_server']['value']."page_tpl",
					params::$params['common_data_server']['value']."block_cache",
					params::$params['common_data_server']['value']."module_tpl",
					params::$params['common_data_server']['value']."prebuild",
					params::$params['adm_htdocs_server']['value'].'export/',
				));
			}
			
			$this->writeable_list = array_unique(array_merge($this->writeable_list, $this->get_root_dirs()));
	}
	
	
	/**
	*	Проводим тест
	*/
	public function do_test(){
		
		$this->set_writeable_list();
		
		$files=array();
		foreach($this->writeable_list as $writeable_item){
			$files=filesystem::ls_r($writeable_item);
			$err_c=0;
			foreach($files as $file)
				if(!$file["is_dir"]) {
					if (!is_writable($file["name"])) {
						$err_c++;
					}
			}
			if($err_c>0)
				$this->report[]=array("descr"=>"$writeable_item: ".metadata::$lang['lang_autotest_test_filesystem_files_with_bad_rights_on_writing'].": {$err_c}");
		}
	}
	
	
	/**
	* Возвращает массив с путями к каталогам, входящих в ROOT_DIR
	*/
	
	private function get_root_dirs() {
		$rd_obj=object::factory('ROOT_DIR');
		$ret=array();
		
		$rows = db::sql_select('SELECT ROOT_DIR_TYPE, ROOT_DIR_VALUE FROM ROOT_DIR');
		
		for ($i=0, $n=sizeof($rows); $i<$n; $i++)
			$ret[] = $rd_obj->get_real_path ($rows[$i]['ROOT_DIR_TYPE'], $rows[$i]['ROOT_DIR_VALUE']);
		
		$rd_obj -> __destruct();
		return $ret;
	}
}
?>