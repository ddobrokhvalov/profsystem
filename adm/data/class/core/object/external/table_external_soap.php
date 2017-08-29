<?php
/**
 * Адаптер декоратора "Внешние данные" для работы с SOAP
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
abstract class table_external_soap extends table_external
{
	/**
	 * WDSL внешнего источника данных
	 */
	protected $resource_wdsl = '';
	
	/**
	 * Объект внешнего источника данных
	 */
	protected $resource_handle = null;
	
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
		$this -> resource_handle = new SoapClient( $this -> resource_wdsl );
	}
	
	/**
	 * Закрытие внешнего источника данных
	 */
	public function external_close()
	{
		unset( $this -> resource_handle );
	}
}
?>
