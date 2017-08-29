<?PHP

/**
* Корневой класс проверки языковых констант
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*
*/


class lang_constants_error_finder {
	
	/**
	* Заголовок отчета
	*/
	
	protected $caption;
	
	/**
	* @var array $db_langs Языки из БД
	*/
	
	protected $db_langs=array();

	
	/**
	* @var array $struct_report Вспомогательный массив репорт, который используется для 
	* формирования результирующего отчета. 
	* Формат: $this->struct_report['report_type'][0] = $message
	*/
	
	protected $struct_report = array();
	
	/**
	* @var array $report Результирующий массив репорт
	*/
	
	protected $report = array();
	
	/**
	* @var array $contant_list Список названий полей, которые необходимо преобразовать вставив полные пути
	*/
	
	public $constant_list = array();
	

	/**
	* @var array $lang_constant_list В данный массив будут собираться элементы из языковых файлов
	*      В ключе массиве собираются все названия констант, существующих в системе
	* @todo Не используется в автотестах, связанных с БД. Нужно вынести в отдельный класс, который будет между данным и файловыми
	*/

	protected $lang_constant_list = array();
	
	/**
	* @var array $dublicate_values Сохраняются дубликаты по отношению к каждому языкуъ
	* Сразу выводить нельзя, потому что нужно будет проверить с другими языками
	* @todo Не используется в автотестах, связанных с БД. Нужно вынести в отдельный класс, который будет между данным и файловыми
	*/
	
	protected $dublicate_values = array (); 
	

	/**
	* Конструктор
	*/
	
	protected function __construct () {
		system_params::parse_template_param_for_object ($this, $this->constant_list);
		$this->set_db_langs();
	}
	
	/**
	* Возвращает объект для поиска ошибок в php и smarty
	*/
	
	public static function make_php_smarty_error_finder () {
		include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_lang_constants/lang_constants_error_finder_php_smarty_sources.php";
		return new lang_constants_error_finder_php_smarty_sources();
	}
	
	/**
	* Возвращает объект для поиска ошибок в javascript
	*/
	
	public static function make_javascript_error_finder () {
		include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_lang_constants/lang_constants_error_finder_javascript_sources.php";
		return new lang_constants_error_finder_javascript_sources();
	}
	
	/**
	* Возвращает объект для поиска ошибок в модулях, в которых языковые константы берутся из таблицы SYSTEM_WORD
	*/
		
	public static function make_sw_sources_error_finder () {
		include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_lang_constants/lang_constants_error_finder_sw_sources.php";
		return new lang_constants_error_finder_sw_sources();
	}
	

	/**
	* Возвращает объект для поиска ошибок в модулях, в которых языковые константы берутся из таблицы SYSTEM_WORD
	*/
		
	public static function make_tt_sources_error_finder () {
		include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_lang_constants/lang_constants_error_finder_tt_sources.php";
		return new lang_constants_error_finder_tt_sources();
	}

	
	/******************************************************************************************************/
	
	/**
	* Запускает процесс автотеста
	*/
	
	public function process () {
		$this->check_langs();
		$this->check_dublicates();
		$this->compare_langs();
		$this->check_sources();
		$this->check_russian();
		return $this->create_report();
	}
	
	/******************************************************************************************************/

	/**
	* Проверка корректности языковых записей
	*/
	
	protected function check_langs() {}
	
	/**
	* Поиск дублей записей и значений, и занесение их в массив struct_report
	* @param array $contents	собранная информация из файлов в виде массива [names] или [values] [item_name][]=item
	*/
	
	protected function find_dublicates ($contents) {
		if (isset($contents['names']))
			$this->find_item_dublicates($contents['names']);
		
		if (isset($contents['values']))	
			$this->find_value_dublicates($contents['values']);
	}
	
	/**
	* Поиск дублей записей и занесение их в массив struct_report
	* @param array $contents	собранная информация из файлов в виде массива [item_name][]=item
	*/
	
	private function find_item_dublicates($contents) {
		$this->set_dublicate_to_report(
			$contents, 
			$this->struct_report[$this->get_lang_from_contents($contents)]['item_dublicate'], 
			metadata::$lang['lang_autotest_test_lang_constants_record_double']
		);
	}
	

	/**
	* Получение языка из contents
	* @param array $contents	в данной структуре язык должен лежать в [item_name][0][lang]
	* @return string
	*/
	
	protected final function get_lang_from_contents($contents) {
		$res = current($contents);
		return $res[0]['lang'];
	}
	
	/**
	* Поиск дублей значений и занесение их в массив struct_report
	* @param array $contents	собранная информация из файлов в виде массива [item_name][]=item
	*/
	
	private function find_value_dublicates($contents) {
		$d_counter = 0;
		foreach ($contents as $value=>$items) 
			if (sizeof($items)>1) {
				foreach ($items as &$item)
					$this->dublicate_values[$item['lang']][$d_counter][] =& $item;
				++$d_counter;
			}
		
		/*$this->set_dublicate_to_report(
			$contents, 
			&$this->struct_report[$this->get_lang_from_contents($contents)]['value_dublicate'], 
			metadata::$lang['lang_autotest_test_lang_constants_value_double']
		);*/
	}
	

	/**
	* Поиск и занесение дубликатов в массив report
	* @param array $dublicates	собранная информация из файлов в виде массива [item_name][]=item
	* @param array $report	массив отчета report
	* @param string $msg	Сообщение, выводимое перед каждой записью в репорт
	*/

	protected final function set_dublicate_to_report ($dublicates, &$report, $msg) {
		foreach ($dublicates as $name=>$items) {
			if (sizeof($items)>1) {
				$report_message=$msg.' "'.$name.'": ';
				for ($i=1, $n=sizeof($items); $i<=$n; $i++) {
					$report_message .= '<br>'.$i.'. '.$this->get_item_info_for_print($items[$i-1]).'; ';
				}
				$report[] = $report_message;
			}
		}
	}
	

	
	/**
	* Получаем строку для вывода информации для строки языкового файла
	* @param array $cont	данные, формируемые дочерними классами
	*/
	protected function get_item_info_for_print ($cont) {}


	/**
	* Проверка на дубликаты с учетом данных других языков
	* В dublicate_values записаны данные о дублирующих значениях по отношению к одному языку
	* В других языках для этого значения могут быть разные значения, в этом случае ошибки быть не должно
	* Поскольку может быть такая ситуация:
	* Язык 1			Язык 2			Язык 3
	* lang_1 = "1"		lang_1 = "2"	lang_1 = "4"
	* lang_2 = "1"		lang_2 = "3"	lang_2 = "4"
	* lang_3 = "1"		lang_3 = "2"	lang_3 = "5"
	* lang_4 = "1"		lang_4 = "3"	lang_4 = "5"
	*
	* Нужно это учесть. В приведенной ситуации ошибок быть не должно
	*/
	
	protected function check_dublicates () {
		if (sizeof($this->dublicate_values)) {
			foreach ($this->dublicate_values as $lang=>$double_list) {
				foreach ($double_list as $double) {
					$n = sizeof($double);
					// здесь будем хранить полученные реальные дубли
					$doubles = array();
					// а здесь названия языковых констант для полученных реальных дублей. Нужна для простого исключения из цикла уже пройденных значений
					$double_names = array();
					
					// Сравнения проходят по парам, так что тут 2 цикла, которые позволяют сравнить по 1 разу все дубликаты, и проверить их в других языках
					for ($i=0; $i<$n-1; $i++) {
						// если уже в дублях данное значение, то нам больше рассматривать не нужно
						if (in_array($double[$i]['item']['name'], $double_names)) continue;
						
						// запоминаем дубли относительно одного значения, их может быть одновременно несколько, например в ситуации:
						// lang_1, lang_2 - не дубли
						// lang_1, lang_3 - дубли
						// lang_1, lang_4 - не дубли
						// lang_2, lang_4 - дубли
						// lang_3, lang_5 - дубли
						// в результате из такого примера должно получиться 2 дубля - это lang_1, lang_3, lang_5 и lang_2, lang_4
						$o_doubles = array();
						
						// сохраняем данные о первом значении, переиндексируем по языку, чтобы легко было добираться до значений на разных языках для данной языковой константы
						$const_1 = lib::array_reindex($this->lang_constant_list[$double[$i]['item']['name']]['items'], 'lang');

						// 2-ой цикл
						for ($j=$i+1; $j<$n; $j++) {
							
							// если уже в дублях данное значение, то нам больше рассматривать не нужно
							if (in_array($double[$j]['item']['name'], $double_names)) continue;
							
							// сохраняем данные о втором значении, переиндексируем по языку, чтобы легко было добираться до значений на разных языках для данной языковой константы
							$const_2 = lib::array_reindex($this->lang_constant_list[$double[$j]['item']['name']]['items'], 'lang');
							
							// изначально считаем что дубликат - это ошибка, потом сравним с другими языками, если там разные значения, то сбросим этот флаг
							$not_error=false;

							// проверяем значения по всем языкам
							foreach ($this->db_langs as $root_dir=>$db_lang) {
								// если это тот же язык, то не нужно
								if ($double[$i]['lang']==$root_dir) continue;
								
								// если такого значения нет, то пропускаем
								if (!$const_1[$root_dir] || !$const_2[$root_dir]) continue;
								
								// если данные не совпадают, то снимаем флаг ошибки
								if ($const_1[$root_dir]['item']['value']!=$const_2[$root_dir]['item']['value'])
									$not_error=true;
							}
							
							// если это ошибка, то записываем в дубли
							if (!$not_error) 
								$o_doubles[] = $const_2[$double[$i]['lang']];
						}
						
						// если были дубли, то добавляем в сведения о первом элементе, и запоминаем в общих массивах дублей
						if (sizeof($o_doubles)) {
							array_unshift($o_doubles, $const_1[$double[$i]['lang']]);
							$doubles[] = $o_doubles;

							foreach ($o_doubles as $o_d)
								$double_names[] = $o_d['item']['name'];
						}
					}
					
					// записываем дубли в репорт		
					if (sizeof ($doubles)) {
						foreach ($doubles as $dbl) {
							// делаем совместимость для метода set_dublicate_to_report
							$dbl_for_report = array($dbl[0]['item']['value'] => $dbl);
							$this->set_dublicate_to_report ($dbl_for_report, $this->struct_report[$this->get_lang_from_contents($dbl_for_report)]['value_dublicate'], metadata::$lang['lang_autotest_test_lang_constants_value_double']);
						}
					}
				}
			}
		}
	}

	
	/******************************************************************************************************/

	/**
	* Проверка соответствия языковых записей для разных языков
	*/	
	
	protected function compare_langs() {}
	
	/**
	* Занесение в struct_report информации об отсутствии в каком-либо из языков констант
	* @param array $constant_list - список констант для всех языков в формате [constant_name][lang]=item
	* @param string $file - файл, в котором обнаружен данный список
	*/
	
	protected function find_absent_constants($constant_list, $file='') {
		foreach ($constant_list as $name=>$lang_element) {
			if ($not_present=array_diff(array_keys($this->db_langs), array_keys($lang_element))) {
				
				// получаем список языков, для которых этот параметр определен
				$language_list = array_diff_key($this->db_langs, array_flip($not_present));
				array_walk($language_list, array($this, 'callback_set_title'));
				
				foreach ($not_present as $lang) 
					$this->struct_report[$lang]['constant_absent'][]=($file?'"'.$file.'": ':'').metadata::$lang['lang_autotest_test_lang_constants_no_value_for_constant'].' "'.htmlspecialchars($name).'", '.metadata::$lang['lang_autotest_test_lang_constants_which_declared_in_languages'].': "'.implode('", "', $language_list).'"';
			}
		}
	}


	/******************************************************************************************************/

	/**
	* Проверка в исходных текстах языковых констант на соответствие с заданными
	*/	
	
	protected function check_sources() {}
	
	/**
	* Проверка в исходных текстах на русский язык
	*/	
	
	/******************************************************************************************************/

	protected function check_russian() {}

	/******************************************************************************************************/

	/**
	* Запускает процесс формирования репорта по результатам автотеста
	*/

	protected function create_report () {
		$this->set_db_problems_report();
		$this->set_languages_report();
		$this->set_constant_problem_report();
		$this->set_russian_in_sources_problem_report();
		
		if (sizeof($this->report))
			$this->report[0]['caption']="<h3 class='autotest'>".mb_strtoupper($this->caption, params::$params["encoding"]["value"]).'</h3> '.$this->report[0]['caption'];
		return $this->report;
	}
	
	
	/**
	* Переносим все данные из struct_report в report по общим проблемам
	*/
		
	protected function set_db_problems_report () {
		if (sizeof($this->struct_report['db_problem']))
			$this->simple_send_to_report($this->struct_report['db_problem'], metadata::$lang['lang_autotest_test_lang_constants_system_problems']);
	}

	
	/*
	* Переносим все данные из struct_report в report по проблемам языковых файлов
	*/
	
   
	protected function set_languages_report() {
		foreach (array_keys($this->db_langs) as $lang)
			$this->set_language_report($lang);
	}
	
	/**
	* Переносим все данные из struct_report в report по проблемам языкового файла
	* @param string $lang	язык
	*/
   
	private function set_language_report($lang) {
		if (sizeof($this->struct_report[$lang])) {
			$report=array_merge(
				(array) $this->struct_report[$lang]['file_format_problem'],
				(array) $this->struct_report[$lang]['item_dublicate'],
				(array) $this->struct_report[$lang]['value_dublicate'],
				(array) $this->struct_report[$lang]['constant_absent']
			);
			if (sizeof ($report)) {
				$this->simple_send_to_report($report, $this->db_langs[$lang]['TITLE']);
			}
		}
	}
	
	/**
	* Переносим все данные из struct_report в report по проблемам отсутствия константы в языковых файлах
	*/
    
	protected function set_constant_problem_report() {
		if (sizeof($this->struct_report['constant_problem']))
			$this->simple_send_to_report($this->struct_report['constant_problem'], metadata::$lang['lang_autotest_test_lang_constants_source_problems']);
	}
	
	/*
	* Переносим все данные из struct_report в report по проблемам русского языка в исходниках
	*/
    	
	protected function set_russian_in_sources_problem_report() {
		if (sizeof($this->struct_report['russian_source'])) {
			$this->simple_send_to_report($this->struct_report['russian_source'], metadata::$lang['lang_autotest_test_russian_in_source_problems']);
		}
	}

	/******************************************************************************************************/
	
	/**
	* Формируем массив доступных языков из БД
	*/
		
	protected function set_db_langs() {
		if (!sizeof($this->db_langs))
		{
			$lang_obj = object::factory( 'LANG' );
			list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
				 $lang_obj -> ext_field_selection( 'TITLE', 1 );
			$lang_obj -> __destruct();
			
			$this->db_langs=lib::array_reindex(db::replace_field(db::sql_select('SELECT LANG.*, ' . $dec_field . ' AS "_TITLE" FROM LANG ' . $dec_join[0] . ' WHERE IN_ADMIN=:in_admin ORDER BY PRIORITY DESC', array('in_admin'=>1) + $dec_binds), 'TITLE', '_TITLE'), 'ROOT_DIR');
		}
	}

	/**
	* Функция применения функции ко всем файлам в указанных директориях
	* @param array $dirs Директории, которые необходимо обойти
	* @param string $func_name Имя функции, которое вызывается с параметрами $file и $func_add_arg
	* @param mixed $func_add_arg Дополнительный аргумент, передаваемый функции
	* @param array $exclude_patterns Массив шаблонов, в случае совпадения которых с полным путем к файлу, ф-ия применена не будет
	*/
	
	protected function bulk_apply_function_to_file ($dirs, $func_name, $func_add_arg='', $exclude_patterns=array()) {
		foreach ($dirs as $dir) {
			$files=filesystem::ls_r($dir);
			foreach ($files as $file) 
				if (!$file['is_dir'] && !$this->patterns_accept($file['name'], $exclude_patterns)) 
					$this->$func_name($file, $func_add_arg);
		}
	}

	/**
	* Проверяет, совпадает ли $str с каким-либо шаблоном из массива $patterns_arr
	* @param string $str Строка
	* @param array $patterns_arr Массив шаблонов
	*/
	
	protected function patterns_accept($str, $patterns_arr) {
		if (!sizeof($patterns_arr)) return false;
		
		foreach ($patterns_arr as $pattern) {
			if (preg_match($pattern, $str)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	* Проверка файла на наличие языковых констант, проверка есть ли константа в прилагаемом списке, запись в отчет
	* @params array $file Файл в формате функции {@link filesystem::ls}
	* @params array $params Параметры, должны быть установлены:
	* string pattern - шаблон для выявления констант
	* int match_no - номер константы по шаблону
	* array constant_list - список констант, с которым идет проверка
	* array report - отчет, куда необходимо занести ошибку выявления константы
	*/
	
	private function find_lang_constants ($file, $params) {
		$file_contents = file_get_contents($file['name']);
		if (!$params['absent_message_suf']) {
			$params['absent_message_suf'] = metadata::$lang['lang_autotest_test_lang_constants_not_declared_in_any_lang_file'];
		}
		
		if (preg_match_all($params['pattern'], $file_contents, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match)
				if (!isset($params['constant_list'][$match[$params['match_no']]]) && !preg_match('/^lang_uncheckable_/', $match[$params['match_no']]))
					$params['report'][]=metadata::$lang['lang_autotest_test_lang_constants_constant'].' "'.$match[$params['match_no']].'", '.metadata::$lang['lang_autotest_test_lang_constants_declared_in_file'].' "'.$file['name'].'" '.$params['absent_message_suf'].'.';
				else
					++$params['constant_list'][$match[$params['match_no']]]['count'];
		}
	}
	
	/**
	* Функция проверки файла на наличие русских символов
	* @param array $file Файл в формате ф-ии {@link filesystem::ls}
	* @param array $params Параметры
	* mixed clear_comments - список языков, комментарии которых могут быть в файле
	* array report - отчет, куда необходимо записать результат тестирования
	*/
	
	protected function check_russian_symbols ($file, $params=array()) {
		$file_contents = file_get_contents($file['name']);
		if ($params['clear_comments']) {
			if (is_array($params['clear_comments'])) {
				foreach ($params['clear_comments'] as $lang)
					$file_contents=$this->clear_comments($file_contents, $lang);
			}
			else {
				$file_contents=$this->clear_comments($file_contents, $params['clear_comments']);
			}
		}
		
		$pattern = '/(([А-я]|Ё|ё)+(\s|-|_)*)+/' . ( params::$params['encoding']['value'] == 'utf-8' ? 'u' : '' );
		if (preg_match_all($pattern, $file_contents, $matches)) {
			$params['report'][]=metadata::$lang['lang_autotest_test_lang_constants_russian_text_in_file'].': '.$file['name'].' - "'.implode('", "', array_map('htmlspecialchars', $matches[0])).'"';
		}
	}
	
	/**
	* Функция очистки комментариев
	* @param string $data Текст
	* @param string $lang Язык - html, smarty, php, javascript
	*/
	
	protected function clear_comments($data, $lang) {
		switch ($lang) {
			case 'html' :	
				$data = preg_replace ('/<!--(.*?)-->/s', '', $data); 
				break;
			case 'smarty' : 
				$data = preg_replace ('/{\*(.*?)\*}/s', '', $data); 
				break;
			case 'php' : 
				$data = preg_replace ('|/\*(.*?)\*/|s', '', $data);
				$data = preg_replace ('/#.*$/m', '', $data);
			case 'javascript' : 
				$data = preg_replace ('|/\*(.*?)\*/|s', '', $data);
				$data = preg_replace ('|//.*$|m', '', $data);	
		 
		}
		return $data;
	}

	
	/**
	* Запись данных в отчет
	* @param array $data Массив событий для занесения в отчет (может быть в формате report)
	* @param string $caption Название, если указано начинается новая таблица в отчете
	*/
		
	protected function simple_send_to_report($data, $caption=null) {
		if (sizeof($data)) {
			if (isset($caption)) {
				$report_record['is_new_table'] = 1;
				$report_record['caption'] = $caption;
			}
			
			foreach ($data as $descr) {
				$report_record['status'] = 1;
				if (is_array($descr)) {
					$report_record = array_merge($report_record, $descr);
				}
				else {
					$report_record['descr'] = $descr;
				}
				
				$this->report[] = $report_record;
				unset ($report_record);
			}
		}
	}
	
	/**
	* Функция обратного вызова, заменяет хеш его данными по ключу TITLE
	* @param array $v	Элемент массива
	*/
	
	protected function callback_set_title (&$v) {
		$v=htmlspecialchars($v['TITLE']);
	}
	
}
?>