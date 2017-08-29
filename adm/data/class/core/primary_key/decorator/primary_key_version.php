<?php
/**
 * Класс декоратор первичных ключей "Версия в первичном ключе"
 *
 * Здесь происходит модификация только выборки полей, так как в прочих случаях система должна
 * сама определять какую версию использовать, независимо от того, что просит пользователь.
 * Приоритет модификации всегда у тестовой версии, а рабочая версия используется только для 
 * публикации и отмены публикации. Еще на нее можно посмотреть в списке, но редактируется
 * она только в том случае, если по каким-то причинам нет тестовой версии записи. Такой случай
 * является нештатной ситуацией с точки зрения логики организации данных, но будет корректно
 * отработан системой.
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class primary_key_version extends decorator{
	
	/**
	 * Возвращает служебные поля записи для кляузы SELECT
	 *
	 * @see primary_key::get_select_clause_fields()
	 */
	
	public function get_select_clause_fields () {
		return array_merge($this->inner_object->get_select_clause_fields (), array('VERSION', 'TIMESTAMP'));
	}
	

	/**
	 * Возвращает список названий полей, дополняющих первичный ключ
	 *
	 * @return array
	 */
	public function ext_pk_fields(){
		return array_merge($this->inner_object->ext_pk_fields(), array("VERSION"));
	}
}
?>