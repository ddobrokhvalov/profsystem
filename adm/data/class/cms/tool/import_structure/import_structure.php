<?PHP

	include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/import_structure/import_check_process.php");
	include_once(params::$params["adm_data_server"]["value"]."class/cms/tool/import_structure/import_process.php");

/**
 * Класс для реализации утилиты Импорт структуры и контента сайта
 *
 * @package RBC_Contents_5_0
 * @subpackage cms
 * @copyright Copyright (c) 2007 RBC SOFT
 * @author Alexandr Vladykin  
 */

	class import_structure extends tool {

		/**
		* Время одной итерации
		*/
		
		const TIME_FOR_RELOAD = 3;
		

		/**
		* Директория с файлами экспорта
		* @var string
		*/

		public  $export_dir = "{adm_htdocs_server}/export/";
		
		/**
		* Константы, которые необходимо преобразовать методом system_params::parse_template_param_for_object. public из-за его ограничений
		* @var array
		*/
		
		public  $constant_list = array('export_dir');

		/**
		* Конструктор, выполняет пребразования констант
		*/

		function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);
			system_params::parse_template_param_for_object ($this, $this->constant_list);
		}

		/**
		* Метод по умолчанию, выводится список разделов и спискок возможных файлов экспорта
		*/
		
		public function action_index () {
			// проверяем на незаконченную операцию импорта, если таковая существует, предлагаем откатить
			$old_import_data = db::sql_select('SELECT IMPORT_LOG_ID FROM IMPORT_LOG LIMIT 1');
			
			if (sizeof($old_import_data)) {
				$msg = metadata::$lang['lang_import_uncompleted_confirm_delete'];
				$tpl = new smarty_ee( metadata::$lang );
				$tpl -> assign('msg', $msg);
				$tpl -> assign
					('buttons', 
					array(
						metadata::$lang['lang_delete'] => $this->url->get_url("", array('add_params'=>array('action'=>'distributed', 'do_op'=>'undo_import'))),
						metadata::$lang['lang_leave_off'] => $this->url->get_url("drop_import_log")
					)
				);
				
				$info = $tpl->fetch($this->tpl_dir."core/object/html_warning.tpl");
			}
			
			$page_obj = self::factory('PAGE');
			$page_obj -> apply_object_parameters( $none = array() );
			
			$headers=$page_obj->get_index_header("tree");
			unset($headers['link_to_block'], $headers['link_to_module'], $headers['link_to_preview']);
			$headers['action'] = array('escape'=>0);
			
			$done_records = $page_obj->get_tree_records(0, array('simple_list'=>1));
			
			for ($i=0, $n=sizeof($done_records); $i<$n; $i++) {
				if (in_array($done_records[$i]['PAGE_TYPE'], array('page', 'folder'))) 
					$done_records[$i]['action'] = '<input type="radio" name="_f_page_id" value="'.$done_records[$i]['PAGE_ID'].'">';
			}
			
			$this->title=metadata::$objects[$this->obj]["title"];
			
			unset(metadata::$objects['PAGE']['fields']['VERSION']['filter_short']);
			$header_tpl = new smarty_ee( metadata::$lang );
			$header_tpl -> assign('export_times', $this->get_export_times());
			$header_tpl -> assign('filter', html_element::html_filter( $page_obj, 1, 1, $this, true ));
			$header = $header_tpl->fetch($this -> tpl_dir . 'cms/import_structure/first_header.tpl');
			
			$fields = $this -> url -> get_hidden();
			$fields .= '<input type="hidden" name="action" value="distributed"> <input type="hidden" name="do_op" value="check_import">';
						
			$tpl = new smarty_ee( metadata::$lang );
			
			$tpl -> assign( 'title', $this -> get_title() );
			$tpl -> assign( 'header',  $info.$header);
			$tpl -> assign( 'table', html_element::html_table( array( 'header' => $headers, 'list' => $done_records, 'counter' => $counter, 'html_hidden' => $fields ), $this -> tpl_dir . 'core/html_element/html_table.tpl', $this -> field ) );
			
			$this -> body = $tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );
			
			$page_obj->__destruct();
			
			return;
		}
		
		/**
		* Запускает операцию удаления данных из журнала импортируемых записей
		*/
		
		public function action_drop_import_log () {
			$this->exec_drop_import_log();
			$this->url->redirect();
		}
		
		/**
		* Удаляет все данные из журнала импортируемых записей
		*/
		
		public function exec_drop_import_log () {
			DB::sql_query('DELETE FROM IMPORT_LOG');
		}
		
		
		/**
		* Возвращает список файлов экспорта в формате для вставки в html_select smarty
		* @return @array
		*/
		
		private function get_export_times() {
			$ret = array();
			$files=filesystem::ls_r($this->export_dir, true, true);
			
			foreach ($files as $dir) {
				if (!$dir['is_dir'] || !preg_match('/^export_/', $dir['pure_name'])) continue;
				$ret[]=$dir['pure_name'];
			}
			
			rsort($ret);
			return $ret;
		}
		
		
		/**
		* Информация об распределенной операции проверки экспортных файлов и совместимости их с текущей системой
		* @param array $status данные распределенной операции
		* @return array
		*/
	
		public function check_import_info (&$status) {
			$status = $_REQUEST;
			$status['check_import_processed'] = true;
			$total=distributed_process::get_count($status, 'import_check_process');
			return array("title"=>metadata::$lang["lang_import_check_import_processing"], "back_url"=>$this->url->get_url(), "total"=>$total, "for_once"=>1, "exception_fatal"=>1);
		}
		
		/**
		* Возвращает информацию закончена ли операция проверки экспортных файлов и совместимости их с текущей системой на данной итерации или нет
		* @return array
		*/
		
		public function check_import_list (&$status, $from, $for_once) {
			return $status['check_import_processed']?array(1):array();
			
		}
		
		/**
		* Запускает итерацию распределенной операции проверки экспортных файлов и совместимости их с текущей системой
		*/
		
		public function check_import_item ($item, &$status) {
			$status['check_import_processed'] = distributed_process::process($status, 'import_check_process', self::TIME_FOR_RELOAD);
			$status['counter']=distributed_process::get_current_counter();
			if ($status['check_import_processed'] && ($status['counter']+1)>=$status['total']) 
				$status['counter']=$status['total']-2;
		}
		
		/**
		* После операции проверки экспортных файлов и совместимости их с текущей системой 
		* выводится второе окно импорта для запуска уже конкретно процесса импорта
		*/
		
		public function check_import_commit (&$status) {
			$status['change_report_on']	= $this->get_second_import_page($status);
		}
		
		/**
		* Возвращает HTML 2-ой страницы импорта
		* @param array $status данные распределенной операции
		* @return string
		*/

		private function get_second_import_page (&$status) {
			$lang_obj = object::factory('LANG');
			$lang_obj -> apply_object_parameters( $none = array() );
			$langs_in_admin = lib::array_reindex($lang_obj->get_index_records($this->data, 'm2m', array('by_in_admin'=>1)), 'ROOT_DIR');
			$lang_obj->__destruct();


			$tpl = new smarty_ee (metadata::$lang);
			$page_obj = object::factory('PAGE');
			$page_name = $page_obj->get_record_title(array('PAGE_ID'=>$status['_f_page_id']));
			$tpl->assign('import_page_name', $page_name);
			$tpl->assign('site_name', $status['info_data']['site_name']);
			$tpl->assign('export_time', lib::unpack_date($status['info_data']['DATETIME'], 'full'));
			$tpl->assign('export_lang', $langs_in_admin[$status['info_data']['LANG_ROOT']]['TITLE']);
			
			$tpl->assign('create_new_blocks_msg', $status['info_data']['INF_BLOCK']?metadata::$lang['lang_yes']:metadata::$lang['lang_no']);
			$tpl->assign('content_elements_msg', $status['info_data']['CONTENT']?metadata::$lang['lang_yes']:metadata::$lang['lang_no']);
			$tpl->assign('create_new_templates_msg', $status['info_data']['TEMPLATE']?metadata::$lang['lang_yes']:metadata::$lang['lang_no']);
			$tpl->assign('create_new_template_types_msg', $status['info_data']['TEMPLATE_TYPE']?metadata::$lang['lang_yes']:metadata::$lang['lang_no']);
			
			$message=$tpl->fetch(params::$params["adm_data_server"]["value"]."tpl/".'cms/import_structure/export_info.tpl');
			
			$fields=array();
			
			
			$cur_lang = $langs_in_admin[params::$params['default_interface_lang']['value']]['LANG_ID'];
			if ($status['info_data']['TEMPLATE']) {
				// Дополняем полученную информации путями к картинкам языковых флажков
				foreach( $langs_in_admin as $lang_root => $lang_item )
					$langs_in_admin[$lang_root]['IMAGE'] = file_exists( params::$params['common_htdocs_server']['value'] . 'adm/img/lang/' . $lang_root . '.gif' ) ? $lang_root : 'default';
				
				$fields['prefix_for_templates']=array (
					"title"=>metadata::$lang['lang_import_prefix_for_templates'],
					"type"=>'text',
					"translate"=>array_values($langs_in_admin),
					"errors" => _nonempty_,
				);
				
				$fields['prefix_for_dirs']=array(
					"title"=>metadata::$lang['lang_import_prefix_for_dirs'],
					"type"=>'text',
					"errors"=>_dirname_|_nonempty_
				);
				
				if ($status['info_data']['TEMPLATE_TYPE']) {
					$fields['prefix_for_templates']["title"].=", ".metadata::$lang['lang_import_prefix_and_template_types'];
					$fields['prefix_for_dirs']["title"].=" ".metadata::$lang['lang_import_and_template_type_system_names'];
				}				
			}					
			elseif ($status['info_data']['INF_BLOCK']) {
				$fields['prefix_for_templates['.$cur_lang.']'] = array (
					"title"=>metadata::$lang['lang_import_prefix_for_blocks'],
					"type"=>'text',
					"errors" => _nonempty_
				);
			}
			
			$fields = array_merge($fields, $this->get_workflows_fields_needed_change($status));
			
			$form_fields = $this->get_form_fields('add', '_f_', array(), '', $fields);
			if ($status['info_data']['INF_BLOCK'] && $status['info_data']['TEMPLATE']) 
				$form_fields['prefix_for_templates['.$cur_lang.']']['title'].=' '.metadata::$lang['lang_import_and_blocks'];


			$fields = html_element::html_fields(
				$form_fields, 
				$this -> tpl_dir . 'core/html_element/html_fields.tpl', 
				$this -> field 
			);
			
			$hidden = $this->url->get_hidden('distributed').'<input type="hidden" name="do_op" value="do_import">';
			$form_name = html_element::get_next_form_name();
			$form = html_element::html_form($fields, $hidden,  $this -> tpl_dir . 'core/html_element/html_form.tpl', true, $message);
			
			$tpl = new smarty_ee( metadata::$lang );
			
			$operations = array(
				array(
					"name"=>"apply", 
					"alt"=>metadata::$lang['lang_import'], 
					"url"=>"javascript:if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { document.forms['{$form_name}'].submit() }"
				),
				array(
					"name"=>"cancel", 
					"alt"=>metadata::$lang['lang_cancel'], 
					"url"=>$status['back_url']
				)				
			);
		
			$tpl -> assign( 'title', metadata::$objects[$this->obj]["title"]);
			$tpl -> assign( 'header', html_element::html_operations( array( 'operations' => $operations, 'label' => 'top' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'footer', html_element::html_operations( array( 'operations' => $operations, 'label' => 'bottom' ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' ) );
			$tpl -> assign( 'form', $form );
			
			// выводим предупреждения
			if (sizeof($status['warnings'])) 
				$warning_text = implode('<br>', $status['warnings']);

			unset($status['warnings']);

			if ($warning_text) {
				$tplw = new smarty_ee( metadata::$lang );
				$tplw -> assign('msg', $warning_text);
				$warning_text = $tplw->fetch($this->tpl_dir."core/object/html_warning.tpl");
			}
		
			return $warning_text.$tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_card.tpl' );					
		}
		
		/**
		* Вычисляет по данным экспорта, есть ли цепочки публикаций, которые использовались в системе при экспорте, но не существуют
		* в целевой системе
		* Для данных цепочек будут выведены поля замены на существующие цепочки
		* Поскольку менять цепочку мы можем только для блоков, то будут выведены цепочки только те, которые используются в объектах с workflow_scope=block
		* @param array $status Разделяемые данные сессий, хранящий информацию из файла импорта, в формате, который предоставляет xml_processor_import_info
		* @return array массив полей для вставки в форму
		*/
		
		private function get_workflows_fields_needed_change(&$status) {
			$ret = array();
			$workflow_obj = object::factory('WF_WORKFLOW');
			if ($status['info_data']['same_server']) {
				if (sizeof($status['info_data']['block_workflows'])) {
					$workflow_ids = array_keys($status['info_data']['block_workflows']);
					for ($i=0, $n=sizeof($workflow_ids); $i<$n; $i++) 
						if ($workflow_obj->primary_key->is_record_exists(array('WF_WORKFLOW_ID'=>$workflow_ids[$i])))
							unset ($status['info_data']['block_workflows'][$workflow_ids[$i]]);
					
				}
			}
			
			if (sizeof($status['info_data']['block_workflows'])) {
				foreach ($status['info_data']['block_workflows'] as $workflow_id => $workflow) {
					$ret['workflow_change['.$workflow_id.']'] 
						= 
							array (
								'title'=>$workflow['TITLE'][$this -> get_interface_lang()], 
								'type'=>'select1',
								"value_list"=>$this->get_workflow_value_list($workflow['TE_OBJECTS']),
								"errors" => _nonempty_
							);
				}
			}
			$workflow_obj->__destruct();
			
			return $ret;
		}
		
		/**
		* Возвращает список цепочек публикаций, которые можно присвоить переданным объектам
		* @param array $te_objects объекты
		* @return array 
		*/
		
		private function get_workflow_value_list($te_objects) {
			static $workflows;
			if (!is_array($workflows)) 
				$workflows 
					= 
						db::sql_select ('
							SELECT 
								W.*, 
								COUNT(S1.WF_WORKFLOW_ID) AS START_WITHOUT_LANG, 
								COUNT(S2.WF_WORKFLOW_ID) AS START_WITH_LANG
							FROM 
								WF_WORKFLOW W 
									LEFT JOIN 
										WF_STATE S1
											INNER JOIN
												WF_EDGE_STATE ES1
											ON (ES1.WF_STATE_ID=S1.WF_STATE_ID AND ES1.EDGE_TYPE="new" AND ES1.LANG_ID=0)
									ON (W.WF_WORKFLOW_ID=S1.WF_WORKFLOW_ID)
									LEFT JOIN 
										WF_STATE S2
											INNER JOIN
												WF_EDGE_STATE ES2
											ON (ES2.WF_STATE_ID=S2.WF_STATE_ID AND ES2.EDGE_TYPE="new" AND ES2.LANG_ID<>0)
									
									ON (W.WF_WORKFLOW_ID=S2.WF_WORKFLOW_ID)

							GROUP BY W.WF_WORKFLOW_ID
						');
			
			
			$res=array();
			
			$workflow_obj = object::factory('WF_WORKFLOW');
			
			foreach ($workflows as $workflow) {
				// проверка на соотв. цепочки
				foreach ($te_objects as $obj) {
					$m_obj_decors = metadata::$objects[$obj]['decorators'];
					if (sizeof($m_obj_decors)) {
						// нельзя к таблице с декоратором "Версии" применить цепочку без версионности
						if (in_array('version', $m_obj_decors) && ($workflow['WORKFLOW_TYPE']!='use_versions')) 
							continue 2;
						
						// Цепочка публикации должна иметь языковой вход, если объект с декоратором "Языки"
						if (in_array('lang', $m_obj_decors) && (!$workflow['START_WITH_LANG'])) 
							continue 2;
							
						// Цепочка публикации должна иметь безязыковой вход, если объект без декоратора "Языки"
						if (!in_array('lang', $m_obj_decors) && (!$workflow['START_WITHOUT_LANG'])) 
							continue 2;
					}
				}
				
				$ret[] 
					= 
						array(
							'title'=>$workflow_obj->get_record_title($workflow_obj->primary_key->get_from_record($workflow)),
							'value'=>$workflow['WF_WORKFLOW_ID']
						);
			}
			$workflow_obj->__destruct();
			return $ret;
		}
		
		
		/**
		* Информация об распределенной операции импорта
		* @param array $status данные распределенной операции
		* @return array
		*/
		
		public function do_import_info (&$status) {
			if (!$_SESSION['distributed']['check_import']) 
				throw new Exception(metadata::$lang['lang_import_files_not_checked']);
			$status = array_merge($_SESSION['distributed']['check_import']['status'], $_REQUEST);
			unset($status['counter'], $status['warnings'], $status['change_report_on'], $status['run_obj']);
			$status['do_import_processed'] = true;
			$total=import_process::get_count($status, 'import_process');
			return array("title"=>metadata::$lang["lang_import_do_import_processing"], "back_url"=>$status['back_url'], "total"=>$total, "complete_message"=>'<div><div class="img"><img src="/common/adm/img/messages/success.gif" alt="" style="margin-right: 2px"/></div>'.metadata::$lang["lang_operation_completed_succesfully"]."</div>", "for_once"=>1, "exception_fatal"=>1);
		}
		
		/**
		* Возвращает информацию закончена ли операция импорта
		*/
		
		public function do_import_list (&$status, $from, $for_once) {
			return $status['do_import_processed']?array(1):array();
		}
		
		/**
		* Запускает итерацию распределенной операции импорта
		*/
		
		public function do_import_item ($item, &$status) {
			$status['do_import_processed'] = distributed_process::process($status, 'import_process', self::TIME_FOR_RELOAD);
			$status['counter']=import_process::get_current_counter();
			if (is_array($status['warnings'])) {
				foreach ($status['warnings'] as $warning) 
					$this->add_distributed_exception('do_import', new Exception($warning));
				unset($status['warnings']);
			}
		}
		
		
		/**
		* Информация об распределенной операции удаления импортируемых данных
		* @param array $status данные распределенной операции
		* @return array
		*/
		
		public function undo_import_info (&$status) {
			$count = db::sql_select('SELECT COUNT(*) AS CNT FROM IMPORT_LOG');
			return array (
				'title' => metadata::$lang['lang_import_undo_import_processing'],
				"back_url" => $this->url->get_url(),
				'total' => $count[0]['CNT'],
				'for_once' => 100,
				'exception_fatal'=>1,
				"complete_message"=>'<div><div class="img"><img src="/common/adm/img/messages/success.gif" alt="" style="margin-right: 2px"/></div>'.metadata::$lang["lang_operation_completed_succesfully"]."</div>"
			);
		}

		/**
		* Формирует список записей, которые нужно распределенно удалить
		*/

		public function undo_import_list(&$status, $counter, $for_once) {
			// не используем counter, потому что в item после каждой успешной вставки удаляем
			return db::sql_select("SELECT * FROM IMPORT_LOG ORDER BY IMPORT_LOG_ID DESC LIMIT $for_once");
		}

		/**
		* Удаление ошибочно импортированных данных
		*/

		public function undo_import_item($item, &$status) {
			$object_name = object_name::$te_object_names[$item['TE_OBJECT_ID']]['SYSTEM_NAME'];
			if (!$object_name) 
				throw new Exception(metadata::$lang['lang_import_not_found_object_with_id'].': '.$item['TE_OBJECT_ID']);
			
			$obj = object::factory($object_name);
			$obj -> import_undo ($item['CONTENT_ID']);
			db::sql_query('DELETE FROM IMPORT_LOG WHERE IMPORT_LOG_ID=:import_log_id', array('import_log_id'=>$item['IMPORT_LOG_ID']));
		}
		
		
		/**
		* Выполняет процесс импорта при помощи API
		* @param string $export_time название экспортной директории (без полного пути к ней)
		* @param int $page_id ID корневого раздела
		* @param string $prefix_for_dirs префикс для названий директорий импортируемых шаблонов
		* @param array $prefixes_for_templates префиксы для названий импортируемых шаблонов, типов шаблонов, областей шаблонов и блоков в формате LANG_ID=>prefix
		* @param string $absolute_path абсолютный путь к дирректории файлов экспорта, если не задан - то берется по умолчанию
		* @return array массив некритических ошибок
		*/
		
		public static function exec_import_structure($export_time, $page_id, $prefix_for_dirs, $prefix_for_templates, $absolute_path='') {
			$data['_f_EXPORT_TIME']=$export_time;
			$data['_f_page_id']=$page_id;
			$data['_f_prefix_for_dirs']=$prefix_for_dirs;
			$data['_f_prefix_for_templates']=$prefix_for_templates;
			$params=array();
			if ($absolute_path) 
				$params['export_dir']=$absolute_path;
			distributed_process::process($data, 'import_check_process', -1, $params);
			distributed_process::process($data, 'import_process', -1, $params);
			return $data['warnings'];
		}
		
		/** 
		* пример вызова exec_import_structure
		*/
		
		//public function action_api_test () {
		private function _action_api_test() {
			$warnings=import_structure::exec_import_structure('export_20071218174207', 223, 'api_import', array('1'=>'russian api ', '6'=>'english api'));
			if (sizeof($warnings))
				foreach ($warnings as $warning) 
					echo $warning."<BR>";
			exit;
		}
	}
?>