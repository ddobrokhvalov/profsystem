<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Резолюции"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class wf_resolution extends table_translate {
	/**
	 * Перед вызовом базового метода происходит дополнительная проверка на недублирование резолюций
	 *
	 * @see table::exec_add()
	 */
	public function exec_add( $raw_fields, $prefix )
	{
		$this -> is_resolution_exists( $raw_fields, $prefix );
		$this->full_object->check_wf_privs($raw_fields, $prefix);
		$id=parent::exec_add( $raw_fields, $prefix );
		$this->full_object->set_wf_privs($raw_fields, $prefix, array("WF_RESOLUTION_ID"=>$id));
		return $id;
	}
	
	/**
	 * Перед вызовом базового метода происходит дополнительная проверка на недублирование резолюций
	 *
	 * @see table::exec_change()
	 */
	public function exec_change( $raw_fields, $prefix, $pk )
	{
		$this -> is_resolution_exists( $raw_fields, $prefix, $pk['WF_RESOLUTION_ID'] );
		$this->full_object->check_wf_privs($raw_fields, $prefix);
		parent::exec_change( $raw_fields, $prefix, $pk );
		$this->full_object->set_wf_privs($raw_fields, $prefix, $pk);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Добавляем поля с правами цепочки публикации
	 */
	public function action_add(){
		$this->full_object->add_wf_privs();
		parent::action_add();
	}

	/**
	 * Добавляем поля с правами цепочки публикации
	 */
	public function action_change(){
		$this->full_object->add_wf_privs();
		parent::action_change();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Ограничиваем список возможных резолюций различными ограничениями, которые нужны для Воркфлоу
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=$this -> call_parent( 'ext_index_by_list_mode', array($mode, $list_mode) );
		// Ограничение пользователем (выдача всех резолюций, на которые есть права у пользователя). Главный администратор не ограничивается
		// Должен использоваться совместно с $list_mode["obj"], иначе система не поймет, на какой объект нужно вычислять права и решит, что прав нет
		// В случае воркфлоу по блокам нужно указывать также $list_mode["inf_block_id"], чтобы можно было вычислить права на блок
		// Совместно с пользователем и идентификатором может указываться $list_mode["parallel_restriction"], чтобы не выводить те параллельные резолюции,
		// права на которые у текущего пользователя уже исчерпаны. В этом случае должен быть указан и язык записи - $list_mode["lang_id"], чтобы правильно выбрать текущую резолюцию
		if($list_mode["AUTH_USER_ID"] && !$this->auth->is_main_admin){
			// Подготовка компонентов, если блоки есть
			if(metadata::$objects[$list_mode["obj"]]["decorators"]["block"]){
				$table_system_name="INF_BLOCK";
				$acl_obj_clause=":inf_block_id";
				$acl_obj_binds=array("inf_block_id"=>$list_mode["inf_block_id"]);
				// Если пользователь вдруг оказался администратором сайта для текущей записи, то не ограничиваем список его резолюций
				$no_restriction=$this->auth->sites_in && auth::is_site_admin_for("INF_BLOCK", $list_mode["inf_block_id"]);
			// Подготовка компонентов, если блоков нет
			}else{
				$table_system_name="AUTH_SYSTEM_SECTION";
				$acl_obj_clause="( SELECT AUTH_SYSTEM_SECTION_ID FROM AUTH_SYSTEM_SECTION, TE_OBJECT WHERE TE_OBJECT.TE_OBJECT_ID = AUTH_SYSTEM_SECTION.TE_OBJECT_ID and TE_OBJECT.SYSTEM_NAME = :obj )";
				$acl_obj_binds=array("obj"=>$list_mode["obj"]);
			}
			// Непосредственно формируем данные (если надо)
			if(!$no_restriction){
				// Невывод параллельных резолюций, на которые уже были наложены права текущего пользователя
				if($list_mode["parallel_restriction"] && $list_mode["autoinc_value"]){
					$current_resolution=db::sql_select("SELECT WF_RESOLUTION_ID FROM {$list_mode["obj"]} WHERE {$list_mode["autoinc_name"]}=:id".($list_mode["lang_id"] ? " AND LANG_ID=:lang_id" : ""), array("id"=>$list_mode["autoinc_value"])+($list_mode["lang_id"] ? array("lang_id"=>$list_mode["lang_id"]) : array()));
					if($current_resolution[0]["WF_RESOLUTION_ID"]){
							$parallel_clause="
							AND EXISTS (
								SELECT 1 FROM WF_PRIVILEGE_RESOLUTION
								WHERE 
									WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
									AND WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID=WF_RESOLUTION.WF_RESOLUTION_ID AND
									(
										WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID NOT IN(
											SELECT AUTH_PRIVILEGE_ID FROM WF_APPROVED
												WHERE WF_APPROVED.CONTENT_ID=:par_id
													AND WF_APPROVED.LANG_ID=:par_lang_id
													AND WF_APPROVED.TE_OBJECT_ID=:par_te_object_id
													AND AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID=WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID
											)
										
									OR
										WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID<>:par_res_id
									)
						)";
					$binds+=array("par_id"=>$list_mode["autoinc_value"], "par_lang_id"=>$list_mode["lang_id"], "par_te_object_id"=>object_name::$te_object_ids[$list_mode["obj"]]["TE_OBJECT_ID"], "par_res_id"=>$current_resolution[0]["WF_RESOLUTION_ID"]);
					}
				}
				// Сообственно ограничение по правам
				$where.="
					AND WF_RESOLUTION.WF_RESOLUTION_ID IN (
						SELECT WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID
						FROM AUTH_PRIVILEGE, AUTH_ACL, AUTH_OBJECT_TYPE, AUTH_OBJECT_TYPE_TABLE, WF_PRIVILEGE_RESOLUTION
						WHERE AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN ({$this->auth->user_roles_in})
							AND AUTH_ACL.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
							AND AUTH_ACL.OBJECT_ID={$acl_obj_clause}
							AND AUTH_ACL.AUTH_OBJECT_TYPE_TABLE_ID=AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_TABLE_ID
							AND AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:table_system_name
							AND AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
							AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:system_name
							AND AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
							AND AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID=WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID
							{$parallel_clause}
					)";
				$binds+=array("table_system_name"=>$table_system_name, "system_name"=>"workflow")+$acl_obj_binds;
			}
		}
		// Ограничение исходным состоянием. isset нужен для того, чтобы корректно отработать случай, когда запись порушена таким образом, что у нее не заполнено поле WF_STATE_ID. Может быть передан массив исходных состояний
		if(isset($list_mode["FIRST_STATE_ID"])){
			if(is_array($list_mode["FIRST_STATE_ID"])){
				$where.=" AND WF_RESOLUTION.FIRST_STATE_ID IN (".join(", ", $list_mode["FIRST_STATE_ID"]).")";
			}else{
				$where.=" AND WF_RESOLUTION.FIRST_STATE_ID=:first_state_id";
				$binds+=array("first_state_id"=>$list_mode["FIRST_STATE_ID"]);
			}
		}
		// Невывод резолюций перевода (чтобы не терялся смысл объектов без языков)
		if($list_mode["no_lang"]){
			$where.=" AND WF_RESOLUTION.LANG_ID=0";
		}
		// Вывод только тех резолюций перевода, на языках которых еще нет версий записи
		// Должен использоваться совместно с $list_mode["obj"], $list_mode["autoinc_name"], $list_mode["autoinc_value"], в противном случае не подключается
		if($list_mode["check_langs"] && $list_mode["autoinc_value"]){
			$where.="
				AND (WF_RESOLUTION.LANG_ID=0 OR NOT EXISTS (
					SELECT 1 FROM {$list_mode["obj"]}
					WHERE {$list_mode["obj"]}.{$list_mode["autoinc_name"]}={$list_mode["autoinc_value"]}
						AND {$list_mode["obj"]}.LANG_ID=WF_RESOLUTION.LANG_ID
			))";
		}
		// Невывод резолюций, которые ведут из состояния с рабочей версией в состояние с рабочей же версией (чтобы не терялся смысл объектов без версий)
		if($list_mode["no_version"]){
			$where.="
				AND WF_RESOLUTION.WF_RESOLUTION_ID NOT IN (
					SELECT WF_RESOLUTION.WF_RESOLUTION_ID
					FROM WF_RESOLUTION, WF_STATE WF_STATE1, WF_STATE WF_STATE2
					WHERE WF_RESOLUTION.FIRST_STATE_ID=WF_STATE1.WF_STATE_ID AND WF_STATE1.VERSIONS='two_versions'
						AND WF_RESOLUTION.LAST_STATE_ID=WF_STATE2.WF_STATE_ID AND WF_STATE2.VERSIONS='two_versions'
			)";
		}
		// Невывод резолюций удаления
		if($list_mode["no_deleted"]){
			$where.="
					AND WF_RESOLUTION.LAST_STATE_ID NOT IN (
						SELECT WF_STATE.WF_STATE_ID FROM WF_STATE, WF_EDGE_STATE
						WHERE WF_STATE.WF_STATE_ID = WF_EDGE_STATE.WF_STATE_ID
							AND WF_EDGE_STATE.EDGE_TYPE = 'deleted')";
		}
		// Ограничение по идентификатору цепочки побликаций
		if($list_mode["WF_WORKFLOW_ID"]){
			$where.="
					AND WF_RESOLUTION.FIRST_STATE_ID IN (
						SELECT WF_STATE.WF_STATE_ID FROM WF_STATE WHERE WF_STATE.WF_WORKFLOW_ID = :wf_workflow_id )";
			$binds+=array("wf_workflow_id"=>$list_mode["WF_WORKFLOW_ID"]);
		}
		return array($where, $binds);
	}

	/**
	 * Добавляем ограничение списка записей резолюций цепочкой публикации через начальное состояние резолюции
	 * 
	 * @see table::get_index_query_components()
	 */
	public function get_index_query_components(&$request, $mode, $list_mode){
		// формат переменной $components - array($fields, $joins, $where, $binds)
		$components=$this->inner_object->get_index_query_components($request, $mode, $list_mode);
		if($request["_f_WF_WORKFLOW_ID"]){
			$components[1].="
				INNER JOIN WF_STATE STATE_BY_WF ON 
					STATE_BY_WF.WF_STATE_ID=WF_RESOLUTION.FIRST_STATE_ID AND
					STATE_BY_WF.WF_WORKFLOW_ID=:wf_workflow_id
				";
			// Добавляем переменную привязки с блоком из фильтра или из $list_mode
			$components[3]["wf_workflow_id"]=$request["_f_WF_WORKFLOW_ID"];
		}
		return $components;
	}
	
	/**
	 * Для формы группового наложения резолюций дополняем список начальным и конечным состоянием
	 *
	 * @see table::get_index_records()
	 */
	public function get_index_records( &$request, $mode, $list_mode, $include = 0, $exclude = array() )
	{
		$records = $this -> call_parent( 'get_index_records', array( &$request, $mode, $list_mode, $include, $exclude ) );
		
		if ( $list_mode['with_state'] )
		{
			$wf_state = object::factory( 'WF_STATE' );
			$wf_state_list = $wf_state -> get_index_records( $request, 'select2', '' );
			$wf_state -> __destruct();
			
			$wf_state_list = lib::array_reindex( $wf_state_list, 'WF_STATE_ID' );
			$wf_resolution_list = lib::array_reindex( db::sql_select( 'select * from WF_RESOLUTION' ), 'WF_RESOLUTION_ID' );
			
			foreach ( $records as $record_index => $record_item )
			{
				$records[$record_index]['_TITLE'] = $record_item['_TITLE'] . ' (' .
					$wf_state_list[$wf_resolution_list[$record_item['WF_RESOLUTION_ID']]['FIRST_STATE_ID']]['_TITLE'] . ' -> ' .
					$wf_state_list[$wf_resolution_list[$record_item['WF_RESOLUTION_ID']]['LAST_STATE_ID']]['_TITLE'] . ')';
			}
		}
		
		return $records;
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Метод бросает исключение, если резолюция с заданными параметрами существует
	 *
	 * @param array $raw_fields		Сырые данные, например, $_REQUEST
	 * @param string $prefix		Префикс, которым дополнены сырые данные
	 * @param int $wf_resolution_id	Идентификатор существующей резолюции
	 * 	 
	 * @return bool
	 */
	public function is_resolution_exists( $raw_fields, $prefix, $wf_resolution_id = '' )
	{
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			$this -> ext_field_selection( 'TITLE', 1 );
		
		// Ищем разолюцию, аналогичную нашей
		$resolution = db::sql_select('
			select
				' . $dec_field . ' as TITLE, WF_RESOLUTION_ID
			from
				WF_RESOLUTION ' . $dec_join[0] . '
			where
				FIRST_STATE_ID = :first_state_id and
				LAST_STATE_ID = :last_state_id and
				MAIN_VERSION = :main_version and
				WF_RESOLUTION_ID <> :wf_resolution_id',
			array(
				'first_state_id' => $raw_fields[$prefix.'FIRST_STATE_ID'],
				'last_state_id' => $raw_fields[$prefix.'LAST_STATE_ID'],
				'main_version' => $raw_fields[$prefix.'MAIN_VERSION'],
				'wf_resolution_id' => $wf_resolution_id ) + $dec_binds );
		
		// Если такая резолюция найдена, сравниваем наборы прав 
		if ( count( $resolution ) > 0 )
		{
			$resolution_privs = db::sql_select( 'select * from WF_PRIVILEGE_RESOLUTION
					where WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID = :wf_resolution_id',
				array( 'wf_resolution_id' => $resolution[0]['WF_RESOLUTION_ID'] ) );
			$resolution_privs = array_keys( lib::array_reindex( $resolution_privs, 'AUTH_PRIVILEGE_ID' ) );
			
			$privs = array();
			foreach( $raw_fields as $key => $value )
				if ( preg_match( "/^{$prefix}AUTH_PRIVILEGE_(\d+)$/", $key, $matches ) && $value )
					$privs[] = $matches[1];
			
			// Если и наборы прав совпадают, бросаем исключение
			if ( !count( array_diff( $privs, $resolution_privs ) ) )
				throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_resolution_exists'] . ': "' . htmlspecialchars( $resolution[0]['TITLE'] ) . '" (' . $resolution[0]['WF_RESOLUTION_ID'] . ')' );
		}
		
		return true;
	}
	
	/**
	 * Заполняем и выводим в браузер массив исходных состояний
	 *
	 * @see table::html_card()
	 */
	public function html_card($mode, &$request){
		$states = db::sql_select( 'select * from WF_STATE' );
		$state_versions_array = array(); $state_workflow_array = array();
		foreach ( $states as $state )
		{
			$state_versions_array[] = "'{$state['WF_STATE_ID']}': '{$state['VERSIONS']}'";
			$state_workflow_array[] = "'{$state['WF_STATE_ID']}': '{$state['WF_WORKFLOW_ID']}'";
		}
		$state_versions_array_str = join( ', ', $state_versions_array );
		$state_workflow_array_str = join( ', ', $state_workflow_array );
		
		list( $title, $html ) = $this -> call_parent( 'html_card', array( $mode, &$request ) );
		
		$form_name = html_element::get_form_name();
		
		$html .= <<<HTM
<script type="text/javascript">
	var oForm = document.forms['{$form_name}'];
	
	var aStateWorkflow = { {$state_workflow_array_str} };
	var aStateVersions = { {$state_versions_array_str} };
	var aStateMainVersion = { 'no_version': [ '2' ], 'one_version': [ '2' ], 'test_version': [ '2' ], 'two_versions': [ '0', '1', '2' ] };
	
	var oWorkflowSelect = oForm['_form_WF_WORKFLOW_ID'];
	var oFirstStateSelect = oForm['_form_FIRST_STATE_ID'];
	var oLastStateSelect = oForm['_form_LAST_STATE_ID'];
	var oVersionSelect = oForm['_form_MAIN_VERSION'];
	
	if ( oWorkflowSelect && oFirstStateSelect && oLastStateSelect && oVersionSelect )
	{
		var aStates = new Array();
		for ( var i = 0; i < oFirstStateSelect.options.length; i++ )
			if ( oFirstStateSelect.options[i].value )
				aStates[oFirstStateSelect.options[i].value] = oFirstStateSelect.options[i].innerHTML;
		
		var aVersions = new Array();
		for ( var i = 0; i < oVersionSelect.options.length; i++ )
			if ( oVersionSelect.options[i].value )
				aVersions[oVersionSelect.options[i].value] = oVersionSelect.options[i].innerHTML;
		
		addListener( oWorkflowSelect, 'change', checkFirstState );
		addListener( oFirstStateSelect, 'change', checkMainVersion );
		
		checkFirstState();
	}
	
	// Заполнение селектов состояний          
	function checkFirstState()
	{
		var iFirstStateSelected = oFirstStateSelect.options[oFirstStateSelect.selectedIndex].value;
		var iLastStateSelected = oLastStateSelect.options[oLastStateSelect.selectedIndex].value;
	
		while ( oFirstStateSelect.firstChild )
			oFirstStateSelect.removeChild( oFirstStateSelect.firstChild );
		while ( oLastStateSelect.firstChild )
			oLastStateSelect.removeChild( oLastStateSelect.firstChild );
		
		var iWorkflow = oWorkflowSelect.options[oWorkflowSelect.selectedIndex].value;
		
		oFirstStateSelect.options[0] = new Option();
		oLastStateSelect.options[0] = new Option();
		for ( var iState in aStates )
		{
			if ( aStateWorkflow[iState] && aStateWorkflow[iState] == iWorkflow )
			{
				oFirstStateSelect.options[oFirstStateSelect.options.length] =
					new Option( aStates[iState], iState );
				oLastStateSelect.options[oLastStateSelect.options.length] =
					new Option( aStates[iState], iState );
			}
		}
		
		for ( var i = 0; i < oFirstStateSelect.options.length; i++ )
			if ( oFirstStateSelect.options[i].value == iFirstStateSelected )
				oFirstStateSelect.selectedIndex = i;
		for ( var i = 0; i < oLastStateSelect.options.length; i++ )
			if ( oLastStateSelect.options[i].value == iLastStateSelected )
				oLastStateSelect.selectedIndex = i;
		
		checkMainVersion();
	}
	
	// Заполнение селекта "Главная версия"          
	function checkMainVersion()
	{
		var iVersionSelected = oVersionSelect.options[oVersionSelect.selectedIndex].value;
		
		while ( oVersionSelect.firstChild )
			oVersionSelect.removeChild( oVersionSelect.firstChild );
		
		var iState = oFirstStateSelect.options[oFirstStateSelect.selectedIndex].value;
		
		oVersionSelect.options[0] = new Option();
		if ( aStateMainVersion[aStateVersions[iState]] )
			for ( var iMainVersion in aStateMainVersion[aStateVersions[iState]] )
				oVersionSelect.options[oVersionSelect.options.length] =
					new Option( aVersions[aStateMainVersion[aStateVersions[iState]][iMainVersion]],
						aStateMainVersion[aStateVersions[iState]][iMainVersion] );

		for ( var i = 0; i < oVersionSelect.options.length; i++ )
			if ( oVersionSelect.options[i].value == iVersionSelected )
				oVersionSelect.selectedIndex = i;
	}
	
	// Проверяет корректность выставленных прав и кворума          
	CheckForm.validate_ext = function()
	{
		var aPrivCheckboxes = new Array();
		
		for ( var i = 0; i < oForm.elements.length; i++ )
			if ( oForm.elements[i].type == 'checkbox' )
				if ( oMatch = oForm.elements[i].name.match( /^_form_AUTH_PRIVILEGE_(\d+)$/i ) )
					aPrivCheckboxes[oMatch[1]] = oForm.elements[i];
		
		var priv_exists = 0;
		for ( var sCheckboxName in aPrivCheckboxes )
			priv_exists += aPrivCheckboxes[sCheckboxName].checked ? 1 : 0;
		
		if ( !priv_exists )
		{
			alert( Dictionary.translate( 'lang_wf_resolution_no_privs' ) );
			return false;
		}
		
		var quorum = parseInt( oForm['_form_QUORUM'].value );
		
		if ( priv_exists > 1 && ( isNaN( quorum ) || quorum < 1 || quorum > priv_exists ) )
		{
			alert( Dictionary.translate( 'lang_wf_resolution_bad_quorum' ) );
			try { oForm['_form_QUORUM'].focus() } catch (e) {};
			return false;
		}
		
		return true;
	}
</script>
HTM;
		return array( $title, $html );
	}

	/**
	 * Возвращает запись для формы ее редактирования
	 *
	 * Дополняем запись резолюции идентификатором воркфлоу, чтобы иметь возможность использовать это значение без дополнительных запросов.
	 * А так же дополняем назначенными для утверждения правами цепочки публикации.
	 */
	public function get_change_record($pk, $throw_exception=false){
		$record=parent::get_change_record($pk, $throw_exception);
		// Идентификатор воркфлоу
		$workflow=db::sql_select("SELECT WF_STATE.WF_WORKFLOW_ID FROM WF_STATE WHERE WF_STATE_ID=:state_id", array("state_id"=>$record["FIRST_STATE_ID"]));
		$record["WF_WORKFLOW_ID"]=$workflow[0]["WF_WORKFLOW_ID"];
		// Назначенные права
		$privs=lib::array_reindex(db::sql_select("SELECT * FROM WF_PRIVILEGE_RESOLUTION WHERE WF_RESOLUTION_ID=:res_id", array("res_id"=>$pk["WF_RESOLUTION_ID"])), "AUTH_PRIVILEGE_ID");
		foreach($privs as $priv){
			$record["AUTH_PRIVILEGE_{$priv["AUTH_PRIVILEGE_ID"]}"]=(int)$priv["AUTH_PRIVILEGE_ID"];
		}
		return $record;
	}

	/**
	 * Выставляет нужные права цепочки публикации для указанной резолюции
	 * 
	 * @param array $raw_fields			Сырые данные, например, $_REQUEST
	 * @param string $prefix			Префикс, которым дополнены сырые данные, например, _form_ для формы
	 * @param array $pk					Первичный ключ записи
	 */
	public function set_wf_privs($raw_fields, $prefix, $pk){
		$priv_object=object::factory("AUTH_PRIVILEGE");
		$privs=$priv_object->get_index_records($none, "select2", array("by_auth_object"=>"workflow"));
		$priv_object->__destruct();
		foreach($privs as $priv){
			$edge_record=array("WF_RESOLUTION_ID"=>$pk["WF_RESOLUTION_ID"], "AUTH_PRIVILEGE_ID"=>$priv["AUTH_PRIVILEGE_ID"]);
			$edge_var="{$prefix}AUTH_PRIVILEGE_{$priv["AUTH_PRIVILEGE_ID"]}";
			// Удалить старое (только если право указано явно)
			if(isset($raw_fields[$edge_var])){
				db::delete_record("WF_PRIVILEGE_RESOLUTION", $edge_record);
			}
			// Выставить новое
			if($raw_fields[$edge_var]){
				db::insert_record("WF_PRIVILEGE_RESOLUTION", $edge_record);
			}
		}
	}

	/**
	 * Помещение в метаданные полей для назначения прав цепочки публикации
	 */
	public function add_wf_privs(){
		$privs=object::factory("AUTH_PRIVILEGE")->get_index_records($none, "select2", array("by_auth_object"=>"workflow"));
		foreach($privs as $priv){
			metadata::$objects["WF_RESOLUTION"]["fields"]+=array("AUTH_PRIVILEGE_".$priv["AUTH_PRIVILEGE_ID"]=>array("title"=>$priv["_TITLE"], "type"=>"checkbox", "virtual"=>1));
		}
	}

	/**
	 * Проверяет корректность выставленных прав и кворума
	 * 
	 * @param array $raw_fields			Сырые данные, например, $_REQUEST
	 * @param string $prefix			Префикс, которым дополнены сырые данные, например, _form_ для формы
	 */
	public function check_wf_privs( $raw_fields, $prefix )
	{
		$privs = array();
		foreach( $raw_fields as $key => $value )
			if ( preg_match( "/^{$prefix}AUTH_PRIVILEGE_(\d+)$/", $key, $matches ) )
				$privs[$matches[1]] = $value ? 1 : 0;
		
		$priv_exists = array_sum( $privs );
		if ( !$priv_exists )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wf_resolution_no_privs'] );
		
		$quorum = intval( $raw_fields["{$prefix}QUORUM"] );
		if ( $priv_exists > 1 && ( $quorum < 1 || $quorum > $priv_exists ) )
			throw new Exception( $this -> te_object_name . ': ' . metadata::$lang['lang_wf_resolution_bad_quorum'] );
	}
}
?>
