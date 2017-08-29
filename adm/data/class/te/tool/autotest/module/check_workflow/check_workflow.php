<?PHP

	include_once params::$params["adm_data_server"]["value"]."class/te/tool/autotest/module/check_workflow/test_workflow.php";
	
/**
* Класс автотеста - проверка workflow
* @package		RBC_Contents_5_0
* @subpackage te
* @author Alexandr Vladykin <avladykin@rbc.ru>
* @copyright	Copyright (c) 2007 RBC SOFT 
*/
	
	class check_workflow extends autotest_test{
		
		/**
		* запуск теста
		*/
		
		public function do_test() {
			try {
				$this->test_whole_system();
				$this->test_all_workflows();
				$this->test_whole_objects();
			}
			catch (Exception $e) {
				// выходим из автотеста, в случае необходимости прекращения, но не ошибки (Exception(return)) - ошибку не выдаем
				if ($e->getMessage() != 'return')
					throw $e;
			}
		}
		
		/**
		* Полный тест системы
		*/
		
		private function test_whole_system() {
			$this->test_whole_workflows();
			$this->test_whole_states();
			$this->test_whole_auth_privileges();
			$this->test_whole_edges();
			$this->test_whole_resolutions();
		}
		
		/**
		* Общая проверка цепочек публикаций
		*/
		
		private function test_whole_workflows() {
			$workflow_count = db::sql_select('SELECT COUNT(*) AS CNT FROM WF_WORKFLOW');
			
			// должна существовать хотя бы одна цепочка публикации
			if ($workflow_count[0]['CNT']==0) {
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_no_workflows_presented']);
				throw new Exception('return');
			}
		}
		
		/**
		* Общая проверка состояний
		*/
		
		private function test_whole_states() {
			$states_count = db::sql_select('SELECT COUNT(*) AS CNT FROM WF_STATE');
			// должно существовать хотя бы одно состояние
			if (!$states_count[0]['CNT']) {
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_no_states_presented']);
				throw new Exception('return');
			}
				
			// состоянию должна быть назначена существующая цепочка публикаций
			$bad_states = db::sql_select('SELECT WS.WF_STATE_ID, WS.WF_WORKFLOW_ID FROM WF_STATE WS LEFT JOIN WF_WORKFLOW WW ON (WS.WF_WORKFLOW_ID=WW.WF_WORKFLOW_ID) WHERE WW.WF_WORKFLOW_ID IS NULL');
			if (sizeof($bad_states)) {
				$state_obj = object::factory('WF_STATE');
				foreach ($bad_states as $st) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_state_has_bad_workflow_id_for'].' "'.$state_obj->get_record_title(array('WF_STATE_ID'=>$st['WF_STATE_ID'])).'": ID='.$st['WF_WORKFLOW_ID']);
				$state_obj->__destruct();
			}
		}
		
		/**
		* Общая проверка прав цепочки публикаций
		*/
		
		private function test_whole_auth_privileges() {
			// получаем номер типа workflow для права цепочки публикаций
			$auth_type = db::sql_select('SELECT AUTH_OBJECT_TYPE_ID FROM AUTH_OBJECT_TYPE WHERE SYSTEM_NAME=:workflow', array('workflow'=>'workflow'));
			if (!sizeof($auth_type)) {
				// тип объекта разделения доступа workflow должен существовать
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_no_auth_object_type_workflow_presented']);
				throw new Exception('return');
			}
			$auth_object_type_id = $auth_type[0]['AUTH_OBJECT_TYPE_ID'];
			
			// должно существовать хотя бы одно право цепочки публикаций
			$count = db::sql_select('
				SELECT 
					COUNT(*) AS CNT 
				FROM 
					AUTH_PRIVILEGE 
				WHERE 
					AUTH_OBJECT_TYPE_ID=:auth_object_type_id
			', 
				array (
					'auth_object_type_id'=>$auth_object_type_id
				)
			);
			if (!$count[0]['CNT']) 
				$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_no_auth_privileges_presented']);
			
			// системные названия всех прав цепочки публикаций должны быть уникальными
			$auth_privileges = db::sql_select('
				SELECT
					SYSTEM_NAME, 
					COUNT(*) AS CNT 
				FROM 
					AUTH_PRIVILEGE 
				WHERE
					AUTH_OBJECT_TYPE_ID=:auth_object_type_id
				GROUP BY 
					SYSTEM_NAME 
				HAVING COUNT(*)>1
			',
				array (
					'auth_object_type_id'=>$auth_object_type_id
				)
			);
			if (sizeof($auth_privileges)) {
				$auth_privileges = lib::array_reindex($auth_privileges, 'SYSTEM_NAME');
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_some_auth_privileges_system_names_doubles'].': "'.implode('", "', array_keys($auth_privileges)).'"');
			}
		}

		/**
		* Общая проверка граничных состояний
		*/
		
		private function test_whole_edges() {
			// Состояния должны быть существующими
			$bad_edges = db::sql_select('SELECT WE.WF_STATE_ID FROM WF_EDGE_STATE WE LEFT JOIN WF_STATE WS ON (WE.WF_STATE_ID=WS.WF_STATE_ID) WHERE WS.WF_STATE_ID IS NULL');
			if (sizeof($bad_edges)) 
				foreach ($bad_edges as $ed) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_edge_has_bad_state_id'].' WF_STATE_ID='.$ed['WF_STATE_ID']);
		}
		
		/**
		* Общая проверка резолюций
		*/
		
		private function test_whole_resolutions() {
			// Состояния должны быть существующими
			$resolution_obj = object::factory('WF_RESOLUTION');
			$bad_resolutions = db::sql_select('SELECT WR.WF_RESOLUTION_ID, WR.FIRST_STATE_ID FROM WF_RESOLUTION WR LEFT JOIN WF_STATE WS ON (WR.FIRST_STATE_ID=WS.WF_STATE_ID) WHERE WS.WF_STATE_ID IS NULL');
			if (sizeof($bad_resolutions)) 
				foreach ($bad_resolutions as $rs) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_bad_first_state_id'].' "'.$resolution_obj->get_record_title(array('WF_RESOLUTION_ID'=>$rs['WF_RESOLUTION_ID'])).'". STATE_ID='.$rs['FIRST_STATE_ID']);

			$bad_resolutions = db::sql_select('SELECT WR.WF_RESOLUTION_ID, WR.LAST_STATE_ID FROM WF_RESOLUTION WR LEFT JOIN WF_STATE WS ON (WR.LAST_STATE_ID=WS.WF_STATE_ID) WHERE WS.WF_STATE_ID IS NULL');
			if (sizeof($bad_resolutions)) 
				foreach ($bad_resolutions as $rs) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_resolution_has_bad_last_state_id'].' "'.$resolution_obj->get_record_title(array('WF_RESOLUTION_ID'=>$rs['WF_RESOLUTION_ID'])).'". STATE_ID='.$rs['LAST_STATE_ID']);
					
			$resolution_obj -> __destruct();

		}

		/**
		* Проверка всех существующих цепочек публикаций
		*/
		
		private function test_all_workflows() {
			$workflows = db::sql_select('SELECT * FROM WF_WORKFLOW ORDER BY IS_DEFAULT DESC');
			for ($i=0, $n=sizeof($workflows); $i<$n; $i++) {
				if (!$i) {
					// Должна существовать цепочка публикаций по умолчанию
					if (!$workflows[$i]['IS_DEFAULT'])
						$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_no_default_workflows_presented']);
					else 
						$this->test_default_workflow($workflows[$i]);
				}
				// Должна существовать только одна цепочка публикаций по умолчанию
				elseif ($i==1)
					if ($workflows[$i]['IS_DEFAULT'])
						$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_several_default_workflows_presented']);
			
				$this->test_workflow($workflows[$i]['WF_WORKFLOW_ID']);
			}
		}
		
		/**
		* Проверка цепочки публикаций по умолчанию
		* @param array $workflow Данные публикации по умолчанию
		*/
		
		private function test_default_workflow ($workflow) {
			// Цепочка публикаций по умолчанию должна иметь тип "Для таблиц с версиями"
			if ($workflow['WORKFLOW_TYPE']!='use_versions') 
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_default_workflow_has_incorrect_type']);
				
			// Цепочка публикаций по умолчанию должна иметь начальные состояния для любого из языков а также для случая без языка
			$edges = lib::array_reindex(
						db::sql_select(
							'SELECT 
								WES.* 
							FROM 
								WF_EDGE_STATE WES
									INNER JOIN
										WF_STATE WS
											ON (WS.WF_STATE_ID=WES.WF_STATE_ID)
							WHERE 
								WS.WF_WORKFLOW_ID=:wf_workflow_id 
									AND 
										WES.EDGE_TYPE=:new', 
							array ('wf_workflow_id'=>$workflow['WF_WORKFLOW_ID'], 'new'=>'new')
						), 
						'LANG_ID'
			);
			
			
			if (!$edges[0] || (sizeof($edges)<2)) 
				$this->report[] = array ("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_default_workflow_has_no_needed_start_edges']);
			
			
		}

		/**
		* Проверяем 1 цепочку публикаций
		* @param int $workflow_id ID цепочки публикации
		*/
		
		private function test_workflow ($workflow_id) {
			$test_obj = new test_workflow($workflow_id);
			$test_obj->test();
			$workflow_report=$test_obj->get_report();
			
			if (sizeof($workflow_report)) {
				$workflow_report[0]['is_new_table'] = 1;
				$workflow_report[0]['caption'] = $test_obj->get_workflow_name();
			}
			
			$this->report = array_merge($this->report, $workflow_report);
		}
		
		
		/**
		* Проверка объектов
		*/
		
		private function test_whole_objects()
		{
			foreach ( metadata::$objects as $obj_name => $obj ) 
			{
				if ( !is_array( $obj['decorators'] ) ) $obj['decorators'] = array();
				
				if ( in_array( 'workflow', $obj['decorators'] ) )
				{
					if ( in_array( 'block', $obj['decorators'] ) )
					{
						if ( $obj['workflow_scope'] == 'block' )
						{
							// Этим объектам нельзя назначать цепочки публикаций
							if ( object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] )
								$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_set_to_object_with_other_scope'].': "'.$obj_name.'" ('.object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] . ')');
							
							// Блокам этих объектов должны быть назначены цепочки публикаций
							$inf_blocks = db::sql_select('select INF_BLOCK_ID from INF_BLOCK where TE_OBJECT_ID = :object_id and WF_WORKFLOW_ID = 0',
								array('object_id'=>object_name::$te_object_ids[$obj_name]['TE_OBJECT_ID']));
							if ( count( $inf_blocks ) )
								$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_not_set_to_blocks_workflow_scope_blocks'].': "'.$obj_name.'" (' . lib::array_make_in( $inf_blocks, 'INF_BLOCK_ID' ) . ')');
							
							$this->test_workflow_blocks($obj_name);
						}
						else
						{
							// Этим объектам должны быть назначены цепочки публикаций
							if ( !object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] )
								$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_not_set_to_object_workflow_scope_table'].': "'.$obj_name.'"');
							
							// Блокам этих объектов нельзя назначать цепочки публикаций
							$inf_blocks = db::sql_select('select INF_BLOCK_ID from INF_BLOCK where TE_OBJECT_ID = :object_id and WF_WORKFLOW_ID <> 0',
								array('object_id'=>object_name::$te_object_ids[$obj_name]['TE_OBJECT_ID']));
							if ( count( $inf_blocks ) )
								$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_set_to_blocks_with_other_scope'].': "'.$obj_name.'" (' . lib::array_make_in( $inf_blocks, 'INF_BLOCK_ID' ) . ')');
							
							$this->test_workflow_object($obj_name, object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID']);
						}
					}
					else
					{
						// Этим объектам должны быть назначены цепочки публикаций
						if ( !object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] )
							$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_not_set_to_object_with_workflow'].': "'.$obj_name.'"');
						
						$this->test_workflow_object($obj_name, object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID']);
					}
				}
				else
				{
					if ( in_array( 'block', $obj['decorators'] ) )
					{
						// Блокам этих объектов нельзя назначать цепочки публикаций
						$inf_blocks = db::sql_select('select INF_BLOCK_ID from INF_BLOCK where TE_OBJECT_ID = :object_id and WF_WORKFLOW_ID <> 0',
							array('object_id'=>object_name::$te_object_ids[$obj_name]['TE_OBJECT_ID']));
						if ( count( $inf_blocks ) )
							$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_set_to_block_without_workflow'].': "'.$obj_name.'" (' . lib::array_make_in( $inf_blocks, 'INF_BLOCK_ID' ) . ')');
					}
					
					// Этим объектам нельзя назначать цепочки публикаций
					if ( object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] )
						$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_autotest_check_workflow_set_to_object_without_workflow'].': "'.$obj_name.'" ('.object_name::$te_object_ids[$obj_name]['WF_WORKFLOW_ID'] . ')');
				}
			}
		}
		
		/**
		* Проверка блоков
		* @param string $obj_name Название объекта
		*/
		
		private function test_workflow_blocks($obj_name) {
			$object_id = object_name::$te_object_ids[$obj_name]['TE_OBJECT_ID'];
			$inf_block_obj = object::factory('INF_BLOCK');
			$inf_blocks = db::sql_select('SELECT INF_BLOCK_ID, WF_WORKFLOW_ID FROM INF_BLOCK WHERE TE_OBJECT_ID=:object_id', array('object_id'=>$object_id));
			foreach ($inf_blocks as $block) 
				$this->test_workflow_object($obj_name, $block['WF_WORKFLOW_ID'], metadata::$lang['lang_autotest_check_workflow_for_block'].' "'.$inf_block_obj->get_record_title(array('INF_BLOCK_ID'=>$block['INF_BLOCK_ID'])).'"');
			$inf_block_obj->__destruct();
		}
		
		/**
		* Проверка объектов
		* @param string $obj_name Название объекта
		* @param int $workflow_id Идентификатор цепочки публикаций, связанной с объектом
		* @param string $add_info Концовка отчета об ошибке
		*/
		
		private function test_workflow_object($obj_name, $workflow_id, $add_info='') {
			if ($workflow_id) {
				$workflow_obj = object::factory('WF_WORKFLOW');
				$workflow=$workflow_obj->get_change_record(array('WF_WORKFLOW_ID'=>$workflow_id));
				// Цепочка публикации имеет тип «Для таблиц без версионности», но привязывается к таблице с декоратором «Версии»
				if (in_array('version', metadata::$objects[$obj_name]['decorators']) && ($workflow['WORKFLOW_TYPE']!='use_versions')) 
					$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$workflow_obj->get_record_title(array('WF_WORKFLOW_ID'=>$workflow_id)).'": '.metadata::$lang['lang_autotest_check_workflow_must_use_versions_for_object'].': "'.$obj_name.'" '.$add_info);
				
				$new_edges = test_workflow::get_edges_for_workflow($workflow_id, 'new');
				if (sizeof($new_edges)) {
					$new_edges = lib::array_reindex($new_edges, 'LANG_ID');
					// Цепочка публикации не имеет входов ни для одного из языков, но привязывается к таблице с декоратором «Языки».
					if (in_array('lang',  metadata::$objects[$obj_name]['decorators'])) {
						unset($new_edges[0]);
						if (!sizeof($new_edges)) 
							$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$workflow_obj->get_record_title(array('WF_WORKFLOW_ID'=>$workflow_id)).'": '.metadata::$lang['lang_autotest_check_workflow_has_no_entrances_for_langs_for_object'].': "'.$obj_name.'" '.$add_info);
					}
					else {
						// Цепочка публикации не имеет входов для случая отсутствия языка, но привязывается к таблице без декоратора «Языки»
						if (!$new_edges[0]) 
							$this->report[] = array("status"=>1, "descr"=>metadata::$lang['lang_wf_workflow'].' "'.$workflow_obj->get_record_title(array('WF_WORKFLOW_ID'=>$workflow_id)).'" '.metadata::$lang['lang_autotest_check_workflow_has_no_entrances_for_no_langs_for_object'].' "'.$obj_name.'" '.$add_info);
					}
				}
				$workflow_obj->__destruct();
			}
		}
	}
?>