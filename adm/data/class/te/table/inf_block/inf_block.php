<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Информационные блоки"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo Нужно сделать возможность добавлять блок через раскладку страниц. При этом надо решить, можно ли в составе CMS добавлять блоки из списка блоков, ведь вне CMS это единственный способ добавления блоков
 */
class inf_block extends table
{
	/**
	 * При добавлении блока выставляем все права на него добавившему пользователю (если у него нет более общих прав)
	 * @todo Когда мы решим сделать полную поддержку немодульных таблиц, нужно будет достроить правильным образом этот метод
	 */
	public function exec_add($raw_fields, $prefix){
		// Сейчас мы пока не разрешаем добавлять блоки в немодульные блочные таблицы
		$module=db::sql_select("SELECT * FROM PRG_MODULE WHERE PRG_MODULE_ID=:prg_module_id", array("prg_module_id"=>$raw_fields[$prefix."PRG_MODULE_ID"]));
		if(!$module[0]["SYSTEM_NAME"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_block_without_module"]);
		}
		if ( isset( metadata::$objects[$module[0]["SYSTEM_NAME"]]['decorators']['workflow'] ) &&
				metadata::$objects[$module[0]["SYSTEM_NAME"]]['workflow_scope'] == 'block' )
			self::check_workflow_id($raw_fields[$prefix.'WF_WORKFLOW_ID'], $module[0]["SYSTEM_NAME"]);
		// Собственно добавление
		$new_block_id=parent::exec_add($raw_fields, $prefix);
		// Если у администратора до такой степени недостаточно собственных прав, что нужно использовать индивидуальную роль, то добавляем ему права
		$auth_system_role_obj = object::factory("AUTH_SYSTEM_ROLE");
		$ind_role_id=$auth_system_role_obj->get_ind_role($raw_fields[$prefix."SITE_ID"]);
		$auth_system_role_obj -> __destruct();
		
		if($ind_role_id){
			// Если есть декоратор Воркфлоу, то применяем разделение доступа Воркфлоу, а если нет, то считаем, что это разделение доступа по блокам (в том числе, если это и блок модуля, не имеющего контента)
			$aot=(metadata::$objects[$module[0]["SYSTEM_NAME"]]["decorators"]["workflow"] ? "workflow" : "inf_block");
			$privs=db::sql_select("
				SELECT AUTH_PRIVILEGE.*, AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_TABLE_ID
				FROM AUTH_PRIVILEGE, AUTH_OBJECT_TYPE, AUTH_OBJECT_TYPE_TABLE
				WHERE AUTH_OBJECT_TYPE.SYSTEM_NAME=:aot
					AND AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID=AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID
					AND AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID
					AND AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:table_system_name
			", array("aot"=>$aot, "table_system_name"=>"INF_BLOCK"));
			foreach($privs as $priv){
				db::insert_record("AUTH_ACL", array("OBJECT_ID"=>$new_block_id, "AUTH_SYSTEM_ROLE_ID"=>$ind_role_id, "AUTH_PRIVILEGE_ID"=>$priv["AUTH_PRIVILEGE_ID"], "AUTH_OBJECT_TYPE_TABLE_ID"=>$priv["AUTH_OBJECT_TYPE_TABLE_ID"]));
			}
		}
		// Выставляем идентификатор объекта для блока (кроме, конечно, модулей без контента)
		db::update_record("INF_BLOCK", array("TE_OBJECT_ID"=>(int)object_name::$te_object_ids[$module[0]["SYSTEM_NAME"]]["TE_OBJECT_ID"]), "", array("INF_BLOCK_ID"=>$new_block_id));
		return $new_block_id;
	}

	/**
	 * При изменении блока позволяем менять сайт только тем, у кого есть такие права
	 */
	public function exec_change($raw_fields, $prefix, $pk){
		$this->full_object->adjust_site_id_change($pk);
		$this->full_object->adjust_workflow_id_change($pk);
		$inf_block=$this->full_object->get_change_record($pk);
		$module_system_name = object_name::$te_object_names[$inf_block['TE_OBJECT_ID']]['SYSTEM_NAME'];
		if ( isset( metadata::$objects[$module_system_name]['decorators']['workflow'] ) &&
				metadata::$objects[$module_system_name]['workflow_scope'] == 'block' )
			self::check_workflow_id($raw_fields[$prefix.'WF_WORKFLOW_ID'], $module_system_name);
		parent::exec_change($raw_fields, $prefix, $pk);
	}

	/**
	 * Удаление кэша блока с файловой системы
	 *
	 * @param array $pk			Первичный ключ блока, у которого нужно очистить кэш
	 * @todo Нужно ли перед удалением кэша проверять права пользователя на данный блок? Сейчас проверяются на всякий случай.
	 */
	public function exec_delete_cache($pk){
		$this->primary_key->is_record_exists($pk, true);
		$this->full_object->is_permitted_to("change", $pk, true);
		filesystem::rm_r(params::$params["common_data_server"]["value"]."block_cache/block{$pk["INF_BLOCK_ID"]}");
	}

	/**
	 * Ограничиваем выборку модулем, который ловится из $_REQUEST["obj"], либо типом разделения доступа - inf_block или workflow
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=parent::ext_index_by_list_mode($mode, $list_mode);
		// По модулю
		if($list_mode["by_module"] || $list_mode["by_module_name"]){
			list($m2m_where, $m2m_binds)=self::get_where_and_binds_by_module( $list_mode["by_module_name"] ? $list_mode["by_module_name"] : $_REQUEST["obj"] );
			$where.=$m2m_where;
			$binds=array_merge($binds, $m2m_binds);
			
			if ( $list_mode["only_one"] )
			{
				// Ограничиваем выборку только блоками, не имеющими контента
				$where .= '
					and INF_BLOCK.INF_BLOCK_ID in (
						select INF_BLOCK.INF_BLOCK_ID from INF_BLOCK
						left join CONTENT_MAP on CONTENT_MAP.INF_BLOCK_ID = INF_BLOCK.INF_BLOCK_ID
						where INF_BLOCK.PRG_MODULE_ID = :prg_module_id_only_one
						and CONTENT_MAP.CONTENT_ID is null ) ';
				$binds = array_merge( $binds, array( 'prg_module_id_only_one' => $m2m_binds['prg_module_id'] ) );
			}
		// По типу разделения доступа. Причем блоки модулей без контента всегда имеют тип разделения доступа "по блокам", то есть никакого воркфлоу на них быть не может
		}elseif($list_mode["by_auth_object"]){
			// Выбираем идентификаторы нужных объектов
			foreach(metadata::$objects as $obj=>$object){
				if($list_mode["by_auth_object"]=="inf_block" && $object["decorators"]["block"] && !$object["decorators"]["workflow"]){
					$system_names[]="'{$obj}'";
				}elseif($list_mode["by_auth_object"]=="workflow" && $object["decorators"]["workflow"]){
					$system_names[]="'{$obj}'";
				}
			}
			// Собираем кляузу
			$in=(is_array($system_names) ? join(", ", $system_names) : "''");
			$subwhere=" INF_BLOCK.TE_OBJECT_ID IN (SELECT TE_OBJECT_ID FROM TE_OBJECT WHERE SYSTEM_NAME IN ({$in})) ";
			// Дополняем кляузу блоками модулей без контента, если надо
			if($list_mode["by_auth_object"]=="inf_block"){
				$subwhere=" AND (INF_BLOCK.TE_OBJECT_ID=0 OR {$subwhere})";
			}else{
				$subwhere=" AND {$subwhere}";
			}
			$where.=$subwhere;
		}
		
		// Фильтр по цепочке публикаций
		if ( $list_mode['by_workflow_id'] )
		{
			// Выделяем из метаданных идентификаторы объектов с workflow_scope = block
			$block_workflow_objects = array();
			foreach ( metadata::$objects as $obj => $object )
				if ( $object['decorators']['block'] && $object['decorators']['workflow'] && $object['workflow_scope'] == 'block' )
					$block_workflow_objects[] = object_name::$te_object_ids[$obj]['TE_OBJECT_ID'];
			$block_workflow_objects_in = lib::array_make_in( $block_workflow_objects );
			
			// Для объектов с workflow_scope = block и workflow_scope = table запросы отличаются
			$where .= '
				and ( INF_BLOCK.INF_BLOCK_ID in (
					select INF_BLOCK.INF_BLOCK_ID from INF_BLOCK
						where INF_BLOCK.TE_OBJECT_ID in ( ' . $block_workflow_objects_in . ' )
							and INF_BLOCK.WF_WORKFLOW_ID = :inf_block_workflow_id ) or 
				INF_BLOCK.INF_BLOCK_ID in (
					select INF_BLOCK.INF_BLOCK_ID from INF_BLOCK, TE_OBJECT
						where TE_OBJECT.TE_OBJECT_ID = INF_BLOCK.TE_OBJECT_ID
							and INF_BLOCK.TE_OBJECT_ID not in ( ' . $block_workflow_objects_in . ' )
							and TE_OBJECT.WF_WORKFLOW_ID = :te_object_workflow_id ) ) ';
			
			$binds['inf_block_workflow_id'] = $binds['te_object_workflow_id'] = $list_mode['by_workflow_id'];
		}
		
		// Фильтр по правам
		if ( $list_mode['use_rights'] )
		{
			list($auth_where, $auth_binds)=$this->full_object->get_auth_clause_and_binds();
			$where.=$auth_where;
			$binds=array_merge($binds, $auth_binds);
		}
		
		return array($where, $binds);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Проверяет существование указанного блока для модуля указанного объекта. В случае неуспеха вызывает исключение, если $throw_exception
	 *
	 * @param int $inf_block_id			идентификатор проверяемого блока
	 * @param string $obj				системное имя объекта
	 * @param boolean $throw_exception	бросать ли исключение в случае неуспеха
	 * @return boolean
	 */
	public static function is_valid_block($inf_block_id, $obj, $throw_exception=false){
		list($where, $binds)=self::get_where_and_binds_by_module($obj);
		$counter=db::sql_select("SELECT COUNT(*) AS COUNTER FROM INF_BLOCK WHERE INF_BLOCK_ID=:inf_block_id {$where}", array_merge($binds, array("inf_block_id"=>$inf_block_id)));
		if($counter[0]["COUNTER"]>0){
			return true;
		}elseif($throw_exception){
			throw new Exception(metadata::$objects["INF_BLOCK"]["title"].": ".metadata::$lang["lang_inf_block_not_found"].": ({$inf_block_id}), ".metadata::$lang["lang_object"].": \"{$obj}\"");
		}else{
			return false;
		}
	}

	/**
	 * Возвращает ссылку на контент для указанного блока и системного названия таблицы, проверка на существование блока НЕ делается,
	 * так как подразумевается использование этого метода в цикле. НЕ использует класс {@link url}, так как метод статический
	 *
	 * @param int $inf_block_id					идентификатор блока
	 * @param string $te_object_system_name		системное имя таблицы
	 * @return string
	 */
	public static function get_link_to_content($inf_block_id, $te_object_system_name, $action = '' ){
		// Ссылку формируем только в том случае, если есть таблица с названием, соответствующим системным названием
		if(isset(metadata::$objects[$te_object_system_name])){
			$link="index.php?obj={$te_object_system_name}&_f_INF_BLOCK_ID={$inf_block_id}" . ( $action ? "&action=" . $action : "" );
		}
		return $link;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Запрещаем добавление блоков из списка
	 */
	public function action_index(){
		metadata::$objects['INF_BLOCK']['no_add'] = true;
		parent::action_index();
	}

	/**
	 * Значение по умолчанию для времени кэширования выставляем таким же, как соответствующий параметр системы (если это поле есть в таблице)
	 */
	public function action_add(){
		throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_block_create_from_list"]);
	}

	/**
	 * Контролируем по правам возможность смены сайта у блока
	 */
	public function action_change(){
		$pk=$this->primary_key->get_from_request();
		$this->full_object->adjust_site_id_change($pk);
		$this->full_object->adjust_workflow_id_change($pk);
		parent::action_change();
	}

	/**
	 * Действие - удаление кэша
	 */
	public function action_delete_cache(){
		if($pk=$this->primary_key->get_from_request()){
			$this->full_object->exec_delete_cache($pk);
		}
		$this->url->redirect();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем в списке колонки "Контент" и "Очистка кэша"
	 * @todo Ссылка очистки кэша должна выводиться только в случае наличия в системе слоя CMS (то же касается и очистки всего кэша)
	 */
	public function ext_index_header($mode){
		return array(
			"link_to_content"=>array("title"=>metadata::$lang["lang_content"], "type"=>"_link"),
			"delete_cache"=>array("title"=>metadata::$lang["lang_delete_cache"], "type"=>"_link"),
		);
	}

	/**
	 * Помещаем ссылки на контент в операции, а также ссылки на очистку кэша
	 */
	public function get_index_ops($record){
		$ops=parent::get_index_ops($record);
		$ops=array_merge(array("delete_cache"=>array("url"=>$this->url->get_url("delete_cache", array("pk"=>$this->primary_key->get_from_record($record))))), $ops);
		if($link_to_content=self::get_link_to_content($record["INF_BLOCK_ID"], $record["SYSTEM_NAME"])){
			$ops=array_merge(array("link_to_content"=>array("url"=>$link_to_content)), $ops);
		}
		return $ops;
	}
	
	/**
	 * Подготовка списка операций над записями
	 * 
	 * @return array
	 */
	public function get_index_operations()
	{
		$operations = parent::get_index_operations();
		
		if( $this -> auth -> is_main_admin )
			$operations['delete_all_cache'] = array( 'name' => 'delete_all_cache', 'alt' => metadata::$lang['lang_delete_all_cache'],
				'url' => $this -> url -> get_url( 'distributed' ) . '&do_op=delete_all_cache' );
		
		return $operations;
	}
	
	/**
	 * Временно подменяем $request для особого случая - фильтра по цепочке публикаций
	 *
	 * @see table::get_index_counter()
	 */
	public function get_index_counter( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		// Если установлен фильтр по цепочке публикаций, вырезаем его из реквеста
		if ( $workflow_id = $request['_f_WF_WORKFLOW_ID'] )
		{
			unset( $request['_f_WF_WORKFLOW_ID'] ); $list_mode['by_workflow_id'] = $workflow_id;
		}
		
		$counter = parent::get_index_counter( $request, $mode, $list_mode, $include, $exclude );
		
		if ( $workflow_id ) $request['_f_WF_WORKFLOW_ID'] = $workflow_id;
		
		return $counter;
	}
	
	/**
	 * Временно подменяем $request для особого случая - фильтра по цепочке публикаций
	 * Скрываем вывод поля "Цепочка публикаций" для блоков, не участвующих в workflow
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		// Если установлен фильтр по цепочке публикаций, вырезаем его из реквеста
		if ( $workflow_id = $request['_f_WF_WORKFLOW_ID'] )
		{
			unset( $request['_f_WF_WORKFLOW_ID'] ); $list_mode['by_workflow_id'] = $workflow_id;
		}
		
		$records = parent::get_index_records( $request, $mode, $list_mode, $include, $exclude );
		
		if ( $workflow_id ) $request['_f_WF_WORKFLOW_ID'] = $workflow_id;
		
		if ( count( $records ) && $mode != 'select2' )
		{
			$prg_modules = db::sql_select( 'select PRG_MODULE_ID, SYSTEM_NAME from PRG_MODULE where PRG_MODULE_ID in ( ' .
				lib::array_make_in( array_keys( lib::array_reindex( $records, '_PRG_MODULE_ID' ) ) ) . ' )' );
			$prg_modules = lib::array_reindex( $prg_modules, 'PRG_MODULE_ID' );
			
			foreach ( $records as $record_index => $record_value )
			{
				$system_name = $prg_modules[$record_value['_PRG_MODULE_ID']]['SYSTEM_NAME'];
				if ( isset( metadata::$objects[$system_name] ) )
				{
					$records[$record_index]['SYSTEM_NAME'] = $system_name;
					if ( !metadata::$objects[$system_name]['decorators']['workflow'] ||
							metadata::$objects[$system_name]['workflow_scope'] != 'block' )
						$records[$record_index]['WF_WORKFLOW_ID'] = '';
				}
			}
		}
		
		return $records;
	}
	
	/**
	 * Определяет модуль для указанного объекта и возвращает кляузу WHERE для ограничения выборки блоков этим модулем, а также переменные привязки
	 *
	 * @param string $obj		системное название объекта
	 * @return array
	 * @todo Когда будем прикручивать полноценную поддержку таблиц с блоками, нужно будет обеспечить возможность выбора не только модулей, но и немодульных блочных таблиц
	 */
	static protected function get_where_and_binds_by_module($obj){
		$binds=array();
		$module=db::sql_select("SELECT PRG_MODULE_ID FROM PRG_MODULE WHERE SYSTEM_NAME=:obj", array("obj"=>$obj));
		if($module[0]["PRG_MODULE_ID"]){
			$where=" AND INF_BLOCK.PRG_MODULE_ID=:prg_module_id ";
			$binds["prg_module_id"]=$module[0]["PRG_MODULE_ID"];
		}else{
			$where=" AND 1=0 ";
		}
		return array($where, $binds);
	}

	/**
	 * Получение потушенных чекбоксов для привязки записей к блокам
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (контентной)
	 * @return array
	 * @todo Переменные привязки в IN
	 */
	public function ext_disabled_CONTENT_MAP($primary_id){
		$disabled=db::sql_select("SELECT * FROM CONTENT_MAP WHERE CONTENT_ID=:primary_id AND INF_BLOCK_ID IN ({$this->index_records_in}) AND IS_MAIN=1", array("primary_id"=>$primary_id));
		foreach($disabled as $dis){
			$r_disabled[$dis["INF_BLOCK_ID"]]=1;
		}
		return $r_disabled;
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на блоки
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_IB($primary_id){
		return auth::get_disabled_for_auth($primary_id, $this->index_records_in, "inf_block", "INF_BLOCK");
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на блоки с воркфлоу
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_IBWF($primary_id){
		return auth::get_disabled_for_auth($primary_id, $this->index_records_in, "workflow", "INF_BLOCK");
	}
	
	/**
	 * Принудительное удаление связанных записей из таблицы AUTH_ACL
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete($pk, $partial=false){
		parent::ext_finalize_delete($pk, $partial);
		auth::clear_AUTH_ACL( $pk['INF_BLOCK_ID'], 'INF_BLOCK' );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Возвращает кляузу для выборки только тех записей, которые можно смотреть данному пользователю
	 *
	 * @return array
	 */
	public function get_auth_clause_and_binds(){
		if($this->auth->is_main_admin){ // Для главного администратора ограничений нет
			return array("", array());
		}else{
			// Собираем информацию по правам для воркфлоу
			$any_priv=db::sql_select("SELECT AUTH_PRIVILEGE.SYSTEM_NAME FROM AUTH_PRIVILEGE, AUTH_OBJECT_TYPE WHERE AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:aot_system_name", array("aot_system_name"=>"workflow"));
			$any_priv=(count($any_priv)>0 ? array_keys(lib::array_group($any_priv, "SYSTEM_NAME")) : "");
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $any_priv, "workflow", "INF_BLOCK");
			// Подкляуза для администраторов сайтов
			if($this->auth->sites_in){
				$site_clause=" UNION SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in})";
			}
			// Добавляем к переменным привязки постфикс "_workflow"
			$workflow_clause = preg_replace( '/(\:[a-z0-9_]+)/i', '$1_workflow', "SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM {$auth_tables} WHERE {$auth_clause} $site_clause" );
			foreach ( $auth_binds as $auth_bind_name => $auth_bind_value )
				$workflow_binds[$auth_bind_name . '_workflow'] = $auth_bind_value;
			
			// Собираем информацию по правам для простого разделения доступа по блокам
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, "access", "inf_block", "INF_BLOCK");
			// Подкляуза для администраторов сайтов
			if($this->auth->sites_in){
				$site_clause=" UNION SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in})";
			}
			// Добавляем к переменным привязки постфикс "_block"
			$block_clause = preg_replace( '/(\:[a-z0-9_]+)/i', '$1_block', "SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM {$auth_tables} WHERE {$auth_clause} $site_clause" );
			foreach ( $auth_binds as $auth_bind_name => $auth_bind_value )
				$block_binds[$auth_bind_name . '_block'] = $auth_bind_value;
			
			return array( " and ( INF_BLOCK.INF_BLOCK_ID in ( {$workflow_clause} ) or INF_BLOCK.INF_BLOCK_ID in ( {$block_clause} ) ) ", $workflow_binds + $block_binds );
		}
	}

	/**
	 * В таблицу блоков разрешаем смотреть всем. Те, у кого нет прав просто ничего не увидят, а добавлять новые блоки можно только в случае наличия системного права "add_block"
	 */
	public function is_permitted_to($ep_type, $pk="", $throw_exception=false){
		if($this->auth->is_main_admin || $ep_type=="view"){ // В таблицу блоков разрешаем смотреть всем. Те, у кого нет прав просто ничего не увидят - см. get_auth_clause_and_binds()
			$is_permitted=true;
		}elseif($ep_type=="add"){ // Добавлять новые блоки можно только в случае наличия системного права "add_block"
			$is_permitted=auth::get_system_privilege($this->auth->user_roles_in, "add_block");
		}else{ // Все прочее разрешается только для владельцев прав на соответствующий блок
			$is_permitted=auth::is_site_admin_for("INF_BLOCK", $pk["INF_BLOCK_ID"]);
			// Если по администратору сайта ничего не получилось, то проверяем обычным образом
			if(!$is_permitted){
				list($te_object_system_name, $auth_system_name, $privs)=$this->full_object->get_auth_info($pk);
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $privs, $auth_system_name, "INF_BLOCK");
				$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables} WHERE {$auth_clause} AND INF_BLOCK.INF_BLOCK_ID=:inf_block_id", array_merge(array("inf_block_id"=>$pk["INF_BLOCK_ID"]), $auth_binds));
				$is_permitted=(bool)$rights[0]["COUNTER"];
			}
		}
		if(!$is_permitted && $throw_exception){
			// Название записи в сообщении не выводится по соображениям защиты данных
			$pk_message=($pk[$this->autoinc_name] ? ": (".$this->primary_key->pk_to_string($pk).")" : "");
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_operation_not_permitted_".$ep_type].$pk_message);
		}
		return $is_permitted;
	}

	/**
	 * Любые операции разрешаем только в том случае, если есть права на доступ к соответствующим блокам
	 *
	 * @see object::is_permitted_to_mass()
	 * @todo Дистинкт применяется, надо понаблюдать за производительностью
	 */
	public function is_permitted_to_mass($ep_type, $ids=array(), $throw_exception=false){
		$not_allowed=array(); $allowed=array();
		if($this->auth->is_main_admin){
			// Можно все
		}else{
			// Определяем к какому типу разделения доступа относятся рассматриваемые блоки и формируем частные массивы по этому признаку
			$in=(is_array($ids) && count($ids)>0 ? join(", ", $ids) : 0);
			$te_objects=lib::array_reindex(db::sql_select("SELECT INF_BLOCK.INF_BLOCK_ID, TE_OBJECT.SYSTEM_NAME FROM INF_BLOCK, TE_OBJECT WHERE INF_BLOCK.INF_BLOCK_ID IN ({$in}) AND INF_BLOCK.TE_OBJECT_ID=TE_OBJECT.TE_OBJECT_ID"), "INF_BLOCK_ID");
			foreach($ids as $id){
				if(metadata::$objects[$te_objects[$id]["SYSTEM_NAME"]]["decorators"]["workflow"]){
					$workflow_ids[]=$id;
				}else{
					$block_ids[]=$id;
				}
			}
		// Собираем информацию по правам для воркфлоу
			$any_priv=db::sql_select("SELECT AUTH_PRIVILEGE.SYSTEM_NAME FROM AUTH_PRIVILEGE, AUTH_OBJECT_TYPE WHERE AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:aot_system_name", array("aot_system_name"=>"workflow"));
			$any_priv=(count($any_priv)>0 ? array_keys(lib::array_group($any_priv, "SYSTEM_NAME")) : "");
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $any_priv, "workflow", "INF_BLOCK");
			$in=(is_array($workflow_ids) ? join(", ", $workflow_ids) : 0);
			// Подкляуза для администраторов сайтов
			if($this->auth->sites_in){
				$site_clause=" UNION SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in}) AND INF_BLOCK.INF_BLOCK_ID IN ({$in})";
			}
			$workflow_rights=db::sql_select("SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID AS OBJECT_ID FROM {$auth_tables} WHERE ({$auth_clause} AND AUTH_ACL.OBJECT_ID IN ({$in})) $site_clause", $auth_binds);
		// Собираем информацию по правам для простого разделения доступа по блокам
			list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, "access", "inf_block", "INF_BLOCK");
			$in=(is_array($block_ids) ? join(", ", $block_ids) : 0);
			// Подкляуза для администраторов сайтов
			if($this->auth->sites_in){
				$site_clause=" UNION SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID FROM INF_BLOCK WHERE INF_BLOCK.SITE_ID IN ({$this->auth->sites_in}) AND INF_BLOCK.INF_BLOCK_ID IN ({$in})";
			}
			$block_rights=db::sql_select("SELECT DISTINCT INF_BLOCK.INF_BLOCK_ID AS OBJECT_ID FROM {$auth_tables} WHERE ({$auth_clause} AND AUTH_ACL.OBJECT_ID IN ({$in})) $site_clause", $auth_binds);
		// Складываем разрешенные идентификаторы и готовим репорт как обычно
			$rights=array_merge($workflow_rights, $block_rights);
			$not_allowed=$this->full_object->is_permitted_to_mass_report($ids, $rights, "OBJECT_ID");
			// для случая с $throw_exception
			$is_permitted=!(bool)count($not_allowed);
			if(!$is_permitted && $throw_exception){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_uncheckable_mass_operation_not_permitted_".$ep_type].": ".join(", ", $not_allowed));
			}
		}
		return $not_allowed;
	}

	/**
	 * Возвращает системное имя таблицы контента блока, системное имя типа разделения доступа по блоку и права на блок
	 *
	 * Права на блок собираются по правилам для auth::get_auth_clause() и безотносительно прав текущего пользователя - то есть возвращает все права, какие есть в данном типе разделения доступа. 
	 * Элементарная операция сюда не передается намеренно - потому что любые права на конкретный блок проверяются одинаково - в случае отсутствия 
	 * воркфлоу, это access, а в случае наличия - это любые права на соответствующий объект, то есть по view для данного объекта
	 *
	 * @param string $auth_system_name			Системное имя типа разделения доступа
	 * @return string
	 */
	public function get_auth_info($pk){
		$block=$this->full_object->get_change_record($pk);
		$te_object_system_name=object_name::$te_object_names[$block["TE_OBJECT_ID"]]["SYSTEM_NAME"];
		$auth_system_name=(metadata::$objects[$te_object_system_name]["decorators"]["workflow"] ? "workflow" : "inf_block");
		if($auth_system_name=="inf_block"){
			$privs="access";
		}elseif($auth_system_name=="workflow"){
			$obj = object::factory($te_object_system_name);
			$privs=$obj->get_privs("view", array());
			$obj -> __destruct();
		}
		return array($te_object_system_name, $auth_system_name, $privs);
	}

	/**
	 * Контролируем по правам возможность смены сайта у блока (если это поле есть в таблице)
	 *
	 * Если у пользователя есть полные права на блок (то есть все роли воркфлоу), то можно
	 *
	 * @param array $pk			Первичный ключ блока
	 * @metadatamod
	 */
	public function adjust_site_id_change($pk){
		if(is_array(metadata::$objects["INF_BLOCK"]["fields"]["SITE_ID"])){
			list($te_object_system_name, $auth_system_name, $privs)=$this->full_object->get_auth_info($pk);
			if($auth_system_name=="workflow" && !$this->auth->is_main_admin){
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->auth->user_roles_in, $privs, $auth_system_name, "INF_BLOCK");
				$rights=db::sql_select("SELECT COUNT(*) AS COUNTER FROM {$auth_tables} WHERE {$auth_clause} AND INF_BLOCK.INF_BLOCK_ID=:inf_block_id", array_merge(array("inf_block_id"=>$pk["INF_BLOCK_ID"]), $auth_binds));
				if($rights[0]["COUNTER"]!=count($privs) || !is_array($privs)){
					metadata::$objects["INF_BLOCK"]["fields"]["SITE_ID"]["no_change"]=1;
				}
			}
		}
	}
	
	/**
	 * Контролируем возможность смены цепочки публикаций
	 *
	 * @param array $pk			Первичный ключ блока
	 * @metadatamod
	 */
	public function adjust_workflow_id_change( $pk )
	{
		$inf_block = $this -> full_object -> get_change_record( $pk );
		$te_object_name = object_name::$te_object_names[$inf_block['TE_OBJECT_ID']]['SYSTEM_NAME'];
		if ( !metadata::$objects[$te_object_name]['decorators']['workflow'] ||
				metadata::$objects[$te_object_name]['workflow_scope'] != 'block' )
		{
			metadata::$objects['INF_BLOCK']['fields']['WF_WORKFLOW_ID']['no_change'] = true;
		}
		else
		{
			$content_count = db::sql_select( '
				select count(*) as content_count from CONTENT_MAP where INF_BLOCK_ID = :INF_BLOCK_ID',
					array( 'INF_BLOCK_ID' => $pk['INF_BLOCK_ID'] ) );
			if ( $content_count[0]['content_count'] )
			{
				metadata::$objects['INF_BLOCK']['fields']['WF_WORKFLOW_ID']['no_change'] = true;
				metadata::$objects['INF_BLOCK']['fields']['WF_WORKFLOW_ID']['disabled'] = true;
			}
		}
	}
	
	/**
	 * Проверяем возможность назначения цепочки публикаций
	 * Метод статический, так как используется в том числе из te_object.php
	 *
	 * @param int $wf_workflow_id		Идентификатор цепочки публикаций
	 * @param string $te_object_name	Системное имя таблицы
	 */
	public static function check_workflow_id( $wf_workflow_id, $te_object_name )
	{
		$wf_workflow_record = object::factory( 'WF_WORKFLOW' ) -> get_change_record( array( 'WF_WORKFLOW_ID' => $wf_workflow_id ), true );
		
		if ( $wf_workflow_record['WORKFLOW_TYPE'] == 'dont_use_versions' &&
				metadata::$objects[$te_object_name]['decorators']['version'] )
			throw new Exception( self::$te_object_ids[$te_object_name]['TITLE'] . ': ' . metadata::$lang['lang_error_workflow_version'] );
		
		$wf_edge_state_langs = db::sql_select( "
				select WF_EDGE_STATE.LANG_ID from WF_EDGE_STATE, WF_STATE
				where WF_EDGE_STATE.WF_STATE_ID = WF_STATE.WF_STATE_ID and
					WF_STATE.WF_WORKFLOW_ID = :WF_WORKFLOW_ID and WF_EDGE_STATE.EDGE_TYPE = 'new'",
			array( 'WF_WORKFLOW_ID' => $wf_workflow_id ) );
		
		$wf_edge_state_no_lang_exist = $wf_edge_state_with_lang_exist = false;
		foreach ( $wf_edge_state_langs as $wf_edge_state_lang )
		{
			if ( $wf_edge_state_lang['LANG_ID'] == '0' )
				$wf_edge_state_no_lang_exist = true;
			else
				$wf_edge_state_with_lang_exist = true;
		}
		
		if ( !$wf_edge_state_with_lang_exist &&
				metadata::$objects[$te_object_name]['decorators']['lang'] )
			throw new Exception( self::$te_object_ids[$te_object_name]['TITLE'] . ': ' . metadata::$lang['lang_error_workflow_with_lang'] );
		
		if ( !$wf_edge_state_no_lang_exist &&
				!metadata::$objects[$te_object_name]['decorators']['lang'] )
			throw new Exception( self::$te_object_ids[$te_object_name]['TITLE'] . ': ' . metadata::$lang['lang_error_workflow_no_lang'] );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Информация о распределенной операции "Очистка всего кэша"
	 *
	 * @return array
	 */
	public function delete_all_cache_info(){
		return array("title"=>metadata::$lang["lang_cache_deleting"], "total"=>-1, "for_once"=>100,  "success_message" => metadata::$lang["lang_elements_of_cache_deleted"] );
	}

	/**
	 * Список записей для распределенной операции "Очистка всего кэша"
	 *
	 * @param array $status		информация об операции
	 * @param int $from			первая запись в текущей итерации
	 * @param int $offset		сколько записей вынимать в текущей итерации
	 * @return array
	 */
	public function delete_all_cache_list( $status, $from, $offset )
	{
		if ( !$this -> auth -> is_main_admin )
			return array();
		
		//  Получаем список содержимое каталога block_cache
		$bc_dirs = filesystem::ls_r( params::$params['common_data_server']['value'] . 'block_cache/', true, true );
		
		// Удаляем из списка содержимого каталога файлы и недоступные для удаления каталоги
		foreach ( $bc_dirs as $dir_id => $dir_item )
			if ( !is_dir( $dir_item['name'] ) || !is_writeable( $dir_item['name'] ) )
				unset( $bc_dirs[$dir_id] );
		
		// Выбираем из списка каталогов первые $offset записей
		$records = array_slice( array_values( $bc_dirs ), 0, $offset );
		
		return $records;
	}

	/**
	 * Выполнение распределенной операции "Очистка всего кэша"
	 *
	 * @param array $status		информация об операции
	 * @param array $dir		информация об удаляемом каталоге
	 */
	public function delete_all_cache_item( $dir, $status )
	{
		filesystem::rm_r( $dir['name'] );
	}
	
	/**
	 * Удаление всего кэша блоков с файловой системы
	 */
	public static function delete_all_cache()
	{
		//  Получаем список содержимое каталога block_cache
		$bc_dirs = filesystem::ls_r( params::$params['common_data_server']['value'] . 'block_cache/', true, true );
		
		// Удаляем с файловой системы каталоги, которые возможно удалить
		foreach ( $bc_dirs as $dir_id => $dir_item )
			if ( is_dir( $dir_item['name'] ) && is_writeable( $dir_item['name'] ) )
				filesystem::rm_r( $dir_item['name'] );
	}
	
	/**
	* В информации для экспорта идентификаторы модуля и объекта заменяются на их системные названия
	*	
	* @todo Заменить static на более глобальное решение?	
	* @todo Красивее назвать, что это такое ID - а в нем не ID, а имя??? см. param_value
	*/
	
	public function get_export_field_values($pk) {
		static $modules_by_id;
		if (!isset($modules_by_id)) 
			$modules_by_id = lib::array_reindex(db::sql_select('SELECT * FROM PRG_MODULE'), 'PRG_MODULE_ID');
			
		$fields = parent::get_export_field_values($pk);
		
		if ($fields['TE_OBJECT_ID'][0]['value'] && object_name::$te_object_names[$fields['TE_OBJECT_ID'][0]['value']]['SYSTEM_NAME'])
			$fields['TE_OBJECT_ID'][0]['value'] = object_name::$te_object_names[$fields['TE_OBJECT_ID'][0]['value']]['SYSTEM_NAME'];
			
		
		if ($fields['PRG_MODULE_ID'][0]['value'] && $modules_by_id[$fields['PRG_MODULE_ID'][0]['value']]['SYSTEM_NAME']) 
			$fields['PRG_MODULE_ID'][0]['value'] = $modules_by_id[$fields['PRG_MODULE_ID'][0]['value']]['SYSTEM_NAME'];

		return $fields;
	}
	
	/**
	* Метод импорта данных из XML - унаследованный метод от table
	* В случае если блок связан с модулем, не имеющим собственных данных - его необходимо заменять первым попавшимся соответствующим
	* @param array $xml_arr массив данных одной записи, возвращаемый ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array карта соответствия между id записей из файла импорта и реальными
	*/
	
	public function import_from_xml ($xml_arr, &$import_data) {
		$field_values = $this->full_object->get_import_field_values($xml_arr['children'], $import_data);
		if (!$field_values['TE_OBJECT_ID']) {
			// если данных нет, то нужно найти первый попавшийся блок данного модуля и его связать
			$inf_block_data = db::sql_select('SELECT INF_BLOCK_ID FROM INF_BLOCK WHERE PRG_MODULE_ID=:prg_module_id LIMIT 1', array('prg_module_id'=>$field_values['PRG_MODULE_ID']));
			if (sizeof($inf_block_data)) {
				return array($xml_arr['attributes']['RECORD_ID']=>$inf_block_data[0]['INF_BLOCK_ID']);
			}
		}
		
		return parent::import_from_xml($xml_arr, $import_data);
	}
	

	/**
	* Возвращает поля для вставки в таблицу в процессе импорта - унаследованный метод от table
	* Дополняет функционал указанием сайта раздела под который происходит импорт
	* @param array $main_children данные обо всех потомках массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return array Подготовленные данные для вставки в таблицу
	*/

	public function get_import_field_values($main_children, &$import_data) {
	
		$field_values = parent::get_import_field_values($main_children, $import_data);
		$field_values['SITE_ID']=$import_data['root_page_info']['SITE_ID'];
		
		
		if ($field_values['TE_OBJECT_ID']) {
			// присваиваем соответствующее workflow, 
			$te_object_name = object_name::$te_object_names[$field_values['TE_OBJECT_ID']]['SYSTEM_NAME'];
			if ( !metadata::$objects[$te_object_name]['decorators']['workflow'] ||
					metadata::$objects[$te_object_name]['workflow_scope'] != 'block' )
			{
				// 1) если воркфлоу относится к объекту, то присваивается воркфлоу объекта
				$field_values['WF_WORKFLOW_ID'] = object_name::$te_object_names[$field_values['TE_OBJECT_ID']]['WF_WORKFLOW_ID'];
			}
			else {
				// 2) если воркфлоу относится к блоку и было изменено воркфлоу, то присваиваем выбранное пользователем
				
				if (($import_data['_f_workflow_change'][$field_values['WF_WORKFLOW_ID']]) && ((int)$import_data['_f_workflow_change'][$field_values['WF_WORKFLOW_ID']]>0)) 
					$field_values['WF_WORKFLOW_ID'] = $import_data['_f_workflow_change'][$field_values['WF_WORKFLOW_ID']];
			}
			
			if (!$field_values['WF_WORKFLOW_ID'] && (metadata::$objects[$te_object_name]['workflow_scope'] == 'block')) {
				// 3) Иначе присваиваем цепочку по умолчанию
						$field_values['WF_WORKFLOW_ID'] = $this->get_default_workflow_id();
						if (!$field_values['WF_WORKFLOW_ID'])
							throw new Exception(metadata::$lang['lang_autotest_check_workflow_no_default_workflows_presented']);
			}
		}

		return $field_values;
	}
	

	/**
	* Возвращает значение для конкретного поля для вставки в таблицу - унаследованный метод от table
	* Дополнительно заменяет TE_OBJECT_ID, PRG_MODULE_ID на соотв. значения для целевой системы и дополняет название блока префиксом
	* В случае если был заменен WF_WORKFLOW_ID, заменяет его на новое значение
	* @param string $field_name Название поля
	* @param array $field_children Данные обо всех потомках данного поля массива записи, возвращаемой ExpatXMLParser
	* @param array $import_data - общие данные, используемые процессом импорта @see import_process
	* @return mixed Значение, которое вставляется в БД (еще возможно подменить, @see get_import_field_values)
	* @todo Заменить static на более глобальное решение?	
	*/

	public function get_import_field_value($field_name, $field_children, &$import_data) {
		$value = parent::get_import_field_value($field_name, $field_children, $import_data);

		static $modules_by_sn;
		if (!isset($modules_by_sn)) 
			$modules_by_sn = lib::array_reindex(db::sql_select('SELECT * FROM PRG_MODULE'), 'SYSTEM_NAME');

		if (($field_name=='TE_OBJECT_ID') && $value)
			$value = object_name::$te_object_ids[$value]['TE_OBJECT_ID'];
		elseif ($field_name=='PRG_MODULE_ID') 
			$value = $modules_by_sn[$value]['PRG_MODULE_ID'];
		elseif ($field_name=='TITLE') 
			$value=$import_data['_f_prefix_for_templates'][ $import_data['langs_in_admin'][ params::$params['default_interface_lang']['value'] ]['LANG_ID'] ].$value;
		/*elseif ($field_name=='WF_WORKFLOW_ID') 
			if (($import_data['_f_workflow_change'][$value]) && ((int)$import_data['_f_workflow_change'][$value]>0)) 
				$value=$import_data['_f_workflow_change'][$value];*/

		return $value;
	}
	
	/**
	* Необходимо кроме блока удалить весь контент
	*/
	public function import_undo ($content_id) {
		// сначала удаляем весь контент, связанный с этим блоком
		$this->delete_all_content(array('INF_BLOCK_ID'=>$content_id));
		
		parent::import_undo($content_id);
	}
	
	/**
	* Удаление всего контента, связанного с блоком
	*/
	
	private function delete_all_content ($pk) {
		$inf_block = $this -> full_object -> get_change_record( $pk );
		if ($inf_block['TE_OBJECT_ID']) {
			$mapped = db::sql_select('SELECT * FROM CONTENT_MAP WHERE INF_BLOCK_ID=:inf_block_id', array('inf_block_id'=>$pk['INF_BLOCK_ID']));
			if (sizeof($mapped)) {
				$te_object_name = self::$te_object_names[$inf_block['TE_OBJECT_ID']]['SYSTEM_NAME'];
				$obj = object::factory($te_object_name);
				foreach($mapped as $map_content) {
					// заставляем удалять все каскадно!
					// можно потом не восстанавливать, потому что вызывается тольо из import_undo, не связанным с пользовательским интерфейсом
					// если будет по другому ИСПРАВИТЬ!
					
					if(is_array(metadata::$objects[$this->obj]["links"]))
						foreach(array_keys(metadata::$objects[$this->obj]["links"]) as $link_name)
							metadata::$objects[$this->obj]["links"][$link_name]['on_delete_cascade']=true;
					
					if (in_array('lang', metadata::$objects[$te_object_name]['decorators'])) {
						// получаем все версии записи и удаляем
						$content = $obj -> get_other_langs(array($obj->autoinc_name=>$map_content['CONTENT_ID'], 'LANG_ID'=>0));
						if (sizeof($content)) 
							foreach ($content as $record) 
								$obj->exec_delete($obj->primary_key->get_from_record($record));
					}
					else 
						$obj->exec_delete(array($obj->autoinc_name=>$map_content['CONTENT_ID']));
				}
			}
			db::sql_query('DELETE FROM CONTENT_MAP WHERE INF_BLOCK_ID=:inf_block_id', array('inf_block_id'=>$pk['INF_BLOCK_ID']));
		}
	}
	
	/**
	* Информационный блок может разблокировать так же администратор сайта
	* @see table::is_checkinout_admin
	* @return boolean
	*/
	
	public function is_checkinout_admin() {
		$pk = $this->primary_key->get_from_request();
		return parent::is_checkinout_admin() || auth::is_site_admin_for($this->obj, $pk[$this->autoinc_name]);
	}
	
}
?>
