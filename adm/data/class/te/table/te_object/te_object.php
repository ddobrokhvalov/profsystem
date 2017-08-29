<?php
	/**
	 * Класс для нестандартной работы таблицы TE_OBJECT
	 *
	 * @package		RBC_Contents_5_0
	 * @subpackage	te
	 * @copyright	Copyright (c) 2007 RBC SOFT
	 */
	class te_object extends table_translate
	{
		/**
		* Дополнительно скрываем поле о блокировке если она выключена, ибо в этом случае она бессмысленна
		*/
		public function html_card($mode, &$request){
			return $this->call_parent('html_card', array($mode, &$request));			
		}
		
		
		/**
		 * Проверяем возможность сохранения поля "Цепочка публикаций"
		 */
		public function exec_change( $raw_fields, $prefix, $pk )
		{
			$this -> full_object -> adjust_workflow_id_change( $pk );
			
			include_once(params::$params['adm_data_server']['value'] . 'class/te/table/inf_block/inf_block.php' );
			
			$te_object=$this->full_object->get_change_record($pk);
			if ( !metadata::$objects['TE_OBJECT']['fields']['WF_WORKFLOW_ID']['no_change'] )
				inf_block::check_workflow_id($raw_fields[$prefix.'WF_WORKFLOW_ID'], $te_object['SYSTEM_NAME']);
			
			$this -> call_parent( 'exec_change', array( $raw_fields, $prefix, $pk ) );
		}
		
		/**
		 * Проверяем необходимость вывода поля "Цепочка публикаций"
		 * А также скрываем блокировку если она не нужна
		 */
		public function action_change()
		{
			$pk = $this -> primary_key -> get_from_request();
			$this -> full_object -> adjust_workflow_id_change( $pk );
			$this -> full_object -> adjust_is_unblockable_change ( $pk );
			$this -> call_parent( 'action_change' );
		}
	
		/**
		 * Контролируем возможность смены цепочки публикаций
		 *
		 * @param array $te_object			Данные изменяемого объекта
		 * @metadatamod
		 */
		public function adjust_workflow_id_change( $pk )
		{
			$te_object_name = object_name::$te_object_names[$pk['TE_OBJECT_ID']]['SYSTEM_NAME'];
			
			if ( !metadata::$objects[$te_object_name]['decorators']['workflow'] ||
					metadata::$objects[$te_object_name]['workflow_scope'] == 'block' )
			{
				metadata::$objects['TE_OBJECT']['fields']['WF_WORKFLOW_ID']['no_change'] = true;
			}
			else
			{
				$content_count = db::sql_select( '
					select count(*) as content_count from ' . $te_object_name );
				if ( $content_count[0]['content_count'] )
				{
					metadata::$objects['TE_OBJECT']['fields']['WF_WORKFLOW_ID']['no_change'] = true;
					metadata::$objects['TE_OBJECT']['fields']['WF_WORKFLOW_ID']['disabled'] = true;
				}
			}
		}
		
		
		/**
		* Контролируем возможность смены блокировки таблицы
		*/
		
		public function adjust_is_unblockable_change ( $pk ) {
			$te_object_name = object_name::$te_object_names[$pk['TE_OBJECT_ID']]['SYSTEM_NAME'];
			
			if (!params::$params['lock_records']['value'] || metadata::$objects[$te_object_name]['type']!='table')
				metadata::$objects['TE_OBJECT']['fields']['IS_UNBLOCKABLE']["no_change"]=1;
		}
		
		/**
		 * Скрываем вывод поля "Цепочка публикаций" для таблиц, не участвующих в workflow
		 *
		 * @see table::get_index_records()
		 */
		public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
		{
			$records = $this -> call_parent( 'get_index_records', array( &$request, $mode, $list_mode, $include, $exclude ) );
			
			if ( count( $records ) && $mode != 'select2' )
				foreach ( $records as $record_index => $record_value )
					if ( !metadata::$objects[$record_value['SYSTEM_NAME']]['decorators']['workflow'] ||
							metadata::$objects[$record_value['SYSTEM_NAME']]['workflow_scope'] == 'block' )
						$records[$record_index]['WF_WORKFLOW_ID'] = '';
			
			return $records;
		}
		
		/**
		 * Кляуза для ограничения списка записей по параметру list_mode
		 *
		 */
		public function ext_index_by_list_mode($mode, $list_mode){
			list ($clause, $binds) = $this -> call_parent('ext_index_by_list_mode', array($mode, $list_mode));
			
			// не выводим внутренние таблицы
			if (!$list_mode['with_internals']) {
				$internals = array();
				foreach (metadata::$objects as $obj_name=>$obj) 
					if ($obj['type']=='internal_table') 
						$internals[]=$obj_name;
					
				if (sizeof($internals)) 
					$clause .= " AND SYSTEM_NAME NOT IN ('".implode("', '", $internals)."')";
			}
			return array($clause, $binds);
		}
		
	}
?>
