<?PHP
/**
* Класс проверки языковых констант в модулях при помощи таблицы SYSTEM_WORD
* @package		RBC_Contents_5_0
* @subpackage cms
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/


class lang_constants_error_finder_sw_sources extends lang_constants_error_finder {
		
		/**
		* Заголовок отчета
		*/

		protected $caption = 'system word';

		/**
		* @var array $source_dirs_list Указывается список директорий с исходниками, которые необходимо 
		* проверить на наличие неизвестных системе констант SYSTEM_WORD. 
		*/
		
		public $source_dirs_list = array (
			'{adm_data_server}page_tpl/',
			'{common_data_server}/',
		);
		
		/**
		* @var array $source_dirs_exclude_patterns Указывается список регулярных выражений, 
		* в случае если полный путь к файлу совпадет с каким-либо из них, то такой файл
		* не будет рассматриваться
		*/
		
		public $source_dirs_exclude_patterns = array(
			'|block_cache|',
			'|config|',
			'|prebuild|',
			'|templates_c|'
		);
		
		/**
		* @var array $contant_list Список названий полей, которые необходимо преобразовать вставив полные пути
		*/

		public $constant_list = array (
			'source_dirs_list', 
			'source_dirs_exclude_patterns', 
		);
		
		
		/**
		* Проверка таблицы SYSTEM_WORD на дубликаты названий и значений, а также правильность названия констант
		*/
		
		protected function check_langs() {
			
			// формируем CASE дабы получить системное имя языка уже после получения данных из БД
			$case_lang = 'CASE LANG_ID';
			
			foreach ($this->db_langs as $db_lang) 
				$case_lang .= ' WHEN '.$db_lang['LANG_ID'].' THEN \''.$db_lang['ROOT_DIR'].'\'';

			$case_lang .= ' END AS lang';
			
			
			// формируем lang_constant_list в том же формате, что и остальные автотесты
			$this->lang_constant_list = lib::array_reindex(db::sql_select('SELECT '.$case_lang.', SYSTEM_NAME AS name, VALUE as value FROM SYSTEM_WORD'), 'name', 'lang');
			foreach ($this->lang_constant_list as $key=>$val) {
				$this->lang_constant_list[$key]['items']=$val;
				foreach ($val as $k=>$v) {
					$this->lang_constant_list[$key]['items'][$k]['item']=$v;
				}
			}
				
			$bad_syntax=db::sql_select('SELECT DISTINCT SYSTEM_NAME FROM SYSTEM_WORD WHERE SYSTEM_NAME NOT LIKE \'sysw_%\' ORDER BY SYSTEM_NAME');

			// выводим плохие названия
			if (sizeof($bad_syntax))
				foreach ($bad_syntax as $item)
					$this->struct_report['db_problem'][] = metadata::$lang['lang_autotest_test_lang_constants_sw_bad_name'].': "'.htmlspecialchars($item['SYSTEM_NAME']).'"';
			
			
			foreach ($this->db_langs as $lang=>$db_lang) {
				$lang_id=$db_lang['LANG_ID'];
				
				switch(params::$params['db_type']['value']){//Эмулируем функционал GROUP_CONCAT из mysql для других вендоров БД
					case 'mssql':
						$query = "
							SELECT SYSTEM_NAME
								, (
									SELECT LEFT(xxx, LEN(xxx) - 1)
									FROM (
										SELECT cast(SYSTEM_WORD_ID as VARCHAR(max)) + ','
										FROM SYSTEM_WORD
										WHERE LANG_ID={$lang_id}
											AND SYSTEM_NAME = t.SYSTEM_NAME
										GROUP BY SYSTEM_WORD_ID
										FOR XML PATH ('')
									) D(xxx)
								) AS IDS
							FROM SYSTEM_WORD as t
							WHERE LANG_ID={$lang_id}
							GROUP BY SYSTEM_NAME
							HAVING COUNT(SYSTEM_NAME) > 1
						";
					break;

					default:
						$query = "
							SELECT SYSTEM_NAME
								, GROUP_CONCAT(SYSTEM_WORD_ID) AS IDS
							FROM SYSTEM_WORD
							WHERE LANG_ID={$lang_id}
							GROUP BY SYSTEM_NAME
							HAVING COUNT(*)>1
						";
					break;
				}
				
				$double_names=db::sql_select($query);
				
				if (sizeof($double_names)) 
					 foreach ($double_names as $item) 
						$this->struct_report[$lang]['item_dublicate'][]=metadata::$lang['lang_autotest_test_lang_constants_record_double'].' : "'.htmlspecialchars($item['SYSTEM_NAME']).'"';
					 
				// плохие значения подводим под общий стандарт (dublicate_values, lang_constant_list), чтобы можно использовать стандартный механизм
				// @see lang_constant_error_finder::check_dublicates
				
				switch(params::$params['db_type']['value']){//Эмулируем функционал GROUP_CONCAT из mysql для других вендоров БД
					case 'mssql':
						$query = "
							SELECT VALUE
								, (
									SELECT LEFT(xxx, LEN(xxx) - 1)
									FROM (
										SELECT cast(SYSTEM_NAME as VARCHAR(max)) + ','
										FROM SYSTEM_WORD
										WHERE LANG_ID={$lang_id}
											AND VALUE = t.VALUE
										GROUP BY SYSTEM_NAME
										FOR XML PATH ('')
									) D(xxx)
								) AS SNS
							FROM SYSTEM_WORD AS t
							WHERE LANG_ID={$lang_id}
							GROUP BY VALUE
							HAVING COUNT(VALUE)>1
						";
					break;

					default:
						$query = "
							SELECT VALUE
								, GROUP_CONCAT(SYSTEM_NAME) AS SNS
							FROM SYSTEM_WORD
							WHERE LANG_ID={$lang_id}
							GROUP BY VALUE
							HAVING COUNT(*)>1
						";
					break;
				}
				
				$double_values=db::sql_select($query);
				
				if (sizeof($double_values)) {
					 $d_counter = 0;			
					 foreach ($double_values as $item) {
						$constant_list=explode(',',$item['SNS']);
						$j=0;
						for ($i=0, $n=sizeof($constant_list); $i<$n; $i++) {
							$this->dublicate_values[$lang][$d_counter][$j]['lang'] = $lang;
							$this->dublicate_values[$lang][$d_counter][$j]['item'] = $this->lang_constant_list[$constant_list[$i]]['items'][$lang]; //$sw_data[$constant_list[$i]];
							++$j;
						}
						++$d_counter;
					 }
				}
			}
		}
		
		/**
		* Получаем строку для вывода информации для строки, используется при дублировании значений
		* @see lang_constant_error_finder::get_item_info_for_print
		*/
		
		protected function get_item_info_for_print ($cont) {
			return $cont['name'];
		}
			
		
		/**
		* Сравнение констант по разным языкам для таблицы SYSTEM_WORD
		*/
		
		protected function compare_langs() {
			$db_ids=array();
			foreach ($this->db_langs as $db_lang) {
				$db_ids[$db_lang['LANG_ID']]=$db_lang['TITLE'];
			}
			
			// проверка на наличие всех языковых версий для констант
				switch(params::$params['db_type']['value']){//Эмулируем функционал GROUP_CONCAT из mysql для других вендоров БД
					case 'mssql':
						$query = "
							SELECT SYSTEM_NAME
								,(
									SELECT LEFT(xxx, LEN(xxx) - 1)
									FROM (
										SELECT cast(LANG_ID as VARCHAR(max)) + ','
										FROM SYSTEM_WORD
										WHERE SYSTEM_NAME = t.SYSTEM_NAME
										GROUP BY LANG_ID
										FOR XML PATH ('')
									) D(xxx)
								)
							FROM SYSTEM_WORD AS t
							GROUP BY SYSTEM_NAME
						";
					break;

					default:
						$query = '
							SELECT SYSTEM_NAME
								, GROUP_CONCAT(DISTINCT LANG_ID) AS LANG_IDS
							FROM SYSTEM_WORD
							GROUP BY SYSTEM_NAME
						';
					break;
				}
				
			$langs=db::sql_select ($query);
			for ($i=0, $n=sizeof($langs); $i++; $i<$n) {
				$lang_ids=explode(',', $langs[$i]['LANG_IDS']);
				if (sizeof($no_langs=array_diff(array_keys($db_ids), $lang_ids))) {
					// выводим языки, в которых данное название не отображено
					array_walk($no_langs, array($this, 'callback_set_title'));
					$this->struct_report['constant_absent'][]=metadata::$lang['lang_autotest_test_lang_constants_no_value_for_constant'].' "'.htmlspecialchars($langs[$i]['SYSTEM_NAME']).'": '.metadata::$lang['lang_autotest_test_lang_constants_in_languages'].'"'.implode('", "', $no_langs).'"';
				}
			}			
		}
		
		/**
		* Проверка констант SYSTEM_WORD в исходном коде шаблонов
		*/
		
		protected function check_sources() {
			$lang_constant_list = lib::array_reindex(db::sql_select('SELECT * FROM SYSTEM_WORD'), 'SYSTEM_NAME');
			
			$this->bulk_apply_function_to_file (
				$this->source_dirs_list,
				'find_lang_constants',
				array (
						'pattern' => '/(\{\$|[\'"])(sysw_\w+)/',
						'match_no' => 2,
						'constant_list' => &$lang_constant_list,
						'report' => &$this->struct_report['constant_problem'],
						'absent_message_suf' => metadata::$lang['lang_autotest_test_lang_constants_not_declared_in_sw']
				),
				$this->source_dirs_exclude_patterns
			);
			
			foreach ($lang_constant_list as $name => $constant) {
				if (!$constant['count']) 
					$this->struct_report['constant_problem'][] = metadata::$lang['lang_autotest_test_lang_constants_constant'].' "'.htmlspecialchars($name).'" '.metadata::$lang['lang_autotest_test_lang_constants_not_used_in_any_source_file'].'.';
			}
		}
		
		/**
		* Для шаблонов проверка на русский не нужна
		*/
		
		protected function check_russian() {
			return false;
		}
}


?>