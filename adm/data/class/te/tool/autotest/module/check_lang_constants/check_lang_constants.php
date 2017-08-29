<?PHP

	include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_lang_constants/lang_constants_error_finder.php";

/**
* Класс автотеста - проверка языковых констант
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

	class check_lang_constants extends autotest_test {
		
		/**
		* тест
		*/
		
		public function do_test () {
			$this->find_php_and_templates_error();
			$this->find_js_error();	
			$this->find_sw_error();
			$this->find_tt_error();
		}
		
		
		/**
		* Ошибки в PHP
		*/
		
		private function find_php_and_templates_error() {
			$error_finder=lang_constants_error_finder::make_php_smarty_error_finder();
			$this->report=array_merge($this->report, $error_finder->process());
		}
		
		/**
		* Ошибки в JS
		*/

		private function find_js_error () {
			$error_finder = lang_constants_error_finder::make_javascript_error_finder();
			$this->report=array_merge($this->report, $error_finder->process());
		}
		
		/**
		* Ошибки в таблице SYSTEM_WORD
		*/
		
		private function find_sw_error () {
			// Без CMS SYSTEM_WORD нет
			if (!params::$params['install_cms']['value']) return;
			
			$error_finder = lang_constants_error_finder::make_sw_sources_error_finder();
			$this->report=array_merge($this->report, $error_finder->process());
		}


		/**
		* Ошибки в таблице TABLE_TRANSLATE
		*/
		
		private function find_tt_error () {
			$error_finder = lang_constants_error_finder::make_tt_sources_error_finder();
			$this->report = array_merge($this->report, $error_finder->process());
		}
		
		/**
		* Исправление ошибок. Пока что можем сделать только для TT
		*/
		public function fix_action () {
			$error_finder = lang_constants_error_finder::make_tt_sources_error_finder();
			return $error_finder -> fix_action();
		}
	}
?>
