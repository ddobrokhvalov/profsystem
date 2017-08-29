<?PHP

include_once 'lang_constants_error_finder_php_smarty_sources.php';

/**
* Класс проверки языковых констант в JavaScript-кодах
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

class lang_constants_error_finder_javascript_sources extends lang_constants_error_finder_php_smarty_sources {

		/**
		* Заголовок отчета
		*/

		protected $caption = 'javascript';
		
		/**
		* @var string $lang_dir Путь к дирректории с js файлами языковых констант
		*/
		
		public $lang_dir = '{common_htdocs_server}js/lang';
		
		/**
		* @var string $lang_files_extention Расширение файлов языковых констант
		*/
		
		protected $lang_files_extension = 'js';

		/**
		* @var array $js_source_dirs Указывается список директорий с исходниками, которые необходимо 
		* проверить на наличие неизвестных системе констант Javascript. 
		*/
			
		public $js_source_dirs = array (
			'{common_htdocs_server}js/',
			'{common_htdocs_server}adm/js/'
		);
		
		/**
		* @var array $js_source_dirs_exclude_patterns Указывается список регулярных выражений, 
		* в случае если полный путь к файлу совпадет с каким-либо из них, то такой файл
		* не будет рассматриваться
		*/
		
		public $js_source_dirs_exclude_patterns = array (
			'|js/lang|'
		);
		
		/**
		* @var array $contant_list Список названий полей, которые необходимо преобразовать вставив полные пути
		*/

		public $constant_list = array (
			'lang_dir', 
			'source_dirs', 
			'source_dirs_exclude_patterns', 
			'template_dirs', 
			'template_dirs_exclude_patterns',
			'js_source_dirs', 
			'js_source_dirs_exclude_patterns', 
		);



		/******************************************************************************************************/

		/**
		* Обработка языкового файла Javascript
		* @param string $lang	Язык
		* @param array $file	файл, в формате, возвращаемом filesystem::ls_r
		*/
		
		protected function process_lang_file($lang, $file) {
			$file_contents = file_get_contents($file['name']);

			preg_match_all('/Dictionary\.aWords\s*\[\s*([\'"])(lang_.+)\1\s*\]\s*=\s*([\'"])([^\3]*[^\\\]?)\3/msSU', $file_contents, $m, PREG_SET_ORDER);
			
			for ($i=0, $n=sizeof($m); $i<$n; $i++) {
				$item['string']=$m[$i][0];
				$item['name']=$m[$i][2];
				$item['value']=$m[$i][4];
				
				// в случае если uncheckable проверять не надо
				if (preg_match('/^lang_uncheckable_/', $item['name'])) continue;
				
				$this->process_lang_item($lang, $file, $item);
			}
		}
		
		/******************************************************************************************************/
				
		/**
		* Проверка языковых констант в исходном коде javascript-ов
		*/
		
		protected function check_sources() {
			$this->check_js();
			$this->check_php_scripts();
			$this->check_templates();
			$this->set_sources_problems_to_report();
		}
		
		/******************************************************************************************************/
				
		/**
		* Проверка javascript-файлов
		*/
		
		private function check_js () {
			$this->bulk_apply_function_to_file (
					$this->js_source_dirs,
					'find_lang_constants',
					array (
						'pattern' => '/([\'"])(lang_[^\1]+?)\1/',
						'match_no' => 2,
						'constant_list' => &$this->lang_constant_list,
						'report' => &$this->struct_report['constant_problem']
					),
					$this->js_source_dirs_exclude_patterns
			);
		}
		
		/**
		* Проверка php-скриптов
		*/
		
		private function check_php_scripts () {
			$this->bulk_apply_function_to_file (
				$this->source_dirs,
				'find_lang_constants',
				array (
					'pattern' => '/Dictionary\s*\.\s*translate\s*\(\s*([\'"])(lang_[^\1]+?)\1\s*\)/',
					'match_no' => 2,
					'constant_list' => &$this->lang_constant_list,
					'report' => &$this->struct_report['constant_problem']
				),
				$this->source_dirs_exclude_patterns
			);
		}
		
		/**
		* Проверка щаблонов
		*/
		
		private function check_templates () {
			//return;
			$this->bulk_apply_function_to_file (
				$this->template_dirs,
				'find_lang_constants',
				array (
					'pattern' => '/([\'"])(lang_[^\1]+?)\1/',
					'match_no' => 2,
					'constant_list' => &$this->lang_constant_list,
					'report' => &$this->struct_report['constant_problem']
				),
				$this->template_dirs_exclude_patterns
			);
		}
		

		/******************************************************************************************************/
		/**
		* Проверяет на русские символы javascript-ы
		*/

		protected function check_russian() {
			$this->bulk_apply_function_to_file(
				$this->js_source_dirs, 
				'check_russian_symbols', 
				array(
					'clear_comments'=>array('javascript', 'html'),
					'report' => &$this->struct_report['russian_source']
				), 
				$this->js_source_dirs_exclude_patterns
			);
		}
}

?>