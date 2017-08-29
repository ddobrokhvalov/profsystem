<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Системные разделы"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class auth_system_section extends table_translate
{
	/**
	 * Устанавливаем значение поля "Тип раздела" таким же, как у родительской записи
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		if ( $raw_fields[$prefix . 'PARENT_ID'] )
		{
			$parent_section_type = db::sql_select( '
					select SECTION_TYPE from AUTH_SYSTEM_SECTION where AUTH_SYSTEM_SECTION_ID = :parent_id',
				array( 'parent_id' => $raw_fields[$prefix . 'PARENT_ID'] ) );
			
			$raw_fields[$prefix . 'SECTION_TYPE'] = $parent_section_type[0]['SECTION_TYPE'];
		}
		
		// Проверяем уникальность системного раздела
		if ( $raw_fields[$prefix . 'TE_OBJECT_ID'] )
			$this -> full_object -> check_section_unique( $raw_fields, $prefix );
		
		return $this -> call_parent( 'exec_add', array( $raw_fields, $prefix ) );
	}
	
	/**
	 * Устанавливаем значение поля "Тип раздела" таким же, как у родительской записи
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		if ( $raw_fields[$prefix . 'PARENT_ID'] )
		{
			$parent_section_type = db::sql_select( '
					select SECTION_TYPE from AUTH_SYSTEM_SECTION where AUTH_SYSTEM_SECTION_ID = :parent_id',
				array( 'parent_id' => $raw_fields[$prefix . 'PARENT_ID'] ) );
			
			$raw_fields[$prefix . 'SECTION_TYPE'] = $parent_section_type[0]['SECTION_TYPE'];
		}
		
		// Запрещаем менять тип раздела у записи, имеющей потомков
		$record = $this -> full_object -> get_change_record( $pk );
		$children = db::sql_select( 'select count(*) as COUNTER from AUTH_SYSTEM_SECTION where PARENT_ID = :parent_id',
			array( 'parent_id' => $pk['AUTH_SYSTEM_SECTION_ID'] ) );
		if ( $children[0]['COUNTER'] && ( $record['SECTION_TYPE'] != $raw_fields[$prefix . 'SECTION_TYPE'] ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_auth_system_section_change_type_deny'] . ': "' . $this -> full_object -> get_record_title( $pk ) . '" (' . $this -> primary_key -> pk_to_string( $pk ) . ')' );
		
		// Проверяем уникальность системного раздела
		if ( $raw_fields[$prefix . 'TE_OBJECT_ID'] )
			$this -> full_object -> check_section_unique( $raw_fields, $prefix, $pk );
		
		$this -> call_parent( 'exec_change', array( $raw_fields, $prefix, $pk ) );
	}
	
	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на системные разделы
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 * @todo Сделать агрегацию прав дочерними системными разделами
	 */
	public function ext_disabled_ACL_SS($primary_id){
		return $this->full_object->get_disabled_for_auth("auth_system_section")+auth::get_disabled_for_auth($primary_id, $this->index_records_in, "auth_system_section", "AUTH_SYSTEM_SECTION");
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на системные разделы с воркфлоу
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_SSWF($primary_id){
		return $this->full_object->get_disabled_for_auth("workflow")+auth::get_disabled_for_auth($primary_id, $this->index_records_in, "workflow", "AUTH_SYSTEM_SECTION");
	}
	
	/**
	 * Принудительное удаление связанных записей из таблицы AUTH_ACL
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete($pk, $partial=false){
		$this->call_parent('ext_finalize_delete', array($pk, $partial));
		auth::clear_AUTH_ACL( $pk['AUTH_SYSTEM_SECTION_ID'], 'AUTH_SYSTEM_SECTION' );
	}
	
	/**
	 * Выдача потушенных чекбоксов по типу разделения доступа
	 *
	 * Возвращает перечень потушенных системных разделов в таком же формате как auth::get_disabled_for_auth для указанного типа разделения доступа.
	 * Применяется для того, чтобы для разделения доступа по блокам были потушены воркфлововские системные разделы, а для разделения доступа по воркфлоу наоборот - были потушены системные разделы с разделением доступа по системным разделам
	 *
	 * param string $auth_mode	тип разделения доступа, который сейчас отрабатывается - "workflow" или "auth_system_section", то есть тушиться будyт системные разделы с противоположным вариантом разделения доступа
	 * @return array
	 */
	public function get_disabled_for_auth($auth_mode){
		// Получаем записи всех системных разделов, а также тех прав, которые нужно потушить. Причем это права совпадают с $auth_mode - ведь мы отображаем именно тот режим, что и в $auth_mode
		$system_sections=db::sql_select("
			SELECT AUTH_SYSTEM_SECTION.*, TE_OBJECT.SYSTEM_NAME, TE_OBJECT.WF_WORKFLOW_ID
			FROM AUTH_SYSTEM_SECTION
			LEFT JOIN TE_OBJECT ON TE_OBJECT.TE_OBJECT_ID = AUTH_SYSTEM_SECTION.TE_OBJECT_ID
			WHERE AUTH_SYSTEM_SECTION_ID IN ({$this->index_records_in})");
		$privileges=db::sql_select("SELECT AUTH_PRIVILEGE.* FROM AUTH_PRIVILEGE, AUTH_OBJECT_TYPE WHERE AUTH_OBJECT_TYPE.SYSTEM_NAME=:system_name AND AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID", array("system_name"=>$auth_mode));
		foreach($privileges as $privilege){
			$privileges_array[$privilege["AUTH_PRIVILEGE_ID"]]=0; // Массив с потушиваемыми привилегиями
		}
		
		if ( $auth_mode=="workflow" && $_REQUEST['_f_WF_WORKFLOW_ID'] != '' )
		{
			$default_workflow = db::sql_select( 'select WF_WORKFLOW_ID from WF_WORKFLOW where IS_DEFAULT = 1' );
			$filter_workflow = array_merge( array( $_REQUEST['_f_WF_WORKFLOW_ID'] ),
				( $_REQUEST['_f_WF_WORKFLOW_ID'] == $default_workflow[0]['WF_WORKFLOW_ID'] ? array( '0' ) : array() ) );
		}

		$disabled=array();
		foreach($system_sections as $system_section){
			// Если декоратор Воркфлоу есть и запрашиваются системные разделы ИЛИ декоратора Воркфлоу нет и запрашивается Воркфлоу, то тушим.
			// Также тушатся разделы с цепочками публикаций, не удовлетворяющими условия фильтрации
			if(
				(metadata::$objects[$system_section["SYSTEM_NAME"]]["decorators"]["workflow"] && $auth_mode=="auth_system_section") ||
				(!metadata::$objects[$system_section["SYSTEM_NAME"]]["decorators"]["workflow"] && $auth_mode=="workflow") ||
				(metadata::$objects[$system_section["SYSTEM_NAME"]]["decorators"]["workflow"] && $auth_mode=="workflow" &&
					$_REQUEST['_f_WF_WORKFLOW_ID'] != '' && !in_array( $system_section['WF_WORKFLOW_ID'], $filter_workflow ) )
			){
				$disabled[$system_section["AUTH_SYSTEM_SECTION_ID"]]=$privileges_array;
			}
		}
		return $disabled;
	}
	
	/**
	 * Возращает список доступных пользователю системных разделов
	 *
	 * @param string $system_section_type - тип системного раздела
	 * @return array
	 */
	public function get_allow_system_sections( $system_section_type )
	{
 		if ( $this -> auth -> is_main_admin )
 		{
 		 	// Получаем список всех системных разделов
			$system_section_allow_records = db::sql_select( "
		 		select AUTH_SYSTEM_SECTION_ID as OBJECT_ID from AUTH_SYSTEM_SECTION" );
	 	}
	 	else
	 	{
 		 	// Получаем список системных разделов, на которые данный пользователь имеет права
	 		list( $auth_tables, $auth_clause, $auth_binds ) =
	 			auth::get_auth_clause( $this -> auth -> user_roles_in, 'access', 'auth_system_section', 'AUTH_SYSTEM_SECTION' );
	 		
	 		$system_section_allow_records = db::sql_select( '
		 		select AUTH_ACL.OBJECT_ID from ' . $auth_tables . '	where ' . $auth_clause, $auth_binds );
	 	}
	 	
	 	$system_section_allow_records = lib::array_reindex( $system_section_allow_records, 'OBJECT_ID' );
	 	
	 	// Для раздела "Настройки" применяем особые правила отображения некоторых разделов
	 	if ( $system_section_type == 'settings' )
	 	{
			// Получаем идентификатор "Избранного" и идентификатор его родительского раздела
			$favourite_records = db::sql_select( "
		 		select AUTH_SYSTEM_SECTION.AUTH_SYSTEM_SECTION_ID, AUTH_SYSTEM_SECTION.PARENT_ID
				from AUTH_SYSTEM_SECTION, TE_OBJECT
				where AUTH_SYSTEM_SECTION.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID and
					TE_OBJECT.SYSTEM_NAME = 'FAVOURITE'" );
			
		 	if ( count( $favourite_records ) && !params::$params['client_mode']['value'] )
				$system_section_allow_records[$favourite_records[0]['AUTH_SYSTEM_SECTION_ID']] =
					array( 'OBJECT_ID' => $favourite_records[0]['AUTH_SYSTEM_SECTION_ID'] );
		 	else
		 	{
		 		unset( $system_section_allow_records[$favourite_records[0]['AUTH_SYSTEM_SECTION_ID']] );
		 		unset( $system_section_allow_records[$favourite_records[0]['PARENT_ID']] );
		 	}
		 	
			// Получаем идентификатор "Административного интерфейса" и идентификатор его родительского раздела
			$params_records = db::sql_select( "
		 		select AUTH_SYSTEM_SECTION.AUTH_SYSTEM_SECTION_ID, AUTH_SYSTEM_SECTION.PARENT_ID
				from AUTH_SYSTEM_SECTION, TE_OBJECT
				where AUTH_SYSTEM_SECTION.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID and
					TE_OBJECT.SYSTEM_NAME = 'SYSTEM_AUTH_USER_PARAMS_TOOL'" );
			
		 	if ( count( $params_records ) && params::$params['params_access']['value'] )
				$system_section_allow_records[$params_records[0]['AUTH_SYSTEM_SECTION_ID']] =
					array( 'OBJECT_ID' => $params_records[0]['AUTH_SYSTEM_SECTION_ID'] );
		 	else
		 	{
		 		unset( $system_section_allow_records[$params_records[0]['AUTH_SYSTEM_SECTION_ID']] );
		 		unset( $system_section_allow_records[$params_records[0]['PARENT_ID']] );
		 	}
		 	
			// Получаем идентификатор "Личных данных"
			$personal_records = db::sql_select( "
		 		select AUTH_SYSTEM_SECTION.AUTH_SYSTEM_SECTION_ID
				from AUTH_SYSTEM_SECTION, TE_OBJECT
				where AUTH_SYSTEM_SECTION.TE_OBJECT_ID = TE_OBJECT.TE_OBJECT_ID and
					TE_OBJECT.SYSTEM_NAME = 'PERSONAL_INFO_TOOL'" );
			
			if ( count( $personal_records ) )
				$system_section_allow_records[$personal_records[0]['AUTH_SYSTEM_SECTION_ID']] =
					array( 'OBJECT_ID' => $personal_records[0]['AUTH_SYSTEM_SECTION_ID'] );
		}
		
		// Добавляем к списку разрешенных разделов их родителей
		$system_section_allow_records_in = lib::array_make_in( $system_section_allow_records, 'OBJECT_ID' );
		$parent_allow_records = db::sql_select( "
	 		select distinct PARENT_ID from AUTH_SYSTEM_SECTION
			where AUTH_SYSTEM_SECTION_ID in ( {$system_section_allow_records_in} )" );
		
		$system_section_show_records = $system_section_allow_records;
		foreach ( $parent_allow_records as $parent_allow_record )
			if ( !isset( $system_section_allow_records[$parent_allow_record['PARENT_ID']] ) )
				$system_section_show_records[$parent_allow_record['PARENT_ID']] =
					array( 'OBJECT_ID' => $parent_allow_record['PARENT_ID'] );
		
 		// Получаем список системных разделов заданного типа с учетом полученных ранее прав
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$this -> ext_field_selection( 'TITLE', 1 );
		
		$system_section_show_records_in = lib::array_make_in( $system_section_show_records, 'OBJECT_ID' );
 		$system_section_records = db::replace_field(db::sql_select( "
 				select
 					AUTH_SYSTEM_SECTION.*, " . $dec_field . " as \"_TITLE\", TE_OBJECT.SYSTEM_NAME AS \"_SYSTEM_NAME\"
 				from AUTH_SYSTEM_SECTION
 					left join TE_OBJECT on TE_OBJECT.TE_OBJECT_ID = AUTH_SYSTEM_SECTION.TE_OBJECT_ID
 				" . $dec_join[0] . "
				where AUTH_SYSTEM_SECTION.SECTION_TYPE = :section_type and
					AUTH_SYSTEM_SECTION.AUTH_SYSTEM_SECTION_ID in ( {$system_section_show_records_in} )
				order by AUTH_SYSTEM_SECTION.SECTION_ORDER",
			array( 'section_type' => $system_section_type ) + $dec_binds ), array('TITLE', 'SYSTEM_NAME'), array('_TITLE', '_SYSTEM_NAME'));
			
		
		// Объекты, на которые нет прав, отображаем без возможности перехода по ссылке
		foreach ( $system_section_records as $system_section_index => $system_section_record )
			if ( !isset( $system_section_allow_records[$system_section_record['AUTH_SYSTEM_SECTION_ID']] ) )
				$system_section_records[$system_section_index]['SYSTEM_NAME'] = '';
		
		return $system_section_records;
	}
	
	/**
	 * Проверяет уникальность системного раздела с учетом параметров объекта
	 *
	 * @param array $raw_fields	Сырые данные, например, $_REQUEST
	 * @param string $prefix	Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param array $pk			Первичный ключ, который определяет изменяемую запись
	 */
	public function check_section_unique( $raw_fields, $prefix, $pk = '' )
	{
		$exception_message = $this -> te_object_name . ( !$pk ? ' (' . metadata::$lang['lang_adding'] . ')' : '' ) . ': ' .
			metadata::$lang['lang_auth_system_section_not_unique'] . ( $pk ? ': "' . $this -> full_object -> get_record_title( $pk ) . '" (' .
				$this -> primary_key -> pk_to_string( $pk ) . ')' : '' );
	
		if ( $raw_fields[$prefix . 'TE_OBJECT_ID'] == object_name::$te_object_ids['INF_BLOCK']['TE_OBJECT_ID'] )
			throw new Exception( $exception_message );
		
		if ( params::$params['install_cms']['value'] )
		{
			$prg_modules = db::sql_select( 'select count(*) as COUNTER from PRG_MODULE where SYSTEM_NAME = :system_name and IS_ELEMENTS = 1',
				array( 'system_name' => object_name::$te_object_names[$raw_fields[$prefix . 'TE_OBJECT_ID']]['SYSTEM_NAME'] ) );
			if ( $prg_modules[0]['COUNTER'] > 0 )
				throw new Exception( $exception_message );
		}
		
		$sections = db::sql_select( 'select * from AUTH_SYSTEM_SECTION
				where TE_OBJECT_ID = :te_object_id and AUTH_SYSTEM_SECTION_ID <> :pk_value',
			array( 'te_object_id' => $raw_fields[$prefix . 'TE_OBJECT_ID'],
				'pk_value' => ( $pk ? $pk['AUTH_SYSTEM_SECTION_ID'] : '' ) ) );
		
		$raw_object_params = $raw_fields[$prefix . 'OBJECT_PARAM'] ? explode( '&', $raw_fields[$prefix . 'OBJECT_PARAM'] ) : array();
		foreach ( $sections as $section )
		{
			$object_params = $section['OBJECT_PARAM'] ? explode( '&', $section['OBJECT_PARAM'] ) : array();
			if ( ( count( $object_params ) == 0 && count( $raw_object_params ) == 0 ) ||
					count( array_intersect( $object_params, $raw_object_params ) ) > 0 )
				throw new Exception( $exception_message );
		}
	}
	
	/**
	 * Управляем видимостью поля "Тип раздела"
	 *
	 * @see table::html_card()
	 */
	public function html_card( $mode, &$request )
	{
		list( $title, $html ) = $this -> call_parent( 'html_card', array( $mode, &$request ) );
		
		$form_name = html_element::get_form_name();
		
		$html .= <<<HTM
<script type="text/javascript">
	var oParentSelect = document.forms['{$form_name}']['_form_PARENT_ID'];
	var oSectionTypeSelect = document.forms['{$form_name}']['_form_SECTION_TYPE'];
	var oSectionTypeRow = document.getElementById( '_form_SECTION_TYPE' );
	
	addListener( oParentSelect, 'change', checkParent ); checkParent();
	
	function checkParent()
	{
		if ( oParentSelect.options[ oParentSelect.selectedIndex ].value == '0' ) {
			oSectionTypeRow.style.display = ''; oSectionTypeSelect.setAttribute( 'lang', '_nonempty_' );
		} else {
			oSectionTypeRow.style.display = 'none'; oSectionTypeSelect.setAttribute( 'lang', '' );
		}
	}
</script>
HTM;
		return array( $title, $html );
	}
	
	/**
	 * Осуществляем фильтрацию по цепочке публикаций
	 */
	public function ext_index_by_list_mode( $mode, $list_mode )
	{
		list( $where, $binds ) = $this -> call_parent( 'ext_index_by_list_mode', array( $mode, $list_mode ) );
		
		// Фильтр по цепочке публикаций
		if ( $list_mode['by_workflow_id'] )
		{
			// Выделяем из метаданных идентификаторы объектов с workflow_scope != block
			$block_workflow_objects = array();
			foreach ( metadata::$objects as $obj => $object )
				if ( $object['decorators']['workflow'] && $object['workflow_scope'] != 'block' )
					$block_workflow_objects[] = object_name::$te_object_ids[$obj]['TE_OBJECT_ID'];
			$block_workflow_objects_in = lib::array_make_in( $block_workflow_objects );
			
			$where .= '
				and AUTH_SYSTEM_SECTION.TE_OBJECT_ID in (
					select TE_OBJECT.TE_OBJECT_ID from TE_OBJECT
						where TE_OBJECT.TE_OBJECT_ID in ( ' . $block_workflow_objects_in . ' )
							and TE_OBJECT.WF_WORKFLOW_ID = :te_object_workflow_id ) ';
			
			$binds['te_object_workflow_id'] = $list_mode['by_workflow_id'];
		}
		
		return array($where, $binds);
	}
}
?>
