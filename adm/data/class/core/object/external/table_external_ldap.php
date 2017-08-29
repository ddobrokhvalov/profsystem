<?php
/**
 * Адаптер декоратора "Внешние данные" для работы с LDAP
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
abstract class table_external_ldap extends table_external
{
	/**
	 * Имя LDAP-сервера
	 */
	protected $hostname = '';
	
	/**
	 * Порт LDAP-сервера
	 */
	protected $port  = '';
	
	/**
	 * Имя пользователя
	 */
	protected $bind_rdn = '';
	
	/**
	 * Пароль пользователя
	 */
	protected $bind_password = '';
	
	/**
	 * Корневой элемент
	 */
	protected $base_dn = '';
	
	/**
	 * Фильтр поиска
	 */
	protected $filter = '';
	
	/**
	 * Дескриптор соединения с LDAP-сервером
	 */
	protected $link_identifier = null;
	
	/**
	 * Ссылка на результаты поиска
	 */
	protected $result_identifier = null;
	
	/**
	 * Счетчик-указатель на текущую запись в результатах поиска
	 */
	protected $ldap_entry = null;
	
	////////////////////////////////////////////////////////////////////////
	
	/**
	 * Преобразование значения записи внешней таблицы во внутреннее представление
	 */
	public function pack_external_field( $field_name, $field_value )
	{
		return @iconv( 'utf-8', params::$params['encoding']['value'], $field_value );
	}
	
	/**
	 * Открытие внешнего источника данных
	 */
	public function external_open( $mode = 'r' )
	{
		$this -> link_identifier = ldap_connect( $this -> hostname, $this -> port );
		
	    ldap_bind( $this -> link_identifier, $this -> bind_rdn, $this -> bind_password );

		$this -> result_identifier = ldap_search( $this -> link_identifier, $this -> base_dn, $this -> filter );
		
		$this -> ldap_entry = ldap_first_entry( $this -> link_identifier, $this -> result_identifier );
	}
	
	/**
	 * Закрытие внешнего источника данных
	 */
	public function external_close()
	{
		ldap_close( $this -> link_identifier );
		unset( $this -> link_identifier );
		
		ldap_free_result( $this -> result_identifier );
		unset( $this -> result_identifier );
	}
}
?>
