<?php
/**
 * Адаптер декоратора "Внешние данные" для работы с XML
 *
 * @package		RBC_Contents_5_0
 * @subpackage	core
 * @copyright	Copyright (c) 2007 RBC SOFT
 */
abstract class table_external_xml extends table_external
{
	/**
	 * Ссылка на внешний источник данных
	 */
	protected $resource_url = '';
	
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
		$this -> resource_handle = new SimpleXMLElement( $this -> resource_url, null, true );
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
