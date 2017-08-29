<?PHP

/**
* Класс проверки языковых констант в PHP-кодах и Smarty шаблонах
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/


class lang_constants_error_finder_php_smarty_sources extends lang_constants_error_finder {
	
		/**
		* Заголовок отчета
		*/
		
		protected $caption = 'php&smarty';	

		/**
		* @var string $lang_dir Путь к дирректории с ini файлами языковых констант
		*/
		
		public $lang_dir = '{adm_data_server}lang';
				

		/**
		* @var string $lang_files_extention Расширение файлов языковых констант
		*/
		
		protected $lang_files_extension = 'ini';

		/**
		* @var array $source_dirs Указывается список директорий с исходниками, которые необходимо 
		* проверить на наличие неизвестных системе констант . 
		*/	
		
		public $source_dirs = array (
				'{adm_data_server}class/',	
				'{common_data_server}lib/'
		);
		
		
		/**
		* @var array $source_dirs_exclude_patterns Указывается список регулярных выражений, 
		* в случае если полный путь к файлу совпадет с каким-либо из них, то такой файл
		* не будет рассматриваться
		*/
		
		public $source_dirs_exclude_patterns = array (
				'|smarty/templates_c|',
				'|\.gif|',
				'|awstats/distrib|',
				'|app/tool/source_meter|',
				'|app/tool/export_lang|'
		);
		
		
		/**
		* @var array $source_dirs_exclude_russian_patterns Указывает список паттернов,
		* для исключения проверки на русский язык
		*/
		
		public $source_dirs_exclude_russian_patterns = array (
				'|awstats\.php|',
				'|lib/upload\.php|',
				'|check_lang_constants/lang_constants_error_finder\.php|'
		);
		

		/**
		* @var array $template_dirs Указывается список директорий со смарти-шаблонами, которые 
		* необходимо проверить на наличие неизвестных системе констант . 
		*/	
		
		public $template_dirs = array (
				'{adm_data_server}tpl/',
		);
		
		
		/**
		* @var array $template_dirs_exclude_patterns Указывается список регулярных выражений, 
		* в случае если полный путь к файлу совпадет с каким-либо из них, то такой файл
		* не будет рассматриваться
		*/

		public $template_dirs_exclude_patterns = array(
			'|lib/bench|'
		);
		
		/**
		* @var array $def_dirs Список дирректорий в которых есть объявления подобные def-никам
		*/
		
		public $def_dirs = array (
			'{adm_data_server}def/',
			'{adm_data_server}class/te/table/log_show/',
			'{adm_data_server}class/te/tool/autotest/autotest.xml',
			'{adm_data_server}prebuild/prebuilder.php'
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
			'def_dirs'
		);
		
		/**
		* @var array $file_contents В данный массив будут собираться элементы из языковых файлов
		*      В нем 2 ключа - names, в элементы которого собираются по имени
		*	   и values - по значению
		*/
		
		protected $file_contents = array();
		
		/**
		* @var array $file_constant_list В данный массив будут собираться элементы из языковых файлов
		*      В массиве ключами являются названия файлов, в которых нашли данный элемент
		*/
		
		protected $file_constant_list = array();
		
		/******************************************************************************************************/
		
		/**
		* Проверка языковых файлов
		*/

		protected function check_langs() {
			$dirs=filesystem::ls_r($this->lang_dir,1,1);
			
			foreach ($dirs as $dir) {
				if (is_dir($dir['name']) && !$this->db_langs[$dir['pure_name']]) {
					// если в базе данных такого языка нет, то записываем ошибку
					$this->struct_report['db_problem'][]=metadata::$lang['lang_autotest_test_lang_constants_no_lang_in_database'].'"'.$dir['pure_name'].'" ("'.$dir['name'].'")';
					continue;
				}
				
				$this->file_contents=array();
				$this->process_lang_dir($dir);
				$this->find_dublicates($this->file_contents);
				
				// запоминаем что каталог есть в файловой системе. Если какого-то каталога не будет, а он есть в БД, то запишем ошибку
				$this->db_langs[$dir['pure_name']]['in_fs']=1;
			}
			
			foreach ($this->db_langs as $root_dir=>$db_lang)
				if (!$db_lang['in_fs']) {
					$this->struct_report['db_problem'][]=metadata::$lang['lang_autotest_test_lang_constants_no_lang_in_fs'].'"'.$root_dir.'"';
					unset($this->db_langs[$root_dir]);
				}
		}

		/**	
		* Обработка каталога с языковыми файлами
		* @param array $dir Каталог в формате, возвращаемом filesystem::ls_r
		*/
		
		protected function process_lang_dir($dir) {
			$files=filesystem::ls_r($dir['name']);
			foreach ($files as $file) {
				// обрабатываем только файлы с нужным расширением
				if ($file['is_dir'] || !preg_match('/\.'.$this->lang_files_extension.'$/', $file['pure_name']))
					continue;
					
				$this->process_lang_file($dir['pure_name'], $file);
			}
		}

		/**
		* Обработка языкового файла
		* @param string $lang	язык
		* @param array $file	файл, в формате, возвращаемом filesystem::ls_r
		*/
		
		protected function process_lang_file($lang, $file) {
			$handle=fopen($file['name'], 'r');
			$i=0;
			if ($handle) {
				while (!feof($handle)) {
					++$i;
					// построчно
					$line=trim(fgets($handle));

					// если пустая строка, или комментарий - пропускаем
					if (!$line || ($line[0]==';'))
						continue;
					
					// проверяем формат строки на правильность
					if (!preg_match('/^(lang_[A-Za-z0-9_]+)\s*=\s*((([\'"])([^\4]*)\4)|([^"\';]+))/', $line, $m)) {
						$this->struct_report[$lang]['file_format_problem'][] = $this->get_file_format_problem_info($lang, $file, $i, $line);
						continue;
					}
					
					
					// запоминаем все элементы строки в массиве item
					$item['string']=$m[0];
					$item['name']=$m[1];
					$item['value']=$m[5]?$m[5]:$m[6];
					
					$item['lineNum']=$i;
					
					// в случае если uncheckable проверять не надо
					if (preg_match('/^lang_uncheckable_/', $item['name'])) continue;
					
					// проверяем итем					
					$this->process_lang_item($lang, $file, $item);
				}
			}
		}
		/**
		* Обработка строки языкового файла
		* @param string $lang	язык
		* @param array $file	файл, в формате, возвращаемом filesystem::ls_r
		* @param $item - массив с ключами string, name, value, lineNum
		*/
		
		protected function process_lang_item ($lang, $file, $item) {
			$element = array('lang'=>$lang, 'file'=>$file, 'item'=>$item);
			
			 // запоминаем элемент в массиве file_contents
			 $this->file_contents['names'][$item['name']][]=&$element;
			 $this->file_contents['values'][$item['value']][]=&$element;
			 
			 // вставка для того, чтобы позже сравнить наличие констант в файлах (на 2-ом этапе)
			 if (preg_match('|'.preg_quote($this->lang_dir).'/'.$lang.'/(.+)|', $file['name'], $m)) {
			 		$this->file_constant_list[$m[1]][$item['name']][$lang]=&$element;
			 }
			 
			 // список всех встретившихся констант
			 $this->lang_constant_list[$item['name']]['items'][]=&$element;
			 $this->lang_constant_list[$item['name']]['count'] = 0;
			 
		}
		
		/**
		* Получаем строку для вывода информации для строки языкового файла
		* @param array $cont массив, определяющий строку языкового файла
		*/
		
		protected function get_item_info_for_print ($cont) {
			return metadata::$lang['lang_file'].': "'.$cont['file']['name'].'"; '.metadata::$lang['lang_string'].': '.$cont['item']['lineNum'].'; '.metadata::$lang['lang_contents'].': '.htmlspecialchars($cont['item']['string']);
		}

		/**
		* Получаем строку для вывода информации о проблемах с форматом строки языкового файла
		* @param string $lang	язык
		* @param array $file	файл, в формате, возвращаемом filesystem::ls_r
		* @param string $lineNo - Номер строки
		* @param string $string - Строка
		*/
		protected function get_file_format_problem_info($lang, $file, $lineNo, $string) {
			return metadata::$lang['lang_autotest_test_lang_constants_file_format_error'].'<br>'.metadata::$lang['lang_file'].': "'.$file['name'].'"; '.metadata::$lang['lang_string'].': '.$lineNo.'; '.metadata::$lang['lang_contents'].': "'.htmlspecialchars($string).'"';
		}
		
		/******************************************************************************************************/
		
		/**
		* Сравнение языковых файлов разных языков
		*/
		
		protected function compare_langs() {
			foreach ($this->file_constant_list as $file=>$constant_list) {
				$this->find_absent_constants($constant_list, $file);
			}
		}
		
		/******************************************************************************************************/
		
		/**
		* Проверка в исходных текстах языковых констант на соответствие с заданными
		*/	
		
		protected function check_sources() {
			$this->check_scripts();
			$this->check_templates();
			$this->check_defs();
			
			$this->set_sources_problems_to_report();
		}
		
		protected function set_sources_problems_to_report () {
			foreach ($this->lang_constant_list as $name=>$constant) 
				if (!$constant['count'] && !preg_match('/^lang_uncheckable_/', $name)) {
					
					$filenames = array();
					
					for ($i=0, $n=sizeof($constant['items']); $i<$n; $i++) 
						$filenames[]=$constant['items'][$i]['file']['name'];
						
					$filenames = '"'.implode('", "', $filenames).'"';
					
					$this->struct_report['constant_problem'][] = metadata::$lang['lang_autotest_test_lang_constants_constant'].' "'.$name.'", '.metadata::$lang['lang_autotest_test_lang_constants_declared_in_files'].' '.$filenames.' '.metadata::$lang['lang_autotest_test_lang_constants_not_used_in_any_source_file'].'.';
				}
		}

		/**
		* Проверка исходных кодов на наличие констант
		*/
		
		private function check_scripts() {
			$this->bulk_apply_function_to_file (
					$this->source_dirs, 
					'find_lang_constants', 
					array (
						'pattern' => '/metadata\s*::\s*\$lang\s*\[\s*([\'"]?)(lang_[A-Za-z0-9_]+)\1\s*]/',
						'match_no' => 2,
						// попытка сделать шаблон одновременно для def-а и всех остальных файлов провалился :(
						//'pattern' => '/((?<=\[)\s*["\']?|["\'])(lang_[A-Za-z0-9_]+)\1/',
						//'match_no' => 2,
						'constant_list' => &$this->lang_constant_list,
						'report' => &$this->struct_report['constant_problem']
					),
					$this->source_dirs_exclude_patterns		
			);
		}

		/**
		* Проверка шаблонов смарти на наличие констант
		*/
		
		private function check_templates() {
			$this->bulk_apply_function_to_file (
					$this->template_dirs, 
					'find_lang_constants', 
					array (
						'pattern' => '/\{\$(lang_[A-Za-z0-9_]+)\}/',
						'match_no' => 1,
						'constant_list' => &$this->lang_constant_list,
						'report' => &$this->struct_report['constant_problem']
					),
					$this->source_dirs_exclude_patterns		
			);
		}
		
		
		/**
		* Проверка деф-файлов на наличие констант
		*/
		private function check_defs() {
			$this->bulk_apply_function_to_file (
					$this->def_dirs, 
					'find_lang_constants', 
					array (
						'pattern' => '/([\'"])(lang_[A-Za-z0-9_]+)\1/',
						'match_no' => 2,
						'constant_list' => &$this->lang_constant_list,
						'report' => &$this->struct_report['constant_problem']
					),
					$this->source_dirs_exclude_patterns		
			);
		}
		
		/******************************************************************************************************/
		
		/**
		* Проверка в исходных текстах на русский язык
		*/	

		protected function check_russian() {
			$this->check_russian_in_scripts();
			$this->check_russian_in_templates();
		}
		
		/**
		* Проверяет на русские символы скрипты
		*/
		
		private function check_russian_in_scripts() {
			$this->bulk_apply_function_to_file(
				$this->source_dirs, 
				'check_russian_symbols', 
				array(
					'clear_comments'=>array('php', 'html'),
					'report' => &$this->struct_report['russian_source']
				), 
				array_merge($this->source_dirs_exclude_patterns, $this->source_dirs_exclude_russian_patterns)
			);
		}
		
		/**
		* Проверяет на русские символы smarty шаблоны
		*/
		
		private function check_russian_in_templates() {
			$this->bulk_apply_function_to_file(
				$this->template_dirs, 
				'check_russian_symbols', 
				array(
					'clear_comments'=>array('smarty', 'html'),
					'report' => &$this->struct_report['russian_source']
				), 
				$this->template_dirs_exclude_patterns
			);	
		}
}

?>