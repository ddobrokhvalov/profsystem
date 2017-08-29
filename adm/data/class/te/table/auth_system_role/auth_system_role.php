<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Системные роли"
 *
 * @package		RBC_Contents_5_0
 * @subpackage te
 * @copyright	Copyright (c) 2007 RBC SOFT
 * @todo Нужен какой-нибудь интерфейс для просмотра или гарантированного снятия прав, потому что, если много назначенных ролей и у многих есть доступ в место, которое хотят администратору запретить, то становится очень трудоемко проползти по всем роля пользователя, чтобы убедиться, что он этот доступ потерял.
 */
class auth_system_role extends table_translate
{
	/**
	 * Делаем дополнительные проверки перед удалением
	 *
	 * В том числе не позволяем удалять системную роль, если:<br>
	 * 1. Заполненно системное имя, такие системные роли совсем системные, то есть используются системой<br>
	 * 2. Привязана к существующему пользователю, такие системные роли могут быть удалены только после удаления соответствующего пользователя
	 */
	public function exec_delete($pk){
		$role=$this->full_object->get_change_record($pk);
		if($role["SYSTEM_NAME"]){
			throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_delete_system_system_role"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
		}
		if($role["AUTH_USER_ID"]){
			$exist_user=db::sql_select("SELECT COUNT(*) AS COUNTER FROM AUTH_USER WHERE AUTH_USER_ID=:auth_user_id", array("auth_user_id"=>$role["AUTH_USER_ID"]));
			if($exist_user[0]["COUNTER"]){
				throw new Exception($this->te_object_name.": ".metadata::$lang["lang_no_delete_ind_role_with_user"].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
			}
		}
		$this -> call_parent( 'exec_delete', array( $pk ) );
	}

	/**
	 * Если запрашиваемая m2m относится к разделению доступа, то выставляем идентификатор таблицы разделения доступа в AUTH_ACL после отработки стандартной exec_m2m
	 */
	public function exec_m2m($m2m_name, $values, $p_ids, $s_ids, $t_ids=array()){
		$na_records = $this -> call_parent( 'exec_m2m', array( $m2m_name, $values, $p_ids, $s_ids, $t_ids ) );
		$m2m=metadata::$objects[$this->obj]["m2m"][$m2m_name];
		// Если это разделение доступа
		if($m2m["m2m_table"]=="AUTH_ACL" && is_array( $values ) ){
			// Системные права отрабатываются немного не так, как остальные права, потому что там для удобства интерфейса перевернуты вторичная и третичная таблицы
			if($m2m["list_mode"]["by_auth_object"]=="system"){
				$table_system_name="AUTH_SYSTEM";
				$object_array="t_ids";
				$privilege_array="s_ids";
			}else{
				$table_system_name=$m2m["secondary_table"];
				$object_array="s_ids";
				$privilege_array="t_ids";
			}
			// Вычисляем идентификатор таблицы объекта разденения доступа
			$table_system_name=($m2m["list_mode"]["by_auth_object"]=="system" ? "AUTH_SYSTEM" : $m2m["secondary_table"]);
			$table_id=db::sql_select("
				SELECT AUTH_OBJECT_TYPE_TABLE.*
				FROM AUTH_OBJECT_TYPE_TABLE, AUTH_OBJECT_TYPE
				WHERE AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:table_system_name
					AND AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
					AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:system_name
				", array("table_system_name"=>$table_system_name, "system_name"=>$m2m["list_mode"]["by_auth_object"]));
			// Переиндексируем для вспомогательных целей массив неизмененных записей и обновляем измененные записи (которые выставлены)
			$r_na_records=lib::array_reindex($na_records, "primary", "secondary", "tertiary");
			foreach($values as $key=>$value){
				if($value==1 && !is_array($r_na_records[$p_ids[$key]][$s_ids[$key]][$t_ids[$key]])){
					db::update_record("AUTH_ACL", array("AUTH_OBJECT_TYPE_TABLE_ID"=>$table_id[0]["AUTH_OBJECT_TYPE_TABLE_ID"]), "", array("AUTH_SYSTEM_ROLE_ID"=>$p_ids[$key], "OBJECT_ID"=>${$object_array}[$key], "AUTH_PRIVILEGE_ID"=>${$privilege_array}[$key]));
				}
			}
		}
		return $na_records;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Добавляем в случае необходимости фильтр с цепочками публикаций
	 *
	 * @see table::action_m2m()
	 */
	public function action_m2m()
	{
		if ( $_REQUEST['m2m'] == 'ACL_IBWF' || $_REQUEST['m2m'] == 'ACL_SSWF' )
		{
			$m2m_table = ( $_REQUEST['m2m'] == 'ACL_IBWF' ) ? 'INF_BLOCK' : 'AUTH_SYSTEM_SECTION';
			
			// Добавляем фильтр по цепочке публикаций
			metadata::$objects[$m2m_table]['fields']['WF_WORKFLOW_ID'] =
				array( 'title' => metadata::$lang['lang_wf_workflow_table'], 'type' => 'select2',
					'fk_table' => 'WF_WORKFLOW', 'filter_short' => 1, 'virtual' => 1 );
			
			// Настраиваем фильтр по цепочке публикаций
			if ( $_REQUEST['_f_WF_WORKFLOW_ID'] )
				metadata::$objects['AUTH_SYSTEM_ROLE']['m2m'][$_REQUEST['m2m']]['list_mode']['by_workflow_id'] = $_REQUEST['_f_WF_WORKFLOW_ID'];
		}
		
		// Настраиваем фильтр по модулю
		if ( $_REQUEST['m2m'] == 'ACL_IB' || $_REQUEST['m2m'] == 'ACL_IBWF' )
			metadata::$objects['INF_BLOCK']['fields']['PRG_MODULE_ID']['list_mode']['by_auth_object'] =
				( $_REQUEST['m2m'] == 'ACL_IB' ) ? 'inf_block' : 'workflow';
		
		$this -> call_parent( 'action_m2m' );
	}
	
	/**
	 * Добавляем в случае необходимости фильтр с цепочками публикаций
	 *
	 * @see table::action_m2med_apply()
	 */
	public function action_m2med_apply()
	{
		if ( $_REQUEST['m2m'] == 'ACL_IBWF' || $_REQUEST['m2m'] == 'ACL_SSWF' )
		{
			$m2m_table = ( $_REQUEST['m2m'] == 'ACL_IBWF' ) ? 'INF_BLOCK' : 'AUTH_SYSTEM_SECTION';
			
			metadata::$objects[$m2m_table]['fields']['WF_WORKFLOW_ID'] =
				array( 'title' => metadata::$lang['lang_wf_workflow_table'], 'type' => 'select2',
					'fk_table' => 'WF_WORKFLOW', 'filter_short' => 1, 'virtual' => 1 );
		}
		
		$this -> call_parent( 'action_m2med_apply' );
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Расширяем шаблон таблицы JavaScript'ом, хитро реагирующим на установку чекбоксов раздачи прав
	 *
	 * @see table::ext_index_template()
	 */
	public function ext_index_template($tpl_output, &$smarty)
	{
		$tpl_output = $this -> call_parent( 'ext_index_template', array( $tpl_output, &$smarty ) );
		
		if ( $_REQUEST['m2m'] != 'ACL_PAGE' )
			return $tpl_output;
		
		$privilege_list = db::sql_select( '
			select AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID, AUTH_PRIVILEGE.SYSTEM_NAME
			from AUTH_PRIVILEGE, AUTH_OBJECT_TYPE
			where AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID = AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
			and AUTH_OBJECT_TYPE.SYSTEM_NAME = :system_name',
			array( 'system_name' => 'page' ) );
		
		$privilege_array = array();
		foreach( $privilege_list as $privilege_item )
			$privilege_array[] = "'{$privilege_item['SYSTEM_NAME']}' : '{$privilege_item['AUTH_PRIVILEGE_ID']}'";
		$privilege_str = '{ ' . join( ', ', $privilege_array ) . ' }';
		
		$html =  <<<HTM
<script type="text/javascript">
	// Массив идентификаторов различных типов привилегий          
	var oPrivileges = {$privilege_str};
	
	// Установка на все чекбоксы формы метода прослушки изменения состояния          
	var oForm = document.forms['checkbox_form'];
	for ( var i = 0; i < oForm.elements.length; i++ )
		if ( oForm.elements[i].type == 'checkbox' &&
				!oForm.elements[i].disabled && !oForm.elements[i].id )
			addListener( oForm.elements[i], 'click', checkBoxClick );
	
	// Обработчик изменения состояния чекбокса          
	function checkBoxClick( oEvent )
	{
		checkBoxAction( oEvent.srcElement ? oEvent.srcElement : oEvent.target );
	}
	
	// Действие при изменении состояния заданного чекбокса          
	function checkBoxAction( oCheckbox )
	{
		var aMatch = oCheckbox.name.match( /group_id_(\d+)_(\d+)/i );
		
		if ( aMatch[2] == oPrivileges['publish'] && oCheckbox.checked )
		{
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['change'], true );
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['meta_change'], true );
		}
		else if ( aMatch[2] == oPrivileges['change'] && oCheckbox.checked )
		{
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['meta_change'], true );
		}
		else if ( aMatch[2] == oPrivileges['change'] && !oCheckbox.checked )
		{
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['publish'], false );
		}
		else if ( aMatch[2] == oPrivileges['meta_change'] && !oCheckbox.checked )
		{
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['publish'], false );
			setCheckboxState( 'group_id_' + aMatch[1] + '_' + oPrivileges['change'], false );
		}
	}
	
	// Метод устанавливает состояние заданного чекбокса в форма          
	function setCheckboxState( sName, bState )
	{
		var cCheckboxes = oForm[sName];
		for ( var i = 0; i < cCheckboxes.length; i++ )
			if ( cCheckboxes[i].type == 'checkbox' )
				cCheckboxes[i].checked = bState;
	}
	
	// Переопределяем стандартную функцию CheckAllBoxes          
	function CheckAllBoxes( column, checkbox )
	{
		for ( var i = 0; i < oForm.elements.length; i++ )
		{
			if ( oForm.elements[i].type == 'checkbox' && !oForm.elements[i].disabled &&
					oForm.elements[i].getAttribute( 'column' ) == column )
				oForm.elements[i].checked = checkbox.checked;
		}
		
		if ( column == oPrivileges['publish'] && checkbox.checked )
		{
			var oChangeCheckbox = document.getElementById( 'check_all' + oPrivileges['change'] );
			oChangeCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['change'], oChangeCheckbox );
			
			var oMetaChangeCheckbox = document.getElementById( 'check_all' + oPrivileges['meta_change'] );
			oMetaChangeCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['meta_change'], oMetaChangeCheckbox );
		}
		else if ( column == oPrivileges['change'] && checkbox.checked )
		{
			var oMetaChangeCheckbox = document.getElementById( 'check_all' + oPrivileges['meta_change'] );
			oMetaChangeCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['meta_change'], oMetaChangeCheckbox );
		}
		else if ( column == oPrivileges['change'] && !checkbox.checked )
		{
			var oPublishCheckbox = document.getElementById( 'check_all' + oPrivileges['publish'] );
			oPublishCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['publish'], oPublishCheckbox );
		}
		else if ( column == oPrivileges['meta_change'] && !checkbox.checked )
		{
			var oPublishCheckbox = document.getElementById( 'check_all' + oPrivileges['publish'] );
			oPublishCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['publish'], oPublishCheckbox );
			
			var oChangeCheckbox = document.getElementById( 'check_all' + oPrivileges['change'] );
			oChangeCheckbox.checked = checkbox.checked; CheckAllBoxes( oPrivileges['change'], oChangeCheckbox );
		}
	}
</script>
HTM;
		
	    return $tpl_output . $html;
	}

	/**
	 * Дополняем механизм разделения доступа пониманием таблицы и типа разделения доступа
	 */
	public function ext_m2m($m2m){
		$binds=array();
		// Не делаем это для системных прав, потому что у них собственные права, которые гарантированно никем больше не использвуются
		if($m2m["m2m_table"]=="AUTH_ACL" && $m2m["tertiary_table"]!="AUTH_SYSTEM"){
			$clause="
				AND AUTH_ACL.AUTH_OBJECT_TYPE_TABLE_ID IN (
					SELECT AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_TABLE_ID
					FROM AUTH_OBJECT_TYPE_TABLE, AUTH_OBJECT_TYPE
					WHERE AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:m2m_aot_table
						AND AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
						AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:m2m_aot
				)
			";
			$binds=array("m2m_aot"=>$m2m["list_mode"]["by_auth_object"], "m2m_aot_table"=>$m2m["secondary_table"]);
		}
		return array($clause, $binds);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает, если надо, идентификатор индивидуальной роли текущего пользователя
	 *
	 * Если пользователь является главным администратором или администратором сайта, то возвращается false.
	 * В противном случае возвращается идентификатор индивидуальной роли пользователя. Если ее еще не было, то
	 * она вначале создается, а потом уже возвращается ее идентификатор
	 *
	 * @param int $site_id	идентификатор сайта для которого нужно убедиться, что пользователь не является администратором сайта
	 * @return mixed
	 */
	public function get_ind_role($site_id=""){
		// Если пользователь является главным администратором или администратором указанного сайта, то индивидуальная роль ему не требуется
		if($this->auth->is_main_admin || ($site_id && isset($this->auth->sites_ids[$site_id]))){
			return false;
		}
		$ind_role=db::sql_select("SELECT * FROM AUTH_SYSTEM_ROLE WHERE AUTH_USER_ID=:auth_user_id", array("auth_user_id"=>$this->auth->user_info["AUTH_USER_ID"]));
		// Если роли не обнаружилось, то создаем ее, иначе возвращаем идентификатор найденной роли
		if(count($ind_role)==0){
			$ind_role_aggregator=db::sql_select("SELECT * FROM AUTH_SYSTEM_ROLE WHERE SYSTEM_NAME=:system_name", array("system_name"=>"ind_roles"));
			
			$auth_user_obj = object::factory("AUTH_USER");
			$ind_role_title=$auth_user_obj->get_record_title(array("AUTH_USER_ID"=>$this->auth->user_info["AUTH_USER_ID"]));
			$auth_user_obj->__destruct();
			
			db::insert_record("AUTH_SYSTEM_ROLE", array(
				"TITLE"=>$ind_role_title,
				"PARENT_ID"=>(int)$ind_role_aggregator[0]["AUTH_SYSTEM_ROLE_ID"], // Если роли-агрегатора не обнаружилось, то индивидуальные роли будут складываться прямо в корень
				"AUTH_USER_ID"=>$this->auth->user_info["AUTH_USER_ID"],
			));
			$ind_role_id=db::last_insert_id("AUTH_SYSTEM_ROLE_SEQ");
			db::insert_record("TABLE_TRANSLATE", array("TE_OBJECT_ID"=>$this->te_object_id, "LANG_ID"=>$this->get_interface_lang(), "CONTENT_ID"=>$ind_role_id, "FIELD_NAME"=>"TITLE", "VALUE"=>$ind_role_title));
			db::insert_record("AUTH_USER_SYSTEM_ROLE", array("AUTH_USER_ID"=>$this->auth->user_info["AUTH_USER_ID"], "AUTH_SYSTEM_ROLE_ID"=>$ind_role_id));
		}else{
			$ind_role_id=$ind_role[0]["AUTH_SYSTEM_ROLE_ID"];
		}
		return $ind_role_id;
	}

	/**
	 * Удаляет индивидуальную роль пользователя в том случае, если это можно
	 *
	 * А можно это в том случае, если соответствующий пользователь удален и роль не привязана к другим пользователям.
	 * Если же пользователь удален, но роль привязана, то она отвязывается от пользователя.
	 * При этом исключений не бросается. Метод либо делает это, либо нет.
	 *
	 * @param int $auth_user_id	идентификатор пользователя, у которого пытаемся удалить индивидуальную роль
	 */
	public function delete_ind_role($auth_user_id){
		$ind_role=db::sql_select("SELECT * FROM AUTH_SYSTEM_ROLE WHERE AUTH_USER_ID=:auth_user_id", array("auth_user_id"=>$auth_user_id));
		$exist_user=db::sql_select("SELECT COUNT(*) AS COUNTER FROM AUTH_USER WHERE AUTH_USER_ID=:auth_user_id", array("auth_user_id"=>$auth_user_id));
		$exist_linked_users=db::sql_select("SELECT COUNT(*) AS COUNTER FROM AUTH_USER_SYSTEM_ROLE WHERE AUTH_SYSTEM_ROLE_ID=:role_id", array("role_id"=>$ind_role[0]["AUTH_SYSTEM_ROLE_ID"]));
		if(count($ind_role) && !$exist_user[0]["COUNTER"]){
			if(!$exist_linked_users[0]["COUNTER"]){
				$this->full_object->exec_delete(array("AUTH_SYSTEM_ROLE_ID"=>$ind_role[0]["AUTH_SYSTEM_ROLE_ID"]));
			}else{
				db::update_record("AUTH_SYSTEM_ROLE", array("AUTH_USER_ID"=>0), "", array("AUTH_SYSTEM_ROLE_ID"=>$ind_role[0]["AUTH_SYSTEM_ROLE_ID"]));
			}
		}
	}
	
	/**
	* Регистрирует событие в журнале
	* Добавляет к стандартной регистрации в журнале операций над записями вызов процедуры добавления
	* записи в журнал log_permissions
	*
	* @param $type - операция журнала
	* @param $fields - поля для журнала
	*/
	public function log_register($type, $fields) {
		$this->call_parent('log_register',array($type, $fields));
		$this->log_register_permissions($fields['m2m_name'], $fields);
	}
	
	/**
	* Регистрирует событие в журнале изменения прав
	* @param $type - операция журнала
	* @param $fields - поля для журнала
	*/
	public function log_register_permissions($type, $fields) {
 		if (!log::is_enabled('log_permissions') || !$fields['m2m_changed']) return;
		$fields=$this->get_info_for_log($fields);
		
		$role_id = array_shift(array_keys($fields['m2m_changed']));

		$log_info = array (
			'role_id' => $role_id,
			'role_name' => $this->get_record_title(array('AUTH_SYSTEM_ROLE_ID'=>$role_id)),
		);
		
		foreach ($fields['m2m_changed'] as $pid=>$data) {
			$fields['log_params']['extended_info']=array('m2m'=>$fields['m2m'], 'm2m_changed'=>$this->sort_m2m($data));
			log::register('log_permissions', $type, $log_info, $this->te_object_id, $role_id, null, null, $fields['log_params']['extended_info']);
		}
	}
	
	/**
	 * Пример дополнения ссылок фильтрацией по умолчальной цепочке публикаций
	 *
	 * @see table::get_index_ops()
	 */
	public function _get_index_ops( $record )
	{
		static $workflow_default_id;
		
		if ( is_null( $workflow_default_id ) )
			$workflow_default_id = $this -> get_default_workflow_id();
		
		$ops = $this -> call_parent( 'get_index_ops', array( $record ) );
		
		if ( isset( $ops['_ops'] ) && is_array( $ops['_ops'] ) ) 
			foreach ( $ops['_ops'] as $ops_index => $ops_value )
				if ( $ops_value['m2m'] == 'ACL_IBWF' || $ops_value['m2m'] == 'ACL_SSWF' )
					$ops['_ops'][$ops_index]['url'] .= '&_f_WF_WORKFLOW_ID=' . $workflow_default_id;
		
		return $ops;
	}	

	/**
	 * пример дополнения ссылок фильтрацией по умолчальной цепочке публикаций
	 *
	 * @see table::get_header_tabs()
	 */
	public function _get_header_tabs( $pk, $mark_select = 'change' )
	{
		static $workflow_default_id;
		
		if ( is_null( $workflow_default_id ) )
			$workflow_default_id = $this -> get_default_workflow_id();
		
		$tabs = $this -> call_parent( 'get_header_tabs', array( $pk, $mark_select ) );
		
		foreach ( $tabs as $tab_index => $tab_value )
			if ( $tab_value['m2m'] == 'ACL_IBWF' || $tab_value['m2m'] == 'ACL_SSWF' )
				$tabs[$tab_index]['url'] .= '&_f_WF_WORKFLOW_ID=' . $workflow_default_id;
		
		return $tabs;
	}
}
?>
