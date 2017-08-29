<?PHP

/**
* Класс проверки цепочки публикаций
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/

	class test_workflow {
		
		/**
		* ID проверяемой цепочки публикаций
		* @var int
		*/
		
		private $workflow_id;
		
		/**
		* Данные цепочки публикаций из БД
		* @var array
		*/
		
		private $workflow;
		
		/**
		* Название цепочки публикации
		*/
		
		private $workflow_name;
		
		
		/**
		* Массив состояний цепочки публикаций
		* @var object
		*/
		
		private $states;
		
		/**
		* Объект состояние цепочки публикаций
		* @var object
		*/
		
		private $state_obj;
		
		/**
		* Объект состояние резолюции
		* @var object
		*/
		
		private $resolution_obj;
		
		/**
		* Сообщения об ошибках
		* @var array
		*/
		
		private $report = array();
		
		/**
		* Конструктор
		* @param int $workflow_id ID цепочки публикаций
		*/
		
		public function __construct($workflow_id) {
			$this->workflow_id=$workflow_id;
		}
		
		/**
		* Деструктор
		*/
		
		public function __destruct() {
			if ($this->state_obj)
				$this->state_obj->__destruct();
			if ($this->resolution_obj) 
				$this->resolution_obj->__destruct();
		}
		
		
		/**
		* Возвращает объект состояние цепочки публикаций
		* @return object
		*/
		
		private function get_state_obj() {
			if (!$this->state_obj)
				$this->state_obj = object::factory('WF_STATE');
			return $this->state_obj;
		}
		
		/**
		* Возвращает объект состояние цепочки публикаций
		* @return object
		*/
		
		private function get_resolution_obj() {
			if (!$this->resolution_obj) 
				$this->resolution_obj = object::factory('WF_RESOLUTION');
			return $this->resolution_obj;
		}
		

		/**
		* Возвращает массив сообщений об ошибках
		* @return array
		*/
		
		public function get_report() {
			return $this->report;
		}
		
		/**
		* Возвращает название цепочки публикаций
		* @return string
		*/
		public function get_workflow_name() {
			return $this->workflow_name;
		}


		/**
		* Запуск теста, возвращает кол-во ошибок
		* @return int кол-во возникших ошибок
		*/
		
		public function test () {
			$workflow_obj = object::factory('WF_WORKFLOW');
			$this->workflow = $workflow_obj->get_change_record(array('WF_WORKFLOW_ID'=>$this->workflow_id));
			$this->workflow_name = $workflow_obj->get_record_title(array('WF_WORKFLOW_ID'=>$this->workflow_id));
			$workflow_obj->__destruct();

			if ($this->workflow['WF_WORKFLOW_ID']) {
				$this->test_workflow_type();
				$this->test_workflow_states();	
				$this->test_workflow_resolutions();
			}
			else {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_bad_workflow_id'].': '.$this->workflow_id);
			}
			
			return sizeof($this->report)?FALSE:TRUE;
		}
		
		/**
		* Проверка типа воркфлоу
		*/
		
		private function test_workflow_type() {
			// Тип цепочек публикаций должен быть существующим
			if (!self::check_type($this->workflow['WORKFLOW_TYPE'], 'WF_WORKFLOW', 'WORKFLOW_TYPE')) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_bad_workflow_type_for'].': "'.$this->workflow_name.'": "'.$this->workflow['WORKFLOW_TYPE'].'"');
			
		}

		
		/**
		* Проверка состояний
		*/
		
		private function test_workflow_states () {
			$all_states = db::sql_select('SELECT * FROM WF_STATE WHERE WF_WORKFLOW_ID=:workflow_id', array('workflow_id'=>$this->workflow_id));
			
			// Должно существовать хотя бы одно состояние в каждой цепочке публикаций
			if (!sizeof($all_states)) {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_workflow_has_no_states'].' "'.$this->workflow_name.'"');
				return;
			}
			
			$all_states=lib::array_reindex($all_states, 'WF_STATE_ID');
			
			$this->set_workflow_states();

			// Состояние не является исходным и в него нельзя привести запись из исходных состояний
			$odd_states = array_diff(array_keys($all_states), array_keys($this->states));
			
			if (sizeof($odd_states)) 
				foreach ($odd_states as $state_id) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_is_not_used'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state_id)).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'"');
			
			foreach ($this->states as $state)
				$this->test_state($state);
			
			$this->test_workflow_edges();
		}
		
		/**
		* Заполнение массива состояний, по workflow и резолюциям, одновременно каждому состоянию назначается язык
		*/
		
		private function set_workflow_states() {
			$start_edges = lib::array_reindex(self::get_edges_for_workflow($this->workflow_id, 'new'), 'LANG_ID');
			ksort($start_edges);
			$this->states = array();
			$i = 0;
			

			// в случае если у нас колонка без языка совмещена с какой-либо другой, то ее рассматривать отдельно не будем
			if (isset($start_edges[0])) 
				foreach ($start_edges as $lang_id=>$starter)
					if ($lang_id)
						if ($starter['WF_STATE_ID']==$start_edges[0]['WF_STATE_ID']) {
							unset ($start_edges[0]);
							break;
						}
			
			
			foreach ($start_edges as $starter) 
				$this->states=lib::array_merge_recursive2($this->states, self::get_states_with_langs($starter['WF_STATE_ID'], $starter['LANG_ID'], $this->states));
		}
		
		
		/**
		* Проверка состояния
		* @param array $state данные состояния
		*/
		
		private function test_state($state) {
			$workflow_type = $this->workflow['WORKFLOW_TYPE'];
			
			// Версионность состояний должна быть существующей
			if (!self::check_type ($state['VERSIONS'], 'WF_STATE', 'VERSIONS')) {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_bad_state_type'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'": "'.$state['VERSIONS'].'"');
			}
			// Версионность состояний должна принимать значения разрешенные текущим типом цепочки публикации
			elseif (
				(
					($workflow_type=='use_versions') 
						&& 
							(!in_array($state['VERSIONS'], array('test_version', 'two_versions', 'no_version')))
				) ||
				(
					($workflow_type=='dont_use_versions') 
						&& 
							(!in_array($state['VERSIONS'], array('one_version', 'no_version')))
				)
			) {
							
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_bad_state_type'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'" :'.$state['VERSIONS']);
			}
			
			$this->test_state_edging($state);
		}
		
		
		/**
		* Проверка граничности состояния
		* @param array $state данные состояния
		*/
		
		private function test_state_edging($state) {
			$edges = db::sql_select('
				SELECT 
					* 
				FROM 
					WF_EDGE_STATE 
				WHERE 
					WF_STATE_ID=:wf_state_id 
				ORDER BY 
					EDGE_TYPE
				', 
				array(
					'wf_state_id'=>$state['WF_STATE_ID'],
				)
			);
			
			// Состояние с версионностью нет версий должно быть удаленным
			if (($state['VERSIONS']=='no_version') && ((!sizeof($edges) || ($edges[0]['EDGE_TYPE']!='deleted')))) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_has'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_no_versions_but_have_no_edge_deleted']);
			
			// Граничные состояния не могут быть с двумя версиями
			if (($state['VERSIONS']=='two_versions') && sizeof($edges)) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_has'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_has_two_versions_but_is_edge']);
		}
		
		
		/**
		* Проверка границ
		*/
		
		private function test_workflow_edges () {
			$edges = self::get_edges_for_workflow($this->workflow_id);
			
			$starters = array();
			$deleters = array();
			$start_ids = array();
			$delete_ids = array();
			foreach ($edges as $ed) {
				$this->test_edge($ed);
				if ($ed['EDGE_TYPE']=='new') {
					$starters[] = $ed;
					$start_ids[] = $ed['WF_STATE_ID'];
				}
				elseif ($ed['EDGE_TYPE']=='deleted') {
					$deleters[] = $ed;
					$delete_ids[] = $ed['WF_STATE_ID'];
				}
			}
			
			// В каждой цепочке публикаций должно быть стартовое состояние
			if (!sizeof($starters)) {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_has_no_start_edges_for'].': "'.$this->workflow_name.'"');
				return;
			}
			
			// Одно и то же состояние не может быть одновременно и начальным и удаленным
			$bad_ids = array_intersect($start_ids, $delete_ids);
			if (sizeof($bad_ids)) {
				foreach ($bad_ids as $state_id) {
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_is_new_and_deleted_at_once'].': "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state_id)).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'"');
				}
			}
		
			$this->test_start_deleted_edges($starters, $deleters);
			$this->test_start_edges($starters);
			$this->test_deleted_edges($deleters);
		}		
	

		/**
		* Проверка граничного состояния
		* @param array $edge Данные граничного состояния
		*/
		
		private function test_edge($edge) {
			//  Языки граничных состояний должны быть существующими
			if ($edge['LANG_ID'] && !self::check_lang($edge['LANG_ID'])) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_edge_has_bad_language'].' ID='.$edge['LANG_ID'].': '.metadata::$lang['lang_state'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$edge['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'"');

			// Типы граничных состояний должны быть существующими
			if (!self::check_type ($edge['EDGE_TYPE'], 'WF_EDGE_STATE', 'EDGE_TYPE')) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_edge_has_bad_type'].' "'.$edge['EDGE_TYPE'].'": '.metadata::$lang['lang_state'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$edge['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'"');
			
			if ($edge['EDGE_TYPE']=='new') {
				// Для каждого стартового состояния должна присутствовать резолюция
				$check_res = db::sql_select('SELECT WF_RESOLUTION_ID FROM WF_RESOLUTION WHERE FIRST_STATE_ID=:state_id', array('state_id'=>$edge['WF_STATE_ID']));

				if (!sizeof ($check_res)) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_start_edge_has_no_resolutions'].': '.metadata::$lang['lang_state'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$edge['WF_STATE_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].': "'.$this->workflow_name.'"');
			}
		}
		
		
		/**
		* Проверка стартовых состояний
		* @param array $starters Данные стартовых состояний из таблицы WF_EDGE_STATE
		*/
		
		private function test_start_edges ($starters) {
			$lang = object::factory('LANG');
			$s_by_lang = array();
			$s_by_state = array();
			$state_errors = array();
			foreach ($starters as $starter) {
				$s_by_lang[$starter['LANG_ID']][]=$starter;
				
				if ($starter['LANG_ID']) {
					// одно и то же состояние не может быть начальным для нескольких языков
					if ($s_by_state[$starter['WF_STATE_ID']]) {
						$state_errors[$starter['LANG_ID']]=$starter['WF_STATE_ID'];
					}
					else {
						$s_by_state[$starter['WF_STATE_ID']]=$starter;
					}
				}
			}
			
			foreach ($s_by_lang as $lang_id=>$s) {
				if (sizeof($s)>1) {
					if ($lang_id) {
						// В каждой цепочке публикации может быть только одно стартовое состояние для одного языка
						$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_workflow_has'].' "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_has_more_than_one_entrance_with_lang'].' '.$lang->get_record_title(array('LANG_ID'=>$lang_id)));
					}
					else {
						// В каждой цепочке публикации может быть только одно стартовое состояние без языка
						$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_workflow_has'].' "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_has_more_than_one_entrance_with_no_lang']);
					}
				}
			}
			
			// одно и то же состояние не может быть начальным для нескольких языков
			foreach ($state_errors as $lang_id=>$state_id) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_workflow_has'].' "'.$this->workflow_name.'"'.metadata::$lang['lang_autotest_check_workflow_has_one_start_for_several_languages'].' - "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$state_id)).'"');

			$lang->__destruct();
		}
		
		/**
		* Проверка удаленных состояний
		* @param array $deleters Данные удаленных состояний из таблицы WF_EDGE_STATE
		*/
		
		private function test_deleted_edges ($deleters) {
			$recycle_deleters = array();
			$deleter_langs = array();
			$full_deleter_founded = 0;
			$deleter_state_id=0;
			$lang_obj = object::factory('LANG');
			
			$used_langs = lib::array_reindex($this->states, 'LANG_ID');
			
			foreach ($deleters as $deleter) {
				if ($this->states[$deleter['WF_STATE_ID']]['VERSIONS']=='no_version' && ($deleter_state_id!=$deleter['WF_STATE_ID'])) {
					++$full_deleter_founded;
					$deleter_state_id=$deleter['WF_STATE_ID'];
				}
				else {
					// Удаленное состояние в цепочке публикации может быть только одно для каждого языка (может быть одновременно для одного из языков и для случая без языка), если физического удаления не производится
					if ($deleter['LANG_ID']) {
						++$recycle_deleters [$deleter['LANG_ID']];
					}
				}
				
				// Если в цепочке публикаций не используется язык, а в то же время удаленное состояние у него есть, то нужно сообщить об этом
				if ($deleter['LANG_ID'] && !in_array($deleter['LANG_ID'], array_keys($used_langs))) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$this->workflow_name.'": '.metadata::$lang['lang_autotest_check_workflow_bad_language_in_deleted_state'].': '.($deleter['LANG_ID']?$lang_obj->get_record_title(array('LANG_ID'=>$deleter['LANG_ID'])):metadata::$lang['lang_wf_workflow_without_language']));
			}
			
			//	Удаленное состояние в цепочке публикации может быть только одно для всех языков (включая случай без языка), если физическое удаление производится.
			if ($full_deleter_founded>1) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_more_than_one_deleted_state']);
			
			foreach ($recycle_deleters as $lang_id=>$rec) {
				if ($rec>1) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$this->workflow_name.'" '.metadata::$lang['lang_autotest_check_workflow_more_than_one_deleted_state_for_lang'].' '.$lang_obj->get_record_title(array('LANG_ID'=>$lang_id)));
			}		
			
			$lang_obj->__destruct();
		}
		
		/**
		* Проверка в комплексе стартовых и удаленных состояний
		* @param array $starters Данные стартовых состояний из таблицы WF_EDGE_STATE
		* @param array $deleters Данные удаленных состояний из таблицы WF_EDGE_STATE
		*/

		private function test_start_deleted_edges($starters, $deleters) {
			// Если одни и те же состояния используются одновременно для случаев языка и без языка, то и для варианта с языком и для варианта без языка удаленное состояние должно быть либо одинаковое, либо не быть ни там, ни там
			$start_langs=array();
			foreach ($starters as $starter) 
				$start_langs[$starter['WF_STATE_ID']][]=$starter['LANG_ID'];
			
			$deleter_langs=array();
			foreach ($deleters as $deleter) 
				$deleter_langs[$deleter['WF_STATE_ID']][]=$deleter['LANG_ID'];
			
			foreach ($start_langs as $s_state_id=>$s_langs) 
				if (sizeof($s_langs)>1)
					foreach ($deleter_langs as $d_state_id=>$d_langs) {
						if (array_intersect($s_langs, $d_langs) && array_diff($s_langs, $d_langs))
							$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_langs_must_be_same_in_starter_and_deleted_states'].': '.metadata::$lang['lang_wf_resolution_first_state'].'"'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$s_state_id)).'", '.metadata::$lang['lang_wf_workflow_after_delete_state'].' "'.$this->get_state_obj()->get_record_title(array('WF_STATE_ID'=>$d_state_id)).'"' );
					}
		}
		
		/**
		* Проверка резолюций воркфлоу
		*/
		
		private function test_workflow_resolutions() {
			// У каждой цепочки публикаций должна существовать хотя бы одна резолюция
			$check_res = db::sql_select('SELECT WR.WF_RESOLUTION_ID FROM WF_RESOLUTION WR INNER JOIN WF_STATE WS ON (WS.WF_STATE_ID=WR.FIRST_STATE_ID) WHERE WS.WF_WORKFLOW_ID=:workflow_id LIMIT 1', array('workflow_id'=>$this->workflow_id));
			if (!sizeof($check_res))
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_has_no_resolutions'].' "'.$this->workflow_name.'"');

			$starters = self::get_edges_for_workflow($this->workflow_id, 'new');
			
			// проводим тест всех состояний по резолюциям для цепочки публикаций
			$processed_states=array();
			foreach ($starters as $starter) {
				$this->go_by_resolutions_for_test($starter['WF_STATE_ID'], $processed_states);
			}
		}
		
		/**
		* Рекурентная ф-ия прохода по резолюциям для тестирования
		* @param int $first_state_id ID начального состояния
		* @param array $processed_states массив пройденных состояний рекурсией (для внутреннего пользования)
		*/
		
		private function go_by_resolutions_for_test ($first_state_id, &$processed_states=array()) {
			if (in_array($first_state_id, $processed_states)) return;
			
			$processed_states[]=$first_state_id;
			$resolutions = db::sql_select('SELECT * FROM WF_RESOLUTION WHERE FIRST_STATE_ID=:state_id', array('state_id'=>$first_state_id));
			if (sizeof($resolutions)) {
				$last_states=array();
				$last_versions=array();
				foreach ($resolutions as $res) {
					if (!$this->is_resolution_parallel($res['WF_RESOLUTION_ID'])) {
						if ($res['FIRST_STATE_ID']!=$res['LAST_STATE_ID']) {
							$last_states[$res['LAST_STATE_ID']][]=$res;
						}
						else
							$last_versions[$res['LAST_STATE_ID']][$res['MAIN_VERSION']][]=$res;
					}
					if ($this->test_resolution($res) && !in_array($res['LAST_STATE_ID'], $processed_states)) 
						$this->go_by_resolutions_for_test($res['LAST_STATE_ID'], $processed_states);
				}
				
				// Нельзя делать несколько непараллельных резолюций, соединяющих одну и ту же пару состояний. Такие резолюции должны заменяться одной параллельной с кворумом 1
				foreach ($last_states as $state_id=>$ress) 
					$this->check_several_unparallel_resolutions($ress);
				
				
				foreach ($last_versions as $state_id=>$versions) 
					foreach ($versions as $version=>$ress) 
						$this->check_several_unparallel_resolutions($ress);
			}
		}
		
		/**
		* Является ли резолюция параллельной
		* @param int $resolution_id ID резолюции
		* @return boolean
		*/
		
		private function is_resolution_parallel ($resolution_id) {
			$priv_res = db::sql_select('SELECT COUNT(*) AS CNT FROM WF_PRIVILEGE_RESOLUTION WHERE WF_RESOLUTION_ID=:resolution_id', array('resolution_id'=>$resolution_id));
			return ($priv_res[0]['CNT']>1)?true:false;
		}
		
		/**
		* Проверка резолюций и вывод в репорт по поводу непараллельных, соединяющих одну и ту же пару состояний.
		* @param array $ress массив резолюций
		*/
		private function check_several_unparallel_resolutions($ress) {
			if (sizeof($ress)>1) {
				$res_str = array();
				foreach ($ress as $res) 
					$res_str[] = $this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID']));
				
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_several_unparallel_resolutions_with_same_states'].': '.implode(', ', $res_str));
			}
		}
		
		/**
		* Тест резолюции
		* @param array $res Данные резолюции
		* @return boolean Продолжать ли рекурентный проход
		*/
		
		private function test_resolution($res) {
			//	Начальное и конечное состояния резолюции должны относиться к одной цепочке публикации.
			if (!$this->states[$res['LAST_STATE_ID']] || ($this->states[$res["FIRST_STATE_ID"]]['WF_WORKFLOW_ID']!=$this->states[$res["LAST_STATE_ID"]]['WF_WORKFLOW_ID'])) {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_first_and_last_state_must_be_in_same_workflow'].'. '.metadata::$lang['lang_resolution'].': "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
				return false;
			}
			
			// Исходное и конечное состояние резолюции одно и то же, при этом главной версии нет – такой переход не имеет смысла.
			if (($res['FIRST_STATE_ID']==$res['LAST_STATE_ID']) && ($res['MAIN_VERSION']==2)) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_first_and_last_state_same_main_version_absent'].'. '.metadata::$lang['lang_resolution'].': "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			// Резолюции должны иметь существующие значения поля «Главная версия»
			if (!in_array($res['MAIN_VERSION'], range(0, 2))) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_bad_main_version'].'. '.metadata::$lang['lang_resolution'].': "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			// У резолюций с начальным состоянием с одной версией значение поля "Главная версия" может иметь значение только "Нет главной версии"
			
			if (in_array($this->states[$res['FIRST_STATE_ID']]['VERSIONS'], array('test_version', 'one_version')) && ($res['MAIN_VERSION']!=2)) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_first_state_has_one_version_and_main_version'].'. '.metadata::$lang['lang_resolution'].': "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			// Резолюция не может вести из состояния без версий
			if ($this->states[$res['FIRST_STATE_ID']]['VERSIONS']=='no_version')
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_first_state_has_no_versions'].'. '.metadata::$lang['lang_resolution'].': "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			$this->test_resolution_privileges($res);
			$this->test_resolution_lang($res);
    	
			return true;
		}
		

		/**
		* Тестирование привилегий резолюции
		* @param array $res Данные резолюции
		*/
		
		private function test_resolution_privileges($res) {
			$privileges = db::sql_select('SELECT * FROM WF_PRIVILEGE_RESOLUTION WHERE WF_RESOLUTION_ID=:resolution_id', array('resolution_id'=>$res['WF_RESOLUTION_ID']));
			
			// Резолюции должно быть назначено хотя бы одно право воркфлоу.
			if (!sizeof($privileges)) {
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_no_privileges'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
				return;
			}
			if (sizeof($privileges)>1) {
				//	Если резолюции назначено более одного права воркфлоу, то поле Кворум должно быть не менее 1 и не более количества назначенных прав воркфло
				if ($res['QUORUM']<1) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_more_than_one_privilege_and_no_quorum'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
				elseif ($res['QUORUM']>sizeof($privileges))
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_more_quorum_than_privileges'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			}
			
			// Все назначенные права цепочек публикации должны быть существующими.
			foreach($privileges as $pri) {
				$check_priv = db::sql_select('
					SELECT 
						AP.AUTH_PRIVILEGE_ID 
					FROM 
						AUTH_PRIVILEGE AP
							INNER JOIN 
								AUTH_OBJECT_TYPE AOT
									ON (AP.AUTH_OBJECT_TYPE_ID=AOT.AUTH_OBJECT_TYPE_ID)
					WHERE 
						AP.AUTH_PRIVILEGE_ID=:auth_privilege_id
							AND
								AOT.SYSTEM_NAME=:workflow
							
				', 
					array(
						'auth_privilege_id'=>$pri['AUTH_PRIVILEGE_ID'],
						'workflow' => 'workflow'
					)
				);
				
				if (!sizeof($check_priv))
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_bad_auth_privilege'].' ID='.$pri['AUTH_PRIVILEGE_ID'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			}
		}
		
		/**
		* Тестирование в резолюции всего что связано с языком
		* @param array $res Данные резолюции
		*/
		
		private function test_resolution_lang($res) {
			if (!$res['LANG_ID']) {
				// У резолюции язык не указан, но исходное и конечное состояния относятся к разным языкам.
				if (($this->states[$res['FIRST_STATE_ID']]['LANG_ID']!=$this->states[$res['LAST_STATE_ID']]['LANG_ID']) && ($this->states[$res['LAST_STATE_ID']]['VERSIONS']!='no_version')) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_is_not_translate_but_states_languages_different'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
				return;
			}
			
			// Языки должны быть назначены существующими
			if (!self::check_lang($res['LANG_ID'])) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_bad_lang_id'].' ID='.$res['LANG_ID'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			// Резолюция переводит запись на тот же самый язык.
			elseif ($this->states[$res['FIRST_STATE_ID']]['LANG_ID']==$this->states[$res['LAST_STATE_ID']]['LANG_ID']) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_translates_on_the_same_language'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			// Резолюция переводит запись в состояние, язык которого не соответствует языку резолюции.
			elseif ($res['LANG_ID']!=$this->states[$res['LAST_STATE_ID']]['LANG_ID']) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_translates_on_different_from_state_language'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			// Резолюция перевода на другой язык не должна вести в состояние с двумя версиями.
			if ($this->states[$res['LAST_STATE_ID']]['VERSIONS']=='two_versions') 
				$this->report[] = array("states"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_translate_resolution_go_to_two_versions'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			// Резолюция перевода не может быть применена к состоянию, в которое запись может попасть только из начального состояния без языка.
			if (!$this->states[$res['FIRST_STATE_ID']]['LANG_ID']) 
				$this->report[] = array("states"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_translate_resolution_can_not_be_applied_to_a_state_without_language'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
			
			//	Резолюция, переводящая в удаленное состояние, не может быть резолюцией перевода.
			$edge_deleted = db::sql_select('SELECT * FROM WF_EDGE_STATE WHERE WF_STATE_ID=:state_id AND EDGE_TYPE=:edge_type', array('state_id'=>$res['LAST_STATE_ID'], 'edge_type'=>'deleted'));
			if (sizeof($edge_deleted)) 
				$this->report[] = array("states"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_for_convert_to_deleted_state_can_not_be_translate'].'. '.metadata::$lang['lang_resolution'].' "'.$this->get_resolution_obj()->get_record_title(array('WF_RESOLUTION_ID'=>$res['WF_RESOLUTION_ID'])).'" '.metadata::$lang['lang_autotest_check_workflow_of_workflow'].' "'.$this->workflow_name.'"');
		}
		
		/**
		* Проверка существования языка
		* @param int $lang_id ID языка
		* @return boolean
		*/
		
		public static function check_lang($lang_id) {
			$check_lang = db::sql_select('SELECT * FROM LANG WHERE LANG_ID=:lang_id', array('lang_id'=>$lang_id));
			return sizeof($check_lang)?true:false;
		}
		
		/**
		* Проверка типа значения по value_list поля select1 из def-а
		* @param mixed $value Значение
		* @param string $obj_name Название объекта
		* @param string $field_name Название поля
		* @return boolean
		*/
		
		public static function check_type ($value, $obj_name, $field_name) {
			if (is_array(metadata::$objects[$obj_name]['fields'][$field_name]['value_list'])) {
				$values = array();

				foreach (metadata::$objects[$obj_name]['fields'][$field_name]['value_list'] as $def_value) 
					$values[] = $def_value['value'];
				
				return in_array($value, $values);
			}
			return true;	
		}

		
		/**
		* Возвращает данные о конечных состояниях цепочки публикаций
		* @param int $workflow_id ID цепочки публикаций
		* @param string $edge_type Тип состояния, если не указан, то выводятся все
		* @return array
		*/
		
		public static function get_edges_for_workflow ($workflow_id, $edge_type='') {
			$params = array();
			
			$sql = 'SELECT WES.* FROM WF_EDGE_STATE WES INNER JOIN WF_STATE WS ON (WES.WF_STATE_ID=WS.WF_STATE_ID) WHERE WS.WF_WORKFLOW_ID=:workflow_id';
			$params['workflow_id'] = $workflow_id;
			
			if ($edge_type) {
				$sql .= ' AND EDGE_TYPE=:edge_type';
				$params['edge_type']=$edge_type;
			}
			
			$edges=db::sql_select($sql, $params);
			
			// добавляем к стартовым состояниям состояния с языками, у которых нет стартовых состояний, и на которые можно попасть только переводом
			if (!$edge_type || ($edge_type=='new')) {
				$filter_func = create_function('$var', 'return $var["EDGE_TYPE"]=="new";');
				$new_edges=array_filter($edges, $filter_func);
				$current_lang_edges = lib::array_reindex($new_edges, 'LANG_ID');
				// если нет языка, то не рассматриваем

				unset($current_lang_edges[0]);
				if (sizeof($current_lang_edges)) {
					$hobo_langs=db::sql_select('
						SELECT DISTINCT
							WR.LAST_STATE_ID, 
							WR.LANG_ID 
						FROM 
							WF_RESOLUTION WR
								INNER JOIN 
									WF_STATE WS
										ON (WR.LAST_STATE_ID=WS.WF_STATE_ID)
						WHERE 
							WR.LANG_ID>0 
								AND 
									LANG_ID NOT IN ('.implode(', ', array_keys($current_lang_edges)).')
								AND
									WS.WF_WORKFLOW_ID=:workflow_id',
						array ('workflow_id'=>$workflow_id)
					);
					if (sizeof($hobo_langs)) 
						foreach ($hobo_langs as $hobo) 
							$edges[]=array(
								'WF_STATE_ID' => $hobo['LAST_STATE_ID'],
								'LANG_ID' => $hobo['LANG_ID'],
								'EDGE_TYPE' => 'new'
							);
				}
			}
		
			return $edges;
		}
		
		

		/**
		* Рекурентная функция, возвращающая массив состояний, начиная со state_id считая по резолюциям. Для каждого состояния назначается язык
		* @param int $state_id ID состояния
		* @param int $lang_id ID языка
		* @param array $states Уже пройденные состояния
		* @return array массив состояний
		*/
		
		public static function get_states_with_langs($state_id, $lang_id, $states=array()) {
			$state=db::sql_select('SELECT * FROM WF_STATE WHERE WF_STATE_ID=:state_id', array('state_id'=>$state_id));
			if (sizeof($state)) {
				$state=$state[0];
				$states[$state['WF_STATE_ID']]=$state;
				if ($lang_id)
					$states[$state['WF_STATE_ID']]['LANG_ID']=$lang_id;
				if (sizeof($states)) {
					$first_state=&reset($states);
					$states[$state['WF_STATE_ID']]['FIRST_STATE_ID']=$first_state['WF_STATE_ID'];
				}
			}
			
			$resolutions = db::sql_select('
				SELECT 
					* 
				FROM 
					WF_RESOLUTION WR 
				WHERE 
					FIRST_STATE_ID=:state_id 
						AND (
								LANG_ID=0 
									OR 
								NOT EXISTS (
									SELECT 
										* 
									FROM 
										WF_RESOLUTION WR2 
									WHERE 
										WR2.LAST_STATE_ID=WR.FIRST_STATE_ID AND LANG_ID=0
								)
						)', array('state_id'=>$state_id));
			foreach ($resolutions as $res) {
				if (!in_array($res['LAST_STATE_ID'], array_keys($states))) 
					$states=lib::array_merge_recursive2($states, self::get_states_with_langs($res['LAST_STATE_ID'], $res['LANG_ID']?$res['LANG_ID']:$lang_id, $states));
			}
			return $states;
		}
		
	}
?>