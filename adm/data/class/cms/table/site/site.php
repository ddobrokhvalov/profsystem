<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Сайты"
 *
 * @package    RBC_Contents_5_0
 * @subpackage cms
 * @copyright  Copyright (c) 2007 RBC SOFT
 */
class site extends table{

	/**
	 * Внедрение BE
	 */
	function __construct($obj, &$full_object=""){
		if(params::$params["license_BE"]["value"]){
			metadata::$objects["SITE"]["no_add"]=1;
			metadata::$objects["SITE"]["no_delete"]=1;
		}
		parent::__construct($obj, $full_object);
	}

	/**
	 * Получение потушенных чекбоксов для привязки раздачи прав на сайты
	 *
	 * @param int $primary_id	идентификатор записи из первичной таблицы (системной роли)
	 * @return array
	 */
	public function ext_disabled_ACL_SITE($primary_id){
		return auth::get_disabled_for_auth($primary_id, $this->index_records_in, "site", "SITE");
	}

	/**
	 * Принудительное удаление связанных записей из таблицы AUTH_ACL
	 *
	 * @see table::ext_finalize_delete()
	 */
	public function ext_finalize_delete($pk, $partial=false){
		parent::ext_finalize_delete($pk, $partial);
		auth::clear_AUTH_ACL( $pk['SITE_ID'], 'SITE' );
	}
}
?>