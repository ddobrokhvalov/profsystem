<?php
/**
 * Класс декоратор первичных ключей "Внешняя таблица"
 *  
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class primary_key_external extends decorator{
	
	/**
	 * Возвращает служебные поля записи для кляузы SELECT
	 *
	 * @see primary_key::get_select_clause_fields()
	 */
	
	public function get_select_clause_fields () {
		return array_merge($this->inner_object->get_select_clause_fields (), array('EXTERNAL_IS_CHANGED', 'EXTERNAL_IS_DELETED'));
	}
}
?>