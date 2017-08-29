<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Права на объекты системы"
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class auth_privilege extends table_translate {

	/**
	 * Ограничиваем список привелегий указанным типом объекта разделения доступа
	 */
	public function ext_index_by_list_mode($mode, $list_mode){
		list($where, $binds)=$this -> call_parent( 'ext_index_by_list_mode', array($mode, $list_mode) );
		if($list_mode["by_auth_object"]){
			$where.=" AND AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=(SELECT AUTH_OBJECT_TYPE_ID FROM AUTH_OBJECT_TYPE WHERE SYSTEM_NAME=:aot_system_name LIMIT 1)";
			$binds=array_merge($binds, array("aot_system_name"=>$list_mode["by_auth_object"]));
		}
		if($list_mode["by_workflow_id"]){
			$where.=" and exists (
				select * from WF_PRIVILEGE_RESOLUTION, WF_RESOLUTION, WF_STATE
				where WF_PRIVILEGE_RESOLUTION.AUTH_PRIVILEGE_ID = AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID and
					WF_PRIVILEGE_RESOLUTION.WF_RESOLUTION_ID = WF_RESOLUTION.WF_RESOLUTION_ID and
					WF_RESOLUTION.FIRST_STATE_ID = WF_STATE.WF_STATE_ID and
					WF_STATE.WF_WORKFLOW_ID = :wf_workflow_id )";
			$binds=array_merge($binds, array("wf_workflow_id"=>$list_mode["by_workflow_id"]));
		}
		return array($where, $binds);
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на систему
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_SP($primary_id){
		$disabled=db::sql_select("SELECT * FROM AUTH_ACL WHERE AUTH_PRIVILEGE_ID IN ({$this->index_records_in}) AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN(".auth::get_parent_roles_in($primary_id, false).")");
		foreach($disabled as $dis){
			$r_disabled[$dis["AUTH_PRIVILEGE_ID"]][$dis["OBJECT_ID"]]=1;
		}
		return $r_disabled;
	}
}
?>